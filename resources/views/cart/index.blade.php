@extends('layouts.app')
@section('title', '장바구니 — 메디셀')

@section('content')
<div class="page-head"><div class="container"><h1>장바구니</h1></div></div>

<div class="container" style="padding-top:26px">
@if($items->count())
    <div class="cart-layout">
        <div>
            <table class="dtable">
                <thead><tr><th>상품정보</th><th style="width:120px">판매가</th><th style="width:130px">수량</th><th style="width:120px">합계</th><th style="width:60px"></th></tr></thead>
                <tbody>
                @foreach($items as $it)
                    @php($unit = $it->product->priceFor(auth()->user()))
                    <tr>
                        <td>
                            <div class="pname">
                                <a href="{{ route('catalog.show', $it->product->slug) }}" class="pthumb">
                                    @if($it->product->thumbnail)<img src="{{ $it->product->thumbnail }}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:8px">@else<x-icon :name="$it->product->category->icon ?? 'box'"/>@endif
                                </a>
                                <div>
                                    <div class="muted" style="font-size:12px">{{ $it->product->maker }}</div>
                                    <a href="{{ route('catalog.show', $it->product->slug) }}" style="font-weight:600">{{ $it->product->name }}</a>
                                    <div class="muted" style="font-size:12px">단위 {{ $it->product->unit }}</div>
                                </div>
                            </div>
                        </td>
                        <td>{{ number_format($unit) }}원</td>
                        <td>
                            <form method="POST" action="{{ route('cart.update', $it) }}" class="inline">
                                @csrf @method('PUT')
                                <div class="qty">
                                    <input type="number" name="quantity" value="{{ $it->quantity }}" min="1" style="width:50px;height:36px" onchange="this.form.submit()">
                                </div>
                            </form>
                        </td>
                        <td><b style="color:var(--navy-800)">{{ number_format($unit * $it->quantity) }}원</b></td>
                        <td>
                            <form method="POST" action="{{ route('cart.remove', $it) }}">
                                @csrf @method('DELETE')
                                <button class="btn btn-ghost btn-sm" style="padding:7px"><x-icon name="trash" :size="16"/></button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            <div style="margin-top:14px"><a href="{{ route('catalog.index') }}" class="btn btn-ghost">＋ 쇼핑 계속하기</a></div>
        </div>

        <div class="sum-card">
            <h3>결제 예상금액</h3>
            <div class="sum-row"><span>상품금액</span><span>{{ number_format($summary['subtotal']) }}원</span></div>
            <div class="sum-row"><span>배송비</span><span>{{ $summary['shipping'] ? '+'.number_format($summary['shipping']).'원' : '무료' }}</span></div>
            <div class="sum-row total"><span>결제예정금액</span><b>{{ number_format($summary['total']) }}원</b></div>
            @if($summary['shipping'] > 0)
                <p class="muted" style="font-size:12.5px;margin:10px 0">{{ number_format($site['free_ship_over'] - $summary['subtotal']) }}원 추가 구매 시 무료배송</p>
            @endif
            <a href="{{ route('order.checkout') }}" class="btn btn-red btn-lg btn-block" style="margin-top:14px">주문하기</a>
        </div>
    </div>
@else
    <div class="empty">
        <x-icon name="cart"/>
        <h3>장바구니가 비어 있습니다</h3>
        <p>필요한 의료소모품을 담아보세요.</p>
        <a href="{{ route('catalog.index') }}" class="btn btn-primary" style="margin-top:18px">상품 보러가기</a>
    </div>
@endif
</div>
@endsection
