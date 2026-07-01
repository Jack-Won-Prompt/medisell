<?php

namespace App\Services\Bank;

use App\Models\BankCollectJob;
use App\Models\BankDeposit;
use App\Models\Order;
use App\Services\Popbill\PopbillEasyFinBankService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * 무통장 입금 자동확인 — 팝빌 계좌조회로 입금내역 수집 후 대기주문과 매칭.
 * simulate=true 면 팝빌 대신 대기주문 기반 가상 입금건을 생성한다.
 */
class BankDepositService
{
    public function __construct(private PopbillEasyFinBankService $popbill) {}

    private function cfg(): array
    {
        return config('popbill.bank');
    }

    /** 입금내역 수집 (기간). 반환: [job, importedCount] */
    public function collect(string $sDate, string $eDate): array
    {
        $c = $this->cfg();

        $job = BankCollectJob::create([
            'bank_code'   => $c['bank_code'],
            'account_num' => $c['account_num'] ?: 'SIM',
            's_date'      => $sDate,
            'e_date'      => $eDate,
            'state'       => 'collecting',
        ]);

        if ($c['simulate']) {
            $count = $this->simulateDeposits($job);
            $job->update(['state' => 'done', 'tx_count' => $count, 'job_id' => 'SIM-'.strtoupper(Str::random(8))]);

            return [$job, $count];
        }

        // 실연동: RequestJob → (완료대기) → Search → 적재
        $corp = preg_replace('/\D/', '', (string) $c['corp_num']);
        $jobId = $this->popbill->requestJob($corp, $c['bank_code'], $c['account_num'], $sDate, $eDate);
        $job->update(['job_id' => $jobId]);

        // 수집 완료 대기(간단 폴링)
        for ($i = 0; $i < 10; $i++) {
            $state = $this->popbill->getJobState($corp, $jobId);
            if ((int) ($state->jobState ?? 0) === 3) {
                break;
            }
            usleep(700000);
        }

        $count = $this->importFromPopbill($corp, $job);
        $job->update(['state' => 'done', 'tx_count' => $count]);

        return [$job, $count];
    }

    /** 시뮬레이트: 무통장 대기주문을 '입금됨'으로 가정한 가상 입금건 생성 */
    private function simulateDeposits(BankCollectJob $job): int
    {
        $orders = Order::where('status', 'pending')
            ->where('payment_method', 'bank')
            ->whereNotNull('depositor')
            ->get();

        $n = 0;
        foreach ($orders as $o) {
            $tid = 'SIM-'.$o->order_no;
            if (BankDeposit::where('tid', $tid)->exists()) {
                continue;
            }
            BankDeposit::create([
                'tid'         => $tid,
                'bank_code'   => $job->bank_code,
                'account_num' => $job->account_num,
                'trade_date'  => Carbon::today(),
                'trade_time'  => now()->format('His'),
                'amount'      => $o->total,
                'depositor'   => $o->depositor,
            ]);
            $n++;
        }

        return $n;
    }

    /** 실연동: 팝빌 Search 결과를 bank_deposits 로 적재 (입금건만) */
    private function importFromPopbill(string $corp, BankCollectJob $job): int
    {
        $res = $this->popbill->search($corp, $job->job_id, ['I']);
        $n = 0;
        foreach (($res->list ?? []) as $r) {
            $accIn = (int) preg_replace('/\D/', '', (string) ($r->accIn ?? '0'));
            if ($accIn <= 0) {
                continue;
            }
            $tid = $r->tid ?? null;
            if ($tid && BankDeposit::where('tid', $tid)->exists()) {
                continue;
            }
            BankDeposit::create([
                'tid'         => $tid,
                'bank_code'   => $job->bank_code,
                'account_num' => $job->account_num,
                'trade_date'  => isset($r->trdate) ? Carbon::createFromFormat('Ymd', substr($r->trdate, 0, 8)) : null,
                'trade_time'  => isset($r->trdate) ? substr($r->trdate, 8) : null,
                'amount'      => $accIn,
                'balance'     => (int) preg_replace('/\D/', '', (string) ($r->balance ?? '0')),
                'depositor'   => $r->remark1 ?? ($r->remark2 ?? null),
            ]);
            $n++;
        }

        return $n;
    }

    /** 미매칭 입금건을 대기주문과 자동 매칭(입금자명+금액). 반환: 확정 건수 */
    public function autoMatch(): int
    {
        $deposits = BankDeposit::whereNull('matched_order_id')->get();
        $matched = 0;

        foreach ($deposits as $d) {
            $order = Order::where('status', 'pending')
                ->where('payment_method', 'bank')
                ->where('total', $d->amount)
                ->get()
                ->first(fn ($o) => BankDeposit::normalize($o->depositor) === BankDeposit::normalize($d->depositor));

            if ($order) {
                $this->confirm($d, $order);
                $matched++;
            }
        }

        return $matched;
    }

    /** 입금건 ↔ 주문 확정 (결제완료 처리) */
    public function confirm(BankDeposit $deposit, Order $order): void
    {
        $deposit->update(['matched_order_id' => $order->id, 'confirmed_at' => now()]);
        $order->markPaid();
    }
}
