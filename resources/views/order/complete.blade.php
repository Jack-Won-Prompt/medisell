@extends('layouts.app')
@section('title', '주문완료 — 메디셀')

@section('content')
<div class="container" style="padding:40px 20px;max-width:760px">
    @php($isPaid = $order->status === 'paid')
    <div style="text-align:center;margin-bottom:30px">
        <div style="width:72px;height:72px;border-radius:50%;background:var(--navy-50);color:var(--navy-800);display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
            <x-icon name="check" :size="40"/>
        </div>
        <h1 style="font-size:24px;font-weight:900">{{ $isPaid ? '결제가 완료되었습니다' : '주문이 정상 접수되었습니다' }}</h1>
        <p class="muted" style="margin-top:8px">주문번호 <b style="color:var(--navy-800)">{{ $order->order_no }}</b></p>
    </div>

    @if($isPaid)
        {{-- 카드/계좌이체 등 결제완료 --}}
        <div class="form-card" style="background:#d1fae5;border-color:#a7f3d0">
            <h3 style="border:0;margin:0 0 6px"><x-icon name="check"/> 결제 완료</h3>
            <p style="font-size:15px"><b class="text-red" style="font-size:18px">{{ number_format($order->total) }}원</b> 결제가 정상 처리되었습니다.</p>
            <div class="muted" style="font-size:13px;margin-top:6px">결제수단 {{ $order->pay_method ?? '토스페이먼츠' }} · 결제일시 {{ optional($order->paid_at)->format('Y.m.d H:i') }}</div>
        </div>
    @elseif($order->va_account)
        {{-- 토스 가상계좌 입금대기 --}}
        <div class="form-card" style="background:var(--navy-50);border-color:var(--navy-100)">
            <h3 style="border:0;margin:0 0 10px"><x-icon name="coin"/> 가상계좌 입금 안내</h3>
            <p style="font-size:15px">아래 가상계좌로 <b class="text-red" style="font-size:18px">{{ number_format($order->total) }}원</b>을 입금해 주세요.</p>
            <div style="margin-top:12px;background:#fff;border:1px solid var(--line);border-radius:10px;padding:16px">
                <div style="font-size:17px;font-weight:800;color:var(--navy-800)">{{ $order->va_bank }} {{ $order->va_account }}</div>
                <div class="muted" style="font-size:13px;margin-top:4px">예금주 {{ $order->va_holder }}@if($order->va_due_at) · 입금기한 {{ $order->va_due_at->format('Y.m.d H:i') }}@endif</div>
            </div>
            <p class="muted" style="font-size:12.5px;margin-top:10px">※ 입금이 확인되면 자동으로 결제완료 처리됩니다.</p>
        </div>
    @else
        {{-- 무통장입금 --}}
        <div class="form-card" style="background:var(--navy-50);border-color:var(--navy-100)">
            <h3 style="border:0;margin:0 0 10px"><x-icon name="coin"/> 입금 안내</h3>
            <p style="font-size:15px">아래 계좌로 <b class="text-red" style="font-size:18px">{{ number_format($order->total) }}원</b>을 입금해 주세요.</p>
            <div style="margin-top:12px;background:#fff;border:1px solid var(--line);border-radius:10px;padding:16px">
                @php($bankInfo = collect($site['banks'])->firstWhere('bank', $order->bank) ?? $site['banks'][0])
                <div style="font-size:17px;font-weight:800;color:var(--navy-800)">{{ $bankInfo['bank'] }} {{ $bankInfo['account'] }}</div>
                <div class="muted" style="font-size:13px;margin-top:4px">예금주 {{ $bankInfo['holder'] }} · 입금자명 {{ $order->depositor }}</div>
            </div>
            <p class="muted" style="font-size:12.5px;margin-top:10px">※ 입금 확인 후 상품이 준비·배송됩니다. 입금자명이 다를 경우 1:1 문의로 알려주세요.</p>
        </div>
    @endif

    <div class="form-card">
        <h3><x-icon name="package"/> 주문 상품</h3>
        <table class="dtable" style="border:0">
            <tbody>
            @foreach($order->items as $it)
                <tr><td>{{ $it->product_name }} <span class="muted">× {{ $it->quantity }}</span></td><td style="text-align:right"><b>{{ number_format($it->subtotal) }}원</b></td></tr>
            @endforeach
            </tbody>
        </table>
        <div class="sum-row" style="border-top:1px solid var(--line);margin-top:10px;padding-top:12px"><span>상품금액</span><span>{{ number_format($order->subtotal) }}원</span></div>
        <div class="sum-row"><span>배송비</span><span>{{ $order->shipping_fee ? number_format($order->shipping_fee).'원' : '무료' }}</span></div>
        @if($order->discount)<div class="sum-row" style="color:var(--red)"><span>쿠폰 할인{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</span><span>-{{ number_format($order->discount) }}원</span></div>@endif
        @if($order->point_used)<div class="sum-row"><span>적립금 사용</span><span>-{{ number_format($order->point_used) }}원</span></div>@endif
        <div class="sum-row total"><span>결제금액</span><b>{{ number_format($order->total) }}원</b></div>
    </div>

    <div style="display:flex;gap:10px;justify-content:center;margin-top:24px">
        <a href="{{ route('mypage.order', $order) }}" class="btn btn-ghost btn-lg">주문 상세보기</a>
        <a href="{{ route('home') }}" class="btn btn-primary btn-lg">쇼핑 계속하기</a>
    </div>
</div>
@endsection
