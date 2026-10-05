<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use App\Services\SmsNotifier;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * 고객 안내 문자 — 응답을 보낸 뒤(dispatchAfterResponse) 발송. 실패는 sms_logs 에 'failed' 로 남는다.
 * kind: paid / shipped / bank_guide (주문 id) · biz (회원 id)
 */
class SendSmsNotice
{
    use Dispatchable;

    public function __construct(public string $kind, public int $id) {}

    public function handle(SmsNotifier $sms): void
    {
        try {
            if ($this->kind === 'biz') {
                if ($user = User::find($this->id)) {
                    $sms->bizResult($user);
                }

                return;
            }
            $order = Order::with('user')->find($this->id);
            if (! $order) {
                return;
            }
            match ($this->kind) {
                'paid'       => $sms->orderPaid($order),
                'shipped'    => $sms->orderShipped($order),
                'bank_guide' => $sms->bankGuide($order),
                default      => null,
            };
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
