@extends('layouts.app')
@section('title', '로그인 — 메디셀')

@section('content')
<div class="auth-wrap">
    <div class="auth-card">
        <a href="{{ route('home') }}" class="brand" style="justify-content:center"><img src="{{ asset('images/logo.svg') }}" alt="메디셀" class="brand-logo" style="height:46px"></a>
        <h2>로그인</h2>
        <p class="sub">의료소모품 전문 쇼핑몰 메디셀</p>

        <form method="POST" action="{{ route('login.attempt') }}">
            @csrf
            <div class="field">
                <label>이메일</label>
                <input type="email" name="email" class="input" value="{{ old('email') }}" required autofocus>
            </div>
            <div class="field">
                <label>비밀번호</label>
                <input type="password" name="password" class="input" required>
            </div>
            <label class="inline" style="font-size:13px;margin-bottom:16px"><input type="checkbox" name="remember"> 로그인 상태 유지</label>
            <button class="btn btn-primary btn-lg btn-block">로그인</button>
        </form>

        @php($demo = config('site.demo_login'))
        @if (($demo['enabled'] ?? false) && filled($demo['email'] ?? null) && filled($demo['password'] ?? null))
            <div class="demo-login">
                <div class="t">테스트 계정 안내</div>
                <dl>
                    <dt>이메일</dt><dd>{{ $demo['email'] }}</dd>
                    <dt>비밀번호</dt><dd>{{ $demo['password'] }}</dd>
                </dl>
                <button type="button" class="btn btn-ghost btn-block" id="demoFill">이 계정으로 입력</button>
                @if (filled($demo['note'] ?? null))
                    <p class="n">{{ $demo['note'] }}</p>
                @endif
            </div>
            @push('scripts')
            <script>
            document.getElementById('demoFill')?.addEventListener('click', function () {
                var f = this.closest('.auth-card').querySelector('form');
                f.querySelector('[name=email]').value    = @json($demo['email']);
                f.querySelector('[name=password]').value = @json($demo['password']);
                f.querySelector('[name=password]').focus();
            });
            </script>
            @endpush
        @endif

        <div class="auth-links">
            <a href="{{ route('register') }}">회원가입</a>
            <span>·</span>
            <a href="{{ route('password.request') }}">비밀번호 찾기</a>
            <span>·</span>
            <a href="{{ route('community.qna') }}">고객센터 문의</a>
        </div>
    </div>
</div>
@endsection
