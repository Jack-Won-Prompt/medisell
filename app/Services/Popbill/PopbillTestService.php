<?php

namespace App\Services\Popbill;

use App\Models\PopbillTest;

/**
 * 관리자 › 팝빌 테스트 — 웹 화면에서 문자·현금영수증·세금계산서를 실제로 보내고/발행·취소해 본다.
 * 사이트의 시뮬레이트 설정과 무관하게 실호출하지만, 서버(테스트/운영)는 POPBILL_IS_TEST 를 따른다.
 * 기록은 popbill_tests 에만 남긴다(주문·매출 이력과 섞지 않음).
 */
class PopbillTestService
{
    /** 실수 방지 — 테스트 발행 금액 상한 */
    public const MAX_AMOUNT = 10000;

    public function __construct(
        private PopbillMessageService $sms,
        private PopbillCashbillService $cashbill,
        private PopbillTaxinvoiceService $taxinvoice,
    ) {}

    private function corp(): string
    {
        return preg_replace('/\D/', '', (string) config('popbill.corp_num'));
    }

    private function userId(): ?string
    {
        return config('popbill.user_id') ?: null;
    }

    /** 화면 상단 상태표 — 각 항목 실패해도 나머지는 보여 준다 */
    public function status(): array
    {
        $rows = [
            '팝빌 서버'   => config('popbill.IsTest') ? '테스트 서버 (POPBILL_IS_TEST=true — 실제 휴대폰·국세청으로 가지 않음)' : '운영 서버',
            '발행 사업자' => $this->corp().' · 회원 아이디 '.($this->userId() ?? '(없음)'),
            '사이트 설정' => '문자 '.config('popbill.sms.mode').' · 현금영수증 '.(config('popbill.cashbill.simulate') ? '시뮬레이트' : '실발행')
                .' · 세금계산서 '.(config('popbill.simulate') ? '시뮬레이트' : '실발행'),
            '발신번호(설정)' => config('popbill.sms.sender'),
        ];
        $try = function (string $label, \Closure $fn) use (&$rows) {
            try {
                $rows[$label] = $fn();
            } catch (\Throwable $e) {
                $rows[$label] = '오류: '.$e->getMessage();
            }
        };
        $try('승인 발신번호', fn () => collect($this->sms->senderNumbers())
            ->map(fn ($state, $num) => $num.($state === 1 ? '' : '(미승인)'))->implode(', ') ?: '없음');
        $try('포인트', function () {
            [$partner, $member, $smsCost, $lmsCost] = $this->sms->balances();

            return '파트너 '.number_format($partner).' · 회원 '.number_format($member)." · 단가 SMS {$smsCost} / LMS {$lmsCost}";
        });
        $try('세금계산서 인증서', function () {
            [$msg, $expire, $subject] = $this->taxinvoice->certStatus($this->corp(), $this->userId());

            return $msg.' · 만료 '.substr($expire, 0, 4).'-'.substr($expire, 4, 2).'-'.substr($expire, 6, 2);
        });

        return $rows;
    }

    public function sendSms(int $adminId, string $to, string $text): PopbillTest
    {
        $to = preg_replace('/\D/', '', $to);
        $base = ['admin_id' => $adminId, 'kind' => 'sms', 'is_test_server' => (bool) config('popbill.IsTest'),
            'receiver' => $to, 'content' => $text];
        try {
            $r = $this->sms->send($to, $text, '[메디셀] 테스트', 'live');

            return PopbillTest::create($base + ['status' => 'sent', 'confirm_num' => $r['receipt_num'], 'popbill_state' => $r['msg_type'].' 접수']);
        } catch (\Throwable $e) {
            return PopbillTest::create($base + ['status' => 'failed', 'error_message' => $e->getMessage()]);
        }
    }

    public function issueCashbill(int $adminId, int $amount, string $usage, string $identity): PopbillTest
    {
        $identity = preg_replace('/\D/', '', $identity);
        $supply = (int) round($amount / 1.1);
        $key = 'T'.now()->format('ymdHis').random_int(10, 99);
        $base = ['admin_id' => $adminId, 'kind' => 'cashbill', 'is_test_server' => (bool) config('popbill.IsTest'),
            'receiver' => $identity, 'amount' => $amount, 'usage' => $usage, 'mgt_key' => $key];

        try {
            $cb = $this->cashbill->newCashbill();
            $cb->mgtKey = $key;
            $cb->tradeType = '승인거래';
            $cb->tradeUsage = $usage;
            $cb->tradeOpt = '일반';
            $cb->taxationType = '과세';
            $cb->franchiseCorpNum = $this->corp();
            $cb->totalAmount = (string) $amount;
            $cb->supplyCost = (string) $supply;
            $cb->tax = (string) ($amount - $supply);
            $cb->serviceFee = '0';
            $cb->identityNum = $identity;
            $cb->customerName = '발행테스트';
            $cb->itemName = '현금영수증 발행 테스트';
            $cb->orderNumber = $key;
            $cb->smssendYN = false;
            $this->cashbill->registIssue($this->corp(), $cb, $this->userId(), '관리자 발행 테스트');
            $info = $this->cashbill->getInfo($this->corp(), $key);

            return PopbillTest::create($base + ['status' => 'issued', 'confirm_num' => $info->confirmNum ?? null,
                'trade_date' => $info->tradeDate ?? now()->format('Ymd'), 'popbill_state' => '발행']);
        } catch (\Throwable $e) {
            return PopbillTest::create($base + ['status' => 'failed', 'error_message' => $e->getMessage()]);
        }
    }

    public function issueTaxinvoice(int $adminId, int $amount, array $to): PopbillTest
    {
        $s = config('popbill.supplier');
        $corp = $this->corp();
        $recv = preg_replace('/\D/', '', $to['corp_num'] ?? '') ?: $corp;
        $supply = (int) round($amount / 1.1);
        $key = 'T'.now()->format('ymdHis').random_int(10, 99);
        $base = ['admin_id' => $adminId, 'kind' => 'taxinvoice', 'is_test_server' => (bool) config('popbill.IsTest'),
            'receiver' => $recv, 'receiver_name' => ($to['corp_name'] ?? '') ?: $s['corp_name'], 'amount' => $amount, 'mgt_key' => $key];

        try {
            $inv = $this->buildTaxinvoice($key, $s, $corp, $recv, $to, $supply, $amount - $supply, $amount);
            $this->taxinvoice->registIssue($corp, $inv, $this->userId(), false, '관리자 발행 테스트');
            $info = $this->taxinvoice->getInfo($corp, $key);

            return PopbillTest::create($base + ['status' => 'issued', 'confirm_num' => $info->ntsconfirmNum ?? null,
                'popbill_state' => '발행 (상태 '.($info->stateCode ?? '?').')']);
        } catch (\Throwable $e) {
            return PopbillTest::create($base + ['status' => 'failed', 'error_message' => $e->getMessage()]);
        }
    }

    /** 취소 — 현금영수증은 취소거래, 세금계산서는 발행취소(국세청 전송 후면 수정세금계산서) */
    public function cancel(PopbillTest $t): PopbillTest
    {
        if (! $t->cancellable()) {
            throw new \RuntimeException('발행 상태인 현금영수증·세금계산서만 취소할 수 있습니다.');
        }
        try {
            if ($t->kind === 'cashbill') {
                $ck = substr($t->mgt_key, 0, 21).'-C';
                $this->cashbill->revokeRegistIssue($this->corp(), $ck, (string) $t->confirm_num, (string) $t->trade_date, $this->userId(), '테스트 취소');
                $t->update(['status' => 'cancelled', 'cancel_mgt_key' => $ck, 'popbill_state' => '취소거래 발행', 'cancelled_at' => now(), 'error_message' => null]);

                return $t;
            }

            try {
                $this->taxinvoice->cancelIssue($this->corp(), $t->mgt_key, '테스트 취소', $this->userId());
                $t->update(['status' => 'cancelled', 'popbill_state' => '발행취소(국세청 전송 전)', 'cancelled_at' => now(), 'error_message' => null]);
            } catch (\Throwable $e) {
                if (! $t->confirm_num) {
                    throw $e;
                }
                // 이미 국세청 전송 — 수정세금계산서(계약의 해제, 마이너스)로 상계
                $s = config('popbill.supplier');
                $key = substr($t->mgt_key, 0, 21).'-M';
                $supply = (int) round($t->amount / 1.1);
                $inv = $this->buildTaxinvoice($key, $s, $this->corp(), $t->receiver, ['corp_name' => $t->receiver_name],
                    -$supply, -($t->amount - $supply), -$t->amount);
                $inv->modifyCode = 4;
                $inv->orgNTSConfirmNum = $t->confirm_num;
                $this->taxinvoice->registIssue($this->corp(), $inv, $this->userId(), false, '테스트 취소 — 계약의 해제');
                $t->update(['status' => 'cancelled', 'cancel_mgt_key' => $key, 'popbill_state' => '수정세금계산서(계약의 해제) 발행', 'cancelled_at' => now(), 'error_message' => null]);
            }
        } catch (\Throwable $e) {
            $t->update(['error_message' => '취소 실패: '.$e->getMessage()]);
        }

        return $t;
    }

    private function buildTaxinvoice(string $key, array $s, string $corp, string $recv, array $to, int $supply, int $tax, int $total)
    {
        $inv = $this->taxinvoice->newInvoice();
        $inv->writeDate = now()->format('Ymd');
        $inv->chargeDirection = '정과금';
        $inv->issueType = '정발행';
        $inv->purposeType = '영수';
        $inv->taxType = '과세';
        $inv->invoicerCorpNum = $corp;
        $inv->invoicerMgtKey = $key;
        $inv->invoicerCorpName = $s['corp_name'];
        $inv->invoicerCEOName = $s['ceo_name'];
        $inv->invoicerAddr = $s['addr'];
        $inv->invoicerBizType = $s['biz_type'];
        $inv->invoicerBizClass = $s['biz_class'];
        $inv->invoicerTEL = $s['tel'];
        $inv->invoicerEmail = $s['email'];
        $self = $recv === $corp;
        $inv->invoiceeType = '사업자';
        $inv->invoiceeCorpNum = $recv;
        $inv->invoiceeCorpName = ($to['corp_name'] ?? '') ?: $s['corp_name'];
        $inv->invoiceeCEOName = ($to['ceo_name'] ?? '') ?: ($self ? $s['ceo_name'] : '-');
        $inv->invoiceeAddr = $self ? $s['addr'] : '';
        $inv->invoiceeEmail1 = ($to['email'] ?? '') ?: ($self ? $s['email'] : '');
        $inv->supplyCostTotal = (string) $supply;
        $inv->taxTotal = (string) $tax;
        $inv->totalAmount = (string) $total;
        $d = $this->taxinvoice->newDetail();
        $d->serialNum = 1;
        $d->purchaseDT = now()->format('Ymd');
        $d->itemName = '세금계산서 발행 테스트';
        $d->qty = '1';
        $d->supplyCost = (string) $supply;
        $d->tax = (string) $tax;
        $inv->detailList = [$d];

        return $inv;
    }
}
