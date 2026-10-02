<div style="font-family:'Malgun Gothic',sans-serif;font-size:14px;color:#1f2937;line-height:1.7">
    <p>결제가 확인된 주문입니다. 주문서 PDF를 첨부합니다.</p>
    <table style="border-collapse:collapse;font-size:13px">
        <tr><td style="padding:3px 12px 3px 0;color:#6b7280">주문번호</td><td><b>{{ $order->order_no }}</b></td></tr>
        <tr><td style="padding:3px 12px 3px 0;color:#6b7280">주문자</td><td>{{ $order->buyer_hospital ?: ($order->user?->company_name ?: '') }} {{ $order->buyer_name ?: ($order->user?->name ?? '비회원') }}</td></tr>
        <tr><td style="padding:3px 12px 3px 0;color:#6b7280">결제</td><td>{{ $payLabel }} · {{ $order->paid_at?->format('Y-m-d H:i') }}</td></tr>
        <tr><td style="padding:3px 12px 3px 0;color:#6b7280">상품</td><td>{{ $order->items->first()?->product_name }}@if($order->items->count() > 1) 외 {{ $order->items->count() - 1 }}건@endif</td></tr>
        <tr><td style="padding:3px 12px 3px 0;color:#6b7280">결제금액</td><td><b>{{ number_format($order->total) }}원</b></td></tr>
        <tr><td style="padding:3px 12px 3px 0;color:#6b7280">배송지</td><td>{{ $order->receiver_name }} · {{ $order->address1 }} {{ $order->address2 }}</td></tr>
    </table>
    <p style="color:#9ca3af;font-size:12px;margin-top:18px">이 메일은 메디셀에서 결제 확인 시 자동 발송됩니다.</p>
</div>
