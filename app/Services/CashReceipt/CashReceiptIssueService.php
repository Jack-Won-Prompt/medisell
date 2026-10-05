<?php

namespace App\Services\CashReceipt;

use App\Models\CashReceipt;
use App\Models\Order;
use App\Services\Popbill\PopbillCashbillService;
use Illuminate\Support\Str;

/**
 * 주문 → 팝빌 현금영수증 발행/취소.
 * - 무통장 주문에서 고객이 신청한 경우 입금 확인(markPaid) 때 자동 발행, 관리자 화면에서 수동 발행·취소
 * - 시뮬레이트 모드(config popbill.cashbill.simulate, 기본 true)에서는 팝빌을 부르지 않고 이력만 남긴다
 */
class CashReceiptIssueService
{
    public const USAGES = ['income' => '소득공제용', 'expense' => '지출증빙용'];

    public function __construct(private PopbillCashbillService $popbill) {}

    /** 주문에 대한 현금영수증 발행 — type: income|expense, identity: 휴대폰/사업자번호 */
    public function issueForOrder(Order $order, string $type, string $identity): CashReceipt
    {
        $order->loadMissing('items', 'user');
        $identity = preg_replace('/\D/', '', $identity);

        if (! isset(self::USAGES[$type])) {
            throw new \RuntimeException('현금영수증 용도(소득공제/지출증빙)를 골라 주세요.');
        }
        if (! preg_match('/^\d{10,13}$/', $identity)) {
            throw new \RuntimeException('휴대폰번호 또는 사업자번호를 확인해 주세요. (숫자 10~13자리)');
        }
        if (! in_array($order->status, ['paid', 'preparing', 'shipped', 'done'])) {
            throw new \RuntimeException('결제완료(입금확인) 이후 주문만 발행할 수 있습니다.');
        }
        if ($order->isCardPayment()) {
            throw new \RuntimeException('카드 결제 주문은 카드매출전표가 증빙이라 현금영수증을 발행할 수 없습니다.');
        }
        if ($order->cashReceipts()->whereIn('status', ['issued', 'simulated'])->exists()) {
            throw new \RuntimeException('이미 현금영수증이 발행된 주문입니다.');
        }
        if ($order->taxInvoices()->whereIn('status', ['issued', 'simulated'])->exists()) {
            throw new \RuntimeException('세금계산서가 발행된 주문이라 현금영수증을 함께 발행할 수 없습니다.');
        }
        if ($order->total <= 0) {
            throw new \RuntimeException('결제금액이 0원인 주문입니다.');
        }

        $total = (int) $order->total;
        $supply = (int) round($total / 1.1);
        $tax = $total - $supply;
        $corpNum = preg_replace('/\D/', '', (string) config('popbill.corp_num'));
        $mgtKey = $this->makeMgtKey($order);

        $snapshot = [
            'order_id'      => $order->id,
            'user_id'       => $order->user_id,
            'mgt_key'       => $mgtKey,
            'trade_usage'   => self::USAGES[$type],
            'identity_num'  => $identity,
            'supply_amount' => $supply,
            'tax_amount'    => $tax,
            'total_amount'  => $total,
        ];

        if (config('popbill.cashbill.simulate', true)) {
            return CashReceipt::create($snapshot + [
                'status'        => 'simulated',
                'popbill_state' => '시뮬레이트',
                'confirm_num'   => 'SIM-'.strtoupper(Str::random(8)),
                'trade_date'    => now()->format('Ymd'),
                'issued_at'     => now(),
            ]);
        }

        try {
            $cb = $this->popbill->newCashbill();
            $cb->mgtKey = $mgtKey;
            $cb->tradeType = '승인거래';
            $cb->tradeUsage = self::USAGES[$type];
            $cb->tradeOpt = '일반';
            $cb->taxationType = '과세';
            $cb->franchiseCorpNum = $corpNum;
            $cb->totalAmount = (string) $total;
            $cb->supplyCost = (string) $supply;
            $cb->tax = (string) $tax;
            $cb->serviceFee = '0';
            $cb->identityNum = $identity;
            $cb->customerName = mb_substr($order->buyer_hospital ?: ($order->user?->company_name ?: ($order->user?->name ?? $order->receiver_name)), 0, 100);
            $cb->itemName = $this->itemName($order);
            $cb->orderNumber = $order->order_no;
            $cb->email = (string) ($order->user?->email ?? '');
            $cb->smssendYN = false;

            $this->popbill->registIssue($corpNum, $cb, config('popbill.user_id') ?: null);
            $info = $this->popbill->getInfo($corpNum, $mgtKey);

            return CashReceipt::create($snapshot + [
                'status'        => 'issued',
                'confirm_num'   => $info->confirmNum ?? null,
                'trade_date'    => $info->tradeDate ?? now()->format('Ymd'),
                'popbill_state' => $info->stateMemo ?? '발행',
                'issued_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            CashReceipt::create($snapshot + ['status' => 'failed', 'error_message' => $e->getMessage()]);
            throw $e;
        }
    }

    /** 취소 — 국세청 규칙상 원본을 지우지 않고 '취소거래' 현금영수증을 새 문서번호로 발행한다 */
    public function cancel(CashReceipt $cr, ?string $memo = null): void
    {
        if ($cr->status === 'simulated') {
            $cr->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            return;
        }
        if ($cr->status !== 'issued' || ! $cr->confirm_num || ! $cr->trade_date) {
            throw new \RuntimeException('발행완료 상태(승인번호 있음)인 현금영수증만 취소할 수 있습니다.');
        }
        $corpNum = preg_replace('/\D/', '', (string) config('popbill.corp_num'));
        $cancelKey = substr($cr->mgt_key, 0, 21).'-C';
        $this->popbill->revokeRegistIssue($corpNum, $cancelKey, $cr->confirm_num, $cr->trade_date, config('popbill.user_id') ?: null, $memo);
        $cr->update(['status' => 'cancelled', 'cancel_mgt_key' => $cancelKey, 'cancelled_at' => now()]);
    }

    /** 고객 보기 URL (시뮬레이트는 없음) */
    public function customerUrl(CashReceipt $cr): ?string
    {
        if ($cr->status === 'simulated') {
            return null;
        }
        $corpNum = preg_replace('/\D/', '', (string) config('popbill.corp_num'));

        return $this->popbill->getMailUrl($corpNum, $cr->mgt_key, config('popbill.user_id') ?: null);
    }

    public function popupUrl(CashReceipt $cr): ?string
    {
        if ($cr->status === 'simulated') {
            return null;
        }
        $corpNum = preg_replace('/\D/', '', (string) config('popbill.corp_num'));

        return $this->popbill->getPopUpUrl($corpNum, $cr->mgt_key, config('popbill.user_id') ?: null);
    }

    /** 문서번호(최대 24자, 재사용 불가) — C + 주문번호 + 차수. 취소 후 재발행하면 차수가 올라간다 */
    private function makeMgtKey(Order $order): string
    {
        $base = 'C'.substr(preg_replace('/[^A-Za-z0-9]/', '', $order->order_no), 0, 17);

        return $base.'-'.(CashReceipt::where('mgt_key', 'like', $base.'-%')->count() + 1);
    }

    private function itemName(Order $order): string
    {
        $first = $order->items->first()?->product_name ?? '의료소모품';
        $more = $order->items->count() - 1;

        return mb_substr($first, 0, 80).($more > 0 ? " 외 {$more}건" : '');
    }
}
