<?php

namespace App\Jobs;

use App\Mail\OrderPaidMail;
use App\Models\Order;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;

/**
 * 결제 완료 주문서 PDF 메일 발송.
 * Order::markPaid() 에서 dispatchAfterResponse 로 부른다 — PDF 생성·SMTP 가 느리거나 실패해도
 * 결제 응답(고객 화면)을 막지 않는다. 실패는 report() 로 남기고 결제 처리에는 영향을 주지 않는다.
 */
class SendOrderPdfMail
{
    use Dispatchable;

    public function __construct(public int $orderId, public ?array $to = null) {}

    public function handle(): void
    {
        $to = array_values(array_filter($this->to ?? (array) config('site.order_mail_to', [])));
        $order = Order::with(['items.product', 'user'])->find($this->orderId);
        if (! $order || ! $to) {
            return;
        }

        try {
            Mail::to($to)->send(new OrderPaidMail($order));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
