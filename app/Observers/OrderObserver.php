<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\FcmService;

class OrderObserver
{
    public function __construct(private FcmService $fcm) {}

    /** 주문 상태가 바뀌면 구매 회원에게 푸시 */
    public function updated(Order $order): void
    {
        if (! $order->wasChanged('status') || ! $order->user_id) {
            return;
        }

        $label = $order->statusLabel();
        $messages = [
            'paid'      => '입금이 확인되었습니다. 곧 상품을 준비할게요.',
            'preparing' => '상품을 준비 중입니다.',
            'shipped'   => '상품이 발송되었습니다.'.($order->tracking_no ? " (송장 {$order->tracking_no})" : ''),
            'done'      => '배송이 완료되었습니다. 이용해 주셔서 감사합니다.',
            'cancelled' => '주문이 취소되었습니다.',
        ];
        $body = $messages[$order->status] ?? "주문 상태가 '{$label}'(으)로 변경되었습니다.";

        $this->fcm->sendToUser($order->user_id, "주문 {$order->order_no} · {$label}", $body, [
            'type'     => 'order',
            'order_id' => $order->id,
        ]);
    }
}
