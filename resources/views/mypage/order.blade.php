@extends('layouts.app')
@section('title', '주문상세 — 메디셀')

@section('content')
<div class="page-head"><div class="container"><h1>주문 상세</h1></div></div>
<div class="container" style="padding-top:26px">
    <div class="my-layout">
        @include('partials.mynav')
        <div>
            <div class="form-card">
                <h3 style="justify-content:space-between">
                    <span><x-icon name="package"/> {{ $order->order_no }}</span>
                    <span class="status-pill st-{{ $order->status }}">{{ $order->statusLabel() }}</span>
                </h3>
                <table class="dtable" style="border:0">
                    <thead><tr><th>상품</th><th style="width:90px">수량</th><th style="width:120px;text-align:right">금액</th></tr></thead>
                    <tbody>
                    @foreach($order->items as $it)
                        <tr><td>{{ $it->product_name }}</td><td>{{ $it->quantity }}{{ $it->unit }}</td><td style="text-align:right"><b>{{ number_format($it->subtotal) }}원</b></td></tr>
                    @endforeach
                    </tbody>
                </table>
                <div class="sum-row" style="border-top:1px solid var(--line);margin-top:8px;padding-top:12px"><span>상품금액</span><span>{{ number_format($order->subtotal) }}원</span></div>
                <div class="sum-row"><span>배송비</span><span>{{ $order->shipping_fee ? number_format($order->shipping_fee).'원' : '무료' }}</span></div>
                @if($order->discount)<div class="sum-row" style="color:var(--red)"><span>쿠폰 할인{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</span><span>-{{ number_format($order->discount) }}원</span></div>@endif
                @if($order->point_used)<div class="sum-row"><span>적립금 사용</span><span>-{{ number_format($order->point_used) }}원</span></div>@endif
                <div class="sum-row total"><span>결제금액</span><b>{{ number_format($order->total) }}원</b></div>
            </div>

            <div class="row2" style="align-items:start">
                <div class="form-card">
                    <h3><x-icon name="pin"/> 배송지</h3>
                    <p style="line-height:1.9;font-size:14px">
                        <b>{{ $order->receiver_name }}</b> · {{ $order->receiver_phone }}<br>
                        ({{ $order->postcode }}) {{ $order->address1 }} {{ $order->address2 }}<br>
                        @if($order->memo)<span class="muted">메모: {{ $order->memo }}</span>@endif
                    </p>
                    @if($order->tracking_no)
                        <div style="border-top:1px solid var(--line);margin-top:10px;padding-top:10px;font-size:14px">
                            <b style="color:var(--navy-800)">배송정보</b><br>
                            {{ $order->courier }} · 송장번호 <b>{{ $order->tracking_no }}</b>
                            @if($order->shipped_at)<br><span class="muted">{{ $order->shipped_at->format('Y.m.d') }} 발송</span>@endif
                        </div>
                    @endif
                </div>
                <div class="form-card">
                    <h3><x-icon name="coin"/> 결제정보</h3>
                    <p style="line-height:1.9;font-size:14px">
                        무통장입금 · {{ $order->bank }}<br>
                        입금자명 {{ $order->depositor }}<br>
                        @if($order->paid_at)<span style="color:var(--navy-800);font-weight:700">입금확인 {{ $order->paid_at->format('Y.m.d H:i') }}</span>
                        @else<span class="text-red">입금대기중</span>@endif
                    </p>
                </div>
            </div>

            <div style="display:flex;gap:10px;margin-top:6px">
                <a href="{{ route('mypage.orders') }}" class="btn btn-ghost">목록</a>
                @if(in_array($order->status, ['pending','paid']))
                    <form method="POST" action="{{ route('mypage.order.cancel', $order) }}" onsubmit="return confirm('주문을 취소하시겠습니까?')">
                        @csrf
                        <button class="btn btn-dark">주문 취소</button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
