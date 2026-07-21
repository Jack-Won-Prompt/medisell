@extends('layouts.app')
@section('title', '비밀번호 찾기 — 메디셀')

@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <a href="{{ route('home') }}" class="brand" style="justify-content:center"><img src="{{ asset('images/logo.svg') }}" alt="메디셀" class="brand-logo" style="height:46px"></a>
        <h2>비밀번호 찾기</h2>
        <p class="sub">가입하신 이메일로 재설정 링크를 보내드립니다.</p>

        @if(session('ok'))<div class="alert alert-ok" style="margin-bottom:16px">{{ session('ok') }}</div>@endif
        @error('email')<div class="alert alert-red" style="margin-bottom:16px">{{ $message }}</div>@enderror

        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <div class="field">
                <label>이메일</label>
                <input type="email" name="email" class="input" value="{{ old('email') }}" required autofocus placeholder="가입한 이메일 주소">
            </div>
            <button class="btn btn-primary btn-lg btn-block">재설정 링크 받기</button>
        </form>

        <div class="auth-links">
            <a href="{{ route('login') }}">로그인으로 돌아가기</a>
            <span>·</span>
            <a href="{{ route('register') }}">회원가입</a>
        </div>
    </div>
</div>
@endsection
