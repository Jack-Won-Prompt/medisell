{{-- 상단 유틸 바 --}}
<div class="top-bar">
    <div class="container">
        <div class="top-left">고객센터 {{ $site['cs_tel'] }} · {{ $site['cs_hours'] }}</div>
        <div class="top-links">
            @auth
                <span>{{ auth()->user()->name }}님</span>
                <span class="sep">|</span>
                <a href="{{ route('mypage.index') }}">마이페이지</a>
                <span class="sep">|</span>
                <a href="{{ route('mypage.orders') }}">주문조회</a>
                @if(auth()->user()->is_admin)
                    <span class="sep">|</span><a href="{{ route('admin.dashboard') }}">관리자</a>
                @endif
                <span class="sep">|</span>
                <form method="POST" action="{{ route('logout') }}" style="display:inline">@csrf
                    <button type="submit" style="background:none;border:0;color:inherit;cursor:pointer;font:inherit;padding:0">로그아웃</button>
                </form>
            @else
                <a href="{{ route('login') }}">로그인</a>
                <span class="sep">|</span>
                <a href="{{ route('register') }}">회원가입</a>
                <span class="sep">|</span>
                <a href="{{ route('community.qna') }}">고객센터</a>
            @endauth
        </div>
    </div>
</div>

{{-- 메인 헤더 (로고 + 검색 + 유틸 아이콘) --}}
<header class="site-header">
    <div class="container header-main">
        <a href="{{ route('home') }}" class="brand" aria-label="메디셀 홈">
            <img src="{{ asset('images/logo.svg') }}" alt="메디셀 MEDISELL" class="brand-logo">
        </a>

        <div class="search-box">
            <form method="GET" action="{{ route('catalog.search') }}">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="찾으시는 의료소모품을 검색하세요" aria-label="상품 검색">
                <button type="submit" aria-label="검색"><x-icon name="search"/></button>
            </form>
            <div class="popular">
                <strong style="color:var(--slate-600)">인기검색어</strong>
                @foreach($site['popular_keywords'] as $kw)
                    <a href="{{ route('catalog.search', ['q' => $kw]) }}">{{ $kw }}</a>
                @endforeach
            </div>
        </div>

        <div class="header-utils">
            @auth
                <a href="{{ route('cart.index') }}" class="util">
                    <x-icon name="cart"/><span>장바구니</span>
                    @if($cartCount > 0)<span class="count">{{ $cartCount }}</span>@endif
                </a>
                <a href="{{ route('mypage.wishlist') }}" class="util">
                    <x-icon name="heart"/><span>관심상품</span>
                    @if(count($wishlistIds ?? []) > 0)<span class="count">{{ count($wishlistIds) }}</span>@endif
                </a>
                <a href="{{ route('mypage.orders') }}" class="util"><x-icon name="package"/><span>주문조회</span></a>
                <a href="{{ route('mypage.index') }}" class="util"><x-icon name="user"/><span>마이페이지</span></a>
            @else
                <a href="{{ route('cart.index') }}" class="util"><x-icon name="cart"/><span>장바구니</span></a>
                <a href="{{ route('login') }}" class="util"><x-icon name="user"/><span>로그인</span></a>
            @endauth
            <button class="nav-toggle" aria-label="메뉴"><x-icon name="menu" :size="28"/></button>
        </div>
    </div>

    {{-- 카테고리 내비 바 --}}
    <div class="cat-bar">
        <div class="container">
            <div class="all-cat"><x-icon name="grid"/> 전체 카테고리</div>
            <nav aria-label="카테고리">
                <ul class="gnb">
                    @foreach($navCategories->take(7) as $cat)
                        <li><a href="{{ route('catalog.category', $cat->slug) }}">{{ $cat->name }}</a></li>
                    @endforeach
                    <li><a href="{{ route('community.qna') }}" class="acc">견적문의</a></li>
                </ul>
            </nav>
        </div>
        {{-- 메가패널 --}}
        <div class="megapanel">
            <div class="inner">
                @foreach($navCategories as $cat)
                    <div class="mega-col">
                        <h4><x-icon :name="$cat->icon ?? 'box'"/><a href="{{ route('catalog.category', $cat->slug) }}">{{ $cat->name }}</a></h4>
                        @foreach($cat->children as $sub)
                            <a href="{{ route('catalog.category', $sub->slug) }}">{{ $sub->name }}</a>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</header>

{{-- 모바일 드로어 --}}
<div class="drawer" id="drawer">
    <div class="scrim"></div>
    <div class="panel">
        <div class="d-head">
            <a href="{{ route('home') }}" class="brand"><img src="{{ asset('images/logo-mark.svg') }}" alt="" class="mark" style="width:34px;height:34px"><span class="bt"><strong>메디셀</strong></span></a>
            <button class="d-close" aria-label="닫기" style="background:none;border:0;cursor:pointer"><x-icon name="close" :size="26"/></button>
        </div>
        @auth
            <div style="display:flex;gap:8px;margin-bottom:12px">
                <a href="{{ route('mypage.index') }}" class="btn btn-ghost btn-sm" style="flex:1">마이페이지</a>
                <a href="{{ route('cart.index') }}" class="btn btn-primary btn-sm" style="flex:1">장바구니 {{ $cartCount }}</a>
            </div>
        @else
            <div style="display:flex;gap:8px;margin-bottom:12px">
                <a href="{{ route('login') }}" class="btn btn-ghost btn-sm" style="flex:1">로그인</a>
                <a href="{{ route('register') }}" class="btn btn-primary btn-sm" style="flex:1">회원가입</a>
            </div>
        @endauth
        @foreach($navCategories as $cat)
            <details>
                <summary>{{ $cat->name }} <x-icon name="chevron-down" :size="18"/></summary>
                <div class="d-sub">
                    <a href="{{ route('catalog.category', $cat->slug) }}"><b>{{ $cat->name }} 전체</b></a>
                    @foreach($cat->children as $sub)
                        <a href="{{ route('catalog.category', $sub->slug) }}">{{ $sub->name }}</a>
                    @endforeach
                </div>
            </details>
        @endforeach
        <details>
            <summary><a href="{{ route('community.qna') }}">고객지원 / 견적문의</a></summary>
        </details>
    </div>
</div>
