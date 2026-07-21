@extends('layouts.app')
@section('title', '비밀번호 재설정 — 메디셀')

@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <a href="{{ route('home') }}" class="brand" style="justify-content:center"><img src="{{ asset('images/logo.svg') }}" alt="메디셀" class="brand-logo" style="height:46px"></a>
        <h2>비밀번호 재설정</h2>
        <p class="sub">새로 사용할 비밀번호를 입력해 주세요.</p>

        @error('email')<div class="alert alert-red" style="margin-bottom:16px">{{ $message }}</div>@enderror
        @error('password')<div class="alert alert-red" style="margin-bottom:16px">{{ $message }}</div>@enderror

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="field">
                <label>이메일</label>
                <input type="email" name="email" class="input" value="{{ old('email', $email) }}" required readonly>
            </div>
            <div class="field">
                <label>새 비밀번호</label>
                <input type="password" name="password" class="input" required autofocus placeholder="8자 이상">
            </div>
            <div class="field">
                <label>새 비밀번호 확인</label>
                <input type="password" name="password_confirmation" class="input" required placeholder="한 번 더 입력">
            </div>
            <button class="btn btn-primary btn-lg btn-block">비밀번호 변경</button>
        </form>

        <div class="auth-links">
            <a href="{{ route('login') }}">로그인으로 돌아가기</a>
        </div>
    </div>
</div>
@endsection
