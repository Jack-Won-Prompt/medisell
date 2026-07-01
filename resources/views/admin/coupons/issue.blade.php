@extends('layouts.admin')
@section('title', '쿠폰 발행')
@section('heading', '쿠폰 발행 · '.$coupon->name)

@section('content')
<div style="display:grid;grid-template-columns:1fr 1.2fr;gap:20px;align-items:start">
    {{-- 발행 --}}
    <div class="adm-card">
        <div class="h">
            <span>{{ $coupon->code }} <span class="pill pill-b">{{ $coupon->typeLabel() }}</span></span>
            @if($coupon->is_public)<span class="pill pill-y">공개형</span>@else<span class="pill pill-w">발행형</span>@endif
        </div>
        <div style="padding:20px">
            <div style="font-size:13.5px;color:#6b7794;line-height:1.8;margin-bottom:16px">
                {{ $coupon->name }} · 최소주문 {{ number_format($coupon->min_order_amount) }}원
                @if($coupon->ends_at) · ~{{ $coupon->ends_at->format('Y.m.d') }}까지 @endif<br>
                @if($coupon->is_public)공개형이라 코드 입력으로 누구나 사용 가능하지만, 특정 회원에게도 발행해 마이페이지에 노출할 수 있습니다.
                @else발행형이라 <b>발행받은 회원만</b> 사용할 수 있습니다.@endif
            </div>

            <form method="POST" action="{{ route('admin.coupons.issue.store', $coupon) }}" data-radio-cards>
                @csrf
                <div class="afield">
                    <label>발행 대상</label>
                    <select name="target" class="aselect" id="targetSel">
                        <option value="all">전체 회원</option>
                        <option value="business">병원 회원</option>
                        <option value="general">일반 회원</option>
                        <option value="emails">특정 회원(이메일 지정)</option>
                    </select>
                </div>
                <div class="afield" id="emailsBox" style="display:none">
                    <label>회원 이메일 (쉼표 또는 줄바꿈 구분)</label>
                    <textarea name="emails" class="atextarea" rows="4" placeholder="user@test.com, clinic@test.com"></textarea>
                </div>
                <button class="abtn abtn-pri" style="width:100%;justify-content:center">발행하기</button>
                <div class="ahint" style="margin-top:8px">이미 발행받은 회원은 중복 발행되지 않습니다.</div>
            </form>
        </div>
    </div>

    {{-- 발행 현황 --}}
    <div class="adm-card">
        <div class="h">발행 현황 <span class="pill pill-b">{{ $issued->count() }}명</span></div>
        <table class="atable">
            <thead><tr><th>회원</th><th>이메일</th><th>상태</th><th></th></tr></thead>
            <tbody>
            @forelse($issued as $uc)
                <tr>
                    <td>{{ $uc->user->name }}</td>
                    <td>{{ $uc->user->email }}</td>
                    <td>@if($uc->used_at)<span class="pill pill-n">사용({{ $uc->used_at->format('m/d') }})</span>@else<span class="pill pill-y">미사용</span>@endif</td>
                    <td style="text-align:right">
                        @unless($uc->used_at)
                            <form method="POST" action="{{ route('admin.coupons.revoke', [$coupon, $uc]) }}" onsubmit="return confirm('발행을 회수하시겠습니까?')">
                                @csrf @method('DELETE')
                                <button class="abtn abtn-red abtn-sm">회수</button>
                            </form>
                        @endunless
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center;color:#97a0b8;padding:30px">아직 발행 내역이 없습니다.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div style="margin-top:16px"><a href="{{ route('admin.index', 'coupons') }}" class="abtn abtn-ghost">쿠폰 목록으로</a></div>

<script>
document.getElementById('targetSel').addEventListener('change', function () {
    document.getElementById('emailsBox').style.display = this.value === 'emails' ? '' : 'none';
});
</script>
@endsection
