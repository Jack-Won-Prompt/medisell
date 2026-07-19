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

            {{-- 구매 대행: 대신 구매할 구매자(병원) 선택 --}}
            @if($user->isAgent())
                <div class="form-card">
                    <h3><x-icon name="user"/> 대행 구매자 선택</h3>
                    @if($agentBuyers->count())
                        <div class="field"><label>어느 병원을 대신해 구매하나요?</label>
                            <select name="agent_buyer_id" class="input">
                                <option value="">— 대행 없음 (본인 구매) —</option>
                                @foreach($agentBuyers as $b)
                                    <option value="{{ $b->id }}" {{ (string) old('agent_buyer_id') === (string) $b->id ? 'selected' : '' }}>
                                        {{ $b->hospital_name }} · {{ $b->buyer_name }} ({{ $b->buyer_phone }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="ahint" style="margin-top:4px">
                                선택 시 주문 총액의 <b>{{ rtrim(rtrim(number_format($user->cashback_rate, 2), '0'), '.') }}%</b>가 캐쉬백으로 적립됩니다.
                                <a href="{{ route('mypage.agent.buyers') }}" style="color:var(--navy-700)">구매자 관리 →</a>
                            </div>
                        </div>
                    @else
                        <p class="muted" style="margin:0">등록된 구매자가 없습니다. <a href="{{ route('mypage.agent.buyers') }}" style="color:var(--navy-700)">구매자 등록 →</a></p>
                    @endif
                </div>
            @endif

            {{-- 배송지 --}}
            <div class="form-card">
                <h3><x-icon name="pin"/> 배송지 정보</h3>
                @php($defaultAddr = $addresses->firstWhere('is_default', true) ?? $addresses->first())
                @if($addresses->count())
                    <div class="field"><label>저장된 배송지</label>
                        <select id="addrSelect" class="input" onchange="pickAddr(this)">
                            @foreach($addresses as $a)
                                <option value="{{ $a->id }}"
                                    data-name="{{ $a->receiver_name }}" data-phone="{{ $a->receiver_phone }}"
                                    data-post="{{ $a->postcode }}" data-a1="{{ $a->address1 }}" data-a2="{{ $a->address2 }}"
                                    {{ optional($defaultAddr)->id === $a->id ? 'selected' : '' }}>
                                    {{ $a->label ?: '배송지' }} — {{ $a->receiver_name }} ({{ $a->address1 }})
                                </option>
                            @endforeach
                            <option value="">+ 새 주소 직접 입력</option>
                        </select>
                        <div class="ahint" style="margin-top:4px"><a href="{{ route('mypage.addresses') }}" style="color:var(--navy-700)">배송지 관리 →</a></div>
                    </div>
                @endif
                <div class="row2">
                    <div class="field"><label>받는분 <span class="req">*</span></label><input type="text" name="receiver_name" class="input" value="{{ old('receiver_name', optional($defaultAddr)->receiver_name ?? $user->name) }}" required></div>
                    <div class="field"><label>연락처 <span class="req">*</span></label><input type="text" name="receiver_phone" class="input" value="{{ old('receiver_phone', optional($defaultAddr)->receiver_phone ?? $user->phone) }}" required></div>
                </div>
                <div class="field" style="max-width:360px"><label>우편번호</label>
                    <div style="display:flex;gap:8px">
                        <input type="text" name="postcode" id="postcode" class="input" value="{{ old('postcode', optional($defaultAddr)->postcode ?? $user->postcode) }}" readonly placeholder="주소 찾기 클릭">
                        <button type="button" class="btn btn-ghost" onclick="findAddr()" style="flex:none;white-space:nowrap">주소 찾기</button>
                    </div>
                </div>
                <div class="field"><label>주소 <span class="req">*</span></label><input type="text" name="address1" id="address1" class="input" value="{{ old('address1', optional($defaultAddr)->address1 ?? $user->address1) }}" placeholder="주소 찾기로 입력" required readonly></div>
                <div class="field"><label>상세주소</label><input type="text" name="address2" id="address2" class="input" value="{{ old('address2', optional($defaultAddr)->address2 ?? $user->address2) }}" placeholder="상세 주소 (동/호수 등)"></div>
                <label class="acheck" style="display:flex;align-items:center;gap:6px;margin-top:2px"><input type="checkbox" name="save_address" value="1"> 이 배송지를 주소록에 저장</label>
                <div class="field"><label>배송 메모</label><input type="text" name="memo" class="input" value="{{ old('memo') }}" placeholder="예) 부재 시 진료실 앞에 놓아주세요"></div>
            </div>

            {{-- 결제수단 --}}
            <div class="form-card">
                <h3><x-icon name="coin"/> 결제수단</h3>
                @php($pg = $site['payment_pg'] ?? 'toss')
                @php($pgName = $pg === 'portone' ? '포트원(아임포트)' : '토스페이먼츠')
                <div class="radio-cards" data-radio-cards style="margin-bottom:16px">
                    <label class="radio-card on">
                        <input type="radio" name="payment_method" value="{{ $pg }}" hidden checked>
                        <strong>카드 · 가상계좌</strong><small>{{ $pgName }} (카드/계좌이체/가상계좌)</small>
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
                        <span style="font-size:13px;font-weight:600;color:var(--navy-800)"><x-icon name="tag" :size="14"/> {{ $coupon->name }}</span>
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

@push('scripts')
<script src="https://t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<script>
function findAddr() {
    new daum.Postcode({
        oncomplete: function (d) {
            document.getElementById('postcode').value = d.zonecode;
            document.getElementById('address1').value = d.roadAddress || d.jibunAddress;
            document.getElementById('address2').focus();
        }
    }).open();
}
function pickAddr(sel) {
    var o = sel.options[sel.selectedIndex];
    document.querySelector('[name=receiver_name]').value = o.dataset.name || '';
    document.querySelector('[name=receiver_phone]').value = o.dataset.phone || '';
    document.getElementById('postcode').value = o.dataset.post || '';
    document.getElementById('address1').value = o.dataset.a1 || '';
    document.getElementById('address2').value = o.dataset.a2 || '';
}
</script>
@endpush
