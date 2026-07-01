@extends('layouts.app')
@section('title', '주문/결제 — 메디셀')

@section('content')
<div class="page-head"><div class="container"><h1>주문 / 결제</h1></div></div>

<div class="container" style="padding-top:26px">
{{-- 쿠폰 적용/해제 폼 (메인 폼과 분리, form 속성으로 연결) --}}
<form id="couponForm" method="POST" action="{{ route('order.coupon.apply') }}">@csrf</form>
<form id="couponRemoveForm" method="POST" action="{{ route('order.coupon.remove') }}">@csrf @method('DELETE')</form>
<script>
function msApplyCoupon(code){var i=document.querySelector('input[name=code][form=couponForm]')||document.querySelector('input[name=code]');if(i)i.value=code;document.getElementById('couponForm').submit();}
</script>

<form method="POST" action="{{ route('order.store') }}">
    @csrf
    <div class="cart-layout">
        <div>
            {{-- 주문상품 --}}
            <div class="form-card">
                <h3><x-icon name="package"/> 주문상품 {{ $items->count() }}건</h3>
                <table class="dtable" style="border:0">
                    <tbody>
                    @foreach($items as $it)
                        @php($unit = $it->product->priceFor($user))
                        <tr>
                            <td>
                                <div class="pname">
                                    <span class="pthumb">@if($it->product->thumbnail)<img src="{{ $it->product->thumbnail }}" style="width:100%;height:100%;object-fit:cover;border-radius:8px" alt="">@else<x-icon :name="$it->product->category->icon ?? 'box'"/>@endif</span>
                                    <div><div style="font-weight:600">{{ $it->product->name }}</div><div class="muted" style="font-size:12px">{{ number_format($unit) }}원 × {{ $it->quantity }}{{ $it->product->unit }}</div></div>
                                </div>
                            </td>
                            <td style="text-align:right;width:120px"><b>{{ number_format($unit * $it->quantity) }}원</b></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            {{-- 배송지 --}}
            <div class="form-card">
                <h3><x-icon name="pin"/> 배송지 정보</h3>
                <div class="row2">
                    <div class="field"><label>받는분 <span class="req">*</span></label><input type="text" name="receiver_name" class="input" value="{{ old('receiver_name', $user->name) }}" required></div>
                    <div class="field"><label>연락처 <span class="req">*</span></label><input type="text" name="receiver_phone" class="input" value="{{ old('receiver_phone', $user->phone) }}" required></div>
                </div>
                <div class="field" style="max-width:200px"><label>우편번호</label><input type="text" name="postcode" class="input" value="{{ old('postcode', $user->postcode) }}"></div>
                <div class="field"><label>주소 <span class="req">*</span></label><input type="text" name="address1" class="input" value="{{ old('address1', $user->address1) }}" placeholder="기본 주소" required></div>
                <div class="field"><label>상세주소</label><input type="text" name="address2" class="input" value="{{ old('address2', $user->address2) }}" placeholder="상세 주소"></div>
                <div class="field"><label>배송 메모</label><input type="text" name="memo" class="input" value="{{ old('memo') }}" placeholder="예) 부재 시 진료실 앞에 놓아주세요"></div>
            </div>

            {{-- 결제수단 --}}
            <div class="form-card">
                <h3><x-icon name="coin"/> 결제수단</h3>
                <div class="radio-cards" data-radio-cards style="margin-bottom:16px">
                    <label class="radio-card on">
                        <input type="radio" name="payment_method" value="toss" hidden checked>
                        <strong>카드 · 가상계좌</strong><small>토스페이먼츠 (카드/계좌이체/가상계좌)</small>
                    </label>
                    <label class="radio-card">
                        <input type="radio" name="payment_method" value="bank" hidden>
                        <strong>무통장입금</strong><small>안내 계좌로 직접 입금</small>
                    </label>
                </div>

                {{-- 무통장 입력 (선택 시에만 표시) --}}
                <div id="bank-fields" style="display:none">
                    <div class="field">
                        <label>입금하실 은행</label>
                        <select name="bank" class="select">
                            @foreach($site['banks'] as $b)
                                <option value="{{ $b['bank'] }}">{{ $b['bank'] }} {{ $b['account'] }} ({{ $b['holder'] }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field"><label>입금자명</label><input type="text" name="depositor" class="input" value="{{ old('depositor', $user->name) }}"></div>
                </div>
                <p id="toss-hint" class="muted" style="font-size:13px;margin:0">다음 화면에서 토스페이먼츠 결제창을 통해 카드 또는 가상계좌로 결제합니다.</p>
            </div>
        </div>

        {{-- 결제요약 --}}
        @php($finalTotal = max(0, $summary['total'] - ($couponDiscount ?? 0)))
        <div class="sum-card">
            <h3>결제금액</h3>

            {{-- 쿠폰 --}}
            <div style="margin-bottom:12px">
                @if($coupon)
                    <div style="display:flex;align-items:center;justify-content:space-between;background:var(--navy-50);border:1px solid var(--navy-100);border-radius:8px;padding:9px 12px">
                        <span style="font-size:13px;font-weight:700;color:var(--navy-800)"><x-icon name="tag" :size="14"/> {{ $coupon->name }}</span>
                        <button type="submit" form="couponRemoveForm" class="btn btn-ghost btn-sm" style="padding:5px 10px">해제</button>
                    </div>
                @else
                    <div style="display:flex;gap:6px">
                        <input type="text" name="code" form="couponForm" class="input" placeholder="쿠폰 코드 입력" style="height:38px">
                        <button type="submit" form="couponForm" class="btn btn-primary btn-sm">적용</button>
                    </div>
                    @if($couponError)<div class="err" style="font-size:12px;color:var(--red);margin-top:5px">{{ $couponError }}</div>@endif
                    @if(isset($availableCoupons) && $availableCoupons->count())
                        <div style="margin-top:8px;display:flex;flex-wrap:wrap;gap:6px;align-items:center">
                            <span class="muted" style="font-size:12px">보유 쿠폰</span>
                            @foreach($availableCoupons as $uc)
                                <button type="button" class="chip" style="cursor:pointer;border-color:var(--navy-100)" onclick="msApplyCoupon('{{ $uc->coupon->code }}')">
                                    {{ $uc->coupon->name }} · {{ $uc->coupon->typeLabel() }}
                                </button>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>

            <div class="sum-row"><span>상품금액</span><span>{{ number_format($summary['subtotal']) }}원</span></div>
            <div class="sum-row"><span>배송비</span><span>{{ $summary['shipping'] ? '+'.number_format($summary['shipping']).'원' : '무료' }}</span></div>
            @if($couponDiscount ?? 0)
                <div class="sum-row" style="color:var(--red)"><span>쿠폰 할인</span><span>-{{ number_format($couponDiscount) }}원</span></div>
            @endif
            @if($user->point > 0)
                <div class="sum-row" style="align-items:center">
                    <span>적립금 사용</span>
                    <span class="inline"><input type="number" name="point_used" value="0" min="0" max="{{ max(0, min($user->point, $summary['subtotal'] - ($couponDiscount ?? 0))) }}" class="input" style="width:110px;height:34px;text-align:right">원</span>
                </div>
                <div class="muted" style="font-size:12px;text-align:right">보유 {{ number_format($user->point) }}원</div>
            @endif
            <div class="sum-row total"><span>최종 결제금액</span><b>{{ number_format($finalTotal) }}원</b></div>
            <button type="submit" class="btn btn-red btn-lg btn-block" style="margin-top:14px">{{ number_format($finalTotal) }}원 결제하기</button>
            <p class="muted" style="font-size:12px;margin-top:10px">결제수단에 따라 결제창 또는 입금안내로 이동합니다.</p>
        </div>
    </div>
</form>
</div>
@endsection
