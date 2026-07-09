<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="pusher-key" content="{{ config('broadcasting.connections.pusher.key') }}">
    <meta name="pusher-cluster" content="{{ config('broadcasting.connections.pusher.options.cluster') }}">
    <title>@yield('title', '관리자') — 메디셀 관리자</title>
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css">
    <link rel="icon" href="{{ asset('images/logo-mark.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v=10">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v=5">
</head>
<body>
    @include('partials.icons')
    @php($resources = config('admin'))
    @php($groups = collect($resources)->groupBy('group', preserveKeys: true))
    @php($cur = request()->route('resource'))
    <div class="adm">
        <aside class="adm-side">
            <a href="{{ route('admin.dashboard') }}" class="adm-brand">
                <img src="{{ asset('images/logo-mark.svg') }}" alt="" class="mark" style="width:32px;height:32px;background:#fff;border-radius:7px;padding:2px">
                <span><strong>메디셀</strong><span>ADMIN</span></span>
            </a>
            <nav class="adm-nav">
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'on' : '' }}"><x-icon name="chart"/> 대시보드</a>
                <a href="{{ route('admin.reports.sales') }}" class="{{ request()->routeIs('admin.reports.*') ? 'on' : '' }}"><x-icon name="chart"/> 매출 리포트</a>

                <div class="grp">주문/회원</div>
                <a href="{{ route('admin.orders.index') }}" class="{{ request()->routeIs('admin.orders.*') ? 'on' : '' }}"><x-icon name="cart"/> 주문관리</a>
                <a href="{{ route('admin.bank.index') }}" class="{{ request()->routeIs('admin.bank.*') ? 'on' : '' }}"><x-icon name="coin"/> 입금확인</a>
                <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'on' : '' }}"><x-icon name="user"/> 회원관리</a>
                <a href="{{ route('admin.chat.index') }}" class="{{ request()->routeIs('admin.chat.*') ? 'on' : '' }}"><x-icon name="headset"/> 실시간 상담</a>
                <a href="{{ route('admin.inquiries.index') }}" class="{{ request()->routeIs('admin.inquiries.*') ? 'on' : '' }}"><x-icon name="question"/> 문의관리</a>
                <a href="{{ route('admin.reviews.index') }}" class="{{ request()->routeIs('admin.reviews.*') ? 'on' : '' }}"><x-icon name="star"/> 후기관리</a>
                <a href="{{ route('admin.coupang.index') }}" class="{{ request()->routeIs('admin.coupang.*') ? 'on' : '' }}"><x-icon name="tag"/> 쿠팡 경쟁가</a>

                @foreach($groups as $gname => $items)
                    <div class="grp">{{ $gname }}</div>
                    @foreach($items as $key => $r)
                        <a href="{{ route('admin.index', $key) }}" class="{{ $cur === $key ? 'on' : '' }}"><x-icon :name="$r['icon']"/> {{ $r['label'] }}</a>
                    @endforeach
                @endforeach

                <div class="grp">환경설정</div>
                <a href="{{ route('admin.settings.edit') }}" class="{{ request()->routeIs('admin.settings.*') ? 'on' : '' }}"><x-icon name="tools"/> 사이트 설정</a>
            </nav>
            <div class="adm-foot">
                <a href="{{ route('home') }}" target="_blank"><x-icon name="arrow-right"/> 쇼핑몰 보기</a>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button type="submit"><x-icon name="logout"/> 로그아웃</button>
                </form>
            </div>
        </aside>

        <div class="adm-main">
            <header class="adm-top">
                <h1>@yield('heading', '관리자')</h1>
                <div class="who"><x-icon name="user" :size="16"/> {{ auth()->user()->name }} 님</div>
            </header>
            <div class="adm-body">
                @if(session('ok'))<div class="flash"><x-icon name="check"/> {{ session('ok') }}</div>@endif
                @if($errors->any())<div class="flash" style="background:#fee2e2;border-color:#fecaca;color:#991b1b"><x-icon name="close"/> {{ $errors->first() }}</div>@endif
                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
