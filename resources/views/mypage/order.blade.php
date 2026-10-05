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
                        {{ \App\Support\OrderPdf::payLabel($order) }}@if($order->payment_method==='bank' && $order->bank) · {{ $order->bank }}@endif<br>
                        @if($order->paid_at)<span style="color:var(--navy-800);font-weight:600">{{ $order->payment_method==='bank' ? '입금확인' : '결제완료' }} {{ $order->paid_at->format('Y.m.d H:i') }}</span>
                        @else<span class="text-red">{{ $order->payment_method==='bank' ? '입금대기중' : '결제대기중' }}</span>@endif
                    </p>
                </div>
            </div>

            {{-- 증빙 발행 내역 (세금계산서·현금영수증) --}}
            @php($evidences = $order->evidenceList())
            @php($evidenceNotice = $order->evidenceNotice())
            @if($evidences || $evidenceNotice)
                <div class="form-card" style="margin-top:4px">
                    <h3><x-icon name="doc"/> 증빙 서류</h3>
                    @foreach($evidences as $e)
                        <div style="display:flex;justify-content:space-between;align-items:center;gap:10px;padding:10px 0;border-top:1px solid var(--line, #eef1f6);font-size:14px">
                            <div style="line-height:1.7">
                                <b>{{ $e['label'] }}</b>
                                <span class="badge" style="margin-left:4px;{{ $e['status']==='cancelled' ? 'background:#eef1f6;color:#6b7280' : 'background:var(--navy-50);color:var(--navy-800)' }}">{{ $e['status_label'] }}</span><br>
                                <span class="muted" style="font-size:12.5px">
                                    {{ number_format($e['amount']) }}원
                                    @if($e['confirm_num']) · 승인번호 {{ $e['confirm_num'] }}@endif
                                    @if($e['issued_at']) · 발행 {{ $e['issued_at']->format('Y.m.d') }}@endif
                                    @if($e['cancelled_at']) · 취소 {{ $e['cancelled_at']->format('Y.m.d') }}@endif
                                </span>
                            </div>
                            @if($e['viewable'])
                                <a href="{{ route('mypage.order.evidence', [$order, $e['type'], $e['id']]) }}" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">보기·인쇄</a>
                            @endif
                        </div>
                    @endforeach
                    @if($evidenceNotice)<p class="muted" style="font-size:13px;margin:8px 0 0">{{ $evidenceNotice }}</p>@endif
                </div>
            @endif

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
