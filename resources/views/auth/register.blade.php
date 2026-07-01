@extends('layouts.app')
@section('title', '회원가입 — 메디셀')

@section('content')
<div class="auth-wrap" style="max-width:560px">
    <div class="auth-card">
        <a href="{{ route('home') }}" class="brand" style="justify-content:center"><img src="{{ asset('images/logo.svg') }}" alt="메디셀" class="brand-logo" style="height:46px"></a>
        <h2>회원가입</h2>
        <p class="sub">가입 즉시 {{ number_format($site['signup_point']) }}원 적립금 지급</p>

        <form method="POST" action="{{ route('register.attempt') }}">
            @csrf
            <div class="field" data-radio-cards>
                <label>회원 구분</label>
                <div class="radio-cards">
                    <label class="radio-card {{ old('member_type','general')==='general' ? 'on' : '' }}">
                        <input type="radio" name="member_type" value="general" hidden {{ old('member_type','general')==='general' ? 'checked' : '' }}>
                        <strong>일반 회원</strong><small>개인 구매자 · 정가 구매</small>
                    </label>
                    <label class="radio-card {{ old('member_type')==='business' ? 'on' : '' }}">
                        <input type="radio" name="member_type" value="business" hidden {{ old('member_type')==='business' ? 'checked' : '' }}>
                        <strong>병원 회원</strong><small>승인 후 병원 전용가</small>
                    </label>
                </div>
            </div>

            <div class="row2">
                <div class="field"><label>이름 <span class="req">*</span></label><input type="text" name="name" class="input" value="{{ old('name') }}" required></div>
                <div class="field"><label>연락처</label><input type="text" name="phone" class="input" value="{{ old('phone') }}"></div>
            </div>
            <div class="field"><label>이메일 <span class="req">*</span></label><input type="email" name="email" class="input" value="{{ old('email') }}" required></div>
            <div class="row2">
                <div class="field"><label>비밀번호 <span class="req">*</span></label><input type="password" name="password" class="input" required></div>
                <div class="field"><label>비밀번호 확인 <span class="req">*</span></label><input type="password" name="password_confirmation" class="input" required></div>
            </div>

            {{-- 사업자 전용 --}}
            <div id="biz-fields" style="display:none;background:var(--navy-50);border:1px solid var(--navy-100);border-radius:10px;padding:16px;margin-bottom:16px">
                <div style="font-size:13px;font-weight:800;color:var(--navy-800);margin-bottom:12px">병원 정보 (승인용)</div>
                <div class="field"><label>상호(병의원명)</label><input type="text" name="company_name" class="input" value="{{ old('company_name') }}"></div>
                <div class="row2">
                    <div class="field"><label>사업자등록번호</label><input type="text" name="biz_no" class="input" value="{{ old('biz_no') }}" placeholder="000-00-00000"></div>
                    <div class="field"><label>업태/종별</label><input type="text" name="biz_type" class="input" value="{{ old('biz_type') }}" placeholder="예: 의원"></div>
                </div>
                <p class="muted" style="font-size:12px;margin:0">※ 관리자 확인 후 승인되며, 승인 시 병원 전용가가 적용됩니다.</p>
            </div>

            <label class="inline" style="font-size:13px;margin-bottom:16px"><input type="checkbox" name="agree" value="1" {{ old('agree') ? 'checked' : '' }}> 이용약관 및 개인정보처리방침에 동의합니다.</label>
            <button class="btn btn-primary btn-lg btn-block">가입하기</button>
        </form>
        <div class="auth-links"><span>이미 회원이신가요?</span><a href="{{ route('login') }}">로그인</a></div>
    </div>
</div>
@endsection
