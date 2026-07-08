<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="pusher-key" content="{{ config('broadcasting.connections.pusher.key') }}">
    <meta name="pusher-cluster" content="{{ config('broadcasting.connections.pusher.options.cluster') }}">
    <title>@yield('title', $site['name'].' — '.$site['tagline'])</title>
    <meta name="description" content="@yield('desc', '의료소모품 전문 쇼핑몰 메디셀 — 거즈·주사기·수액·소독·글러브 등 병의원 의료소모품을 합리적인 가격에 공급합니다.')">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css">
    <link rel="icon" href="{{ asset('images/logo-mark.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v=7">
    @stack('head')
</head>
<body>
    @include('partials.icons')
    @include('partials.header')

    <main>
        @if(session('ok') || session('error') || $errors->any())
            <div class="container" style="padding-top:18px">
                @if(session('ok'))<div class="flash"><x-icon name="check"/>{{ session('ok') }}</div>@endif
                @if(session('error'))<div class="flash err"><x-icon name="close"/>{{ session('error') }}</div>@endif
                @if($errors->any())<div class="flash err"><x-icon name="close"/>{{ $errors->first() }}</div>@endif
            </div>
        @endif
        @yield('content')
    </main>

    @include('partials.footer')

    @include('partials.recent')
    @include('partials.chat')
    <button class="to-top" aria-label="맨 위로"><x-icon name="arrow-right"/></button>
    <script src="{{ asset('js/site.js') }}?v=5" defer></script>
    @stack('scripts')
</body>
</html>
