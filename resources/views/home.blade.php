@extends('layouts.app')

@section('content')
<div class="container" style="padding-top:24px">

    {{-- 히어로 + 사이드 프로모 --}}
    @php($heroPics = $bestProducts->pluck('thumbnail')->filter()->values())
    <div class="home-top">
        <div class="hero">
            @foreach($mainBanners as $i => $b)
                <div class="slide {{ $i === 0 ? 'on' : '' }}"
                     style="{{ $b->image ? "background-image:linear-gradient(100deg,rgba(6,37,107,.82),rgba(6,37,107,.3)),url('{$b->image}');background-size:cover;background-position:center" : 'background:'.($b->bg_color ?? '#0b3d91') }}">
                    <div class="slide-text">
                        <small>MEDISELL</small>
                        <h2>{{ $b->title }}</h2>
                        @if($b->subtitle)<p>{{ $b->subtitle }}</p>@endif
                        <a href="{{ $b->link ?: route('catalog.index') }}" class="btn btn-white" style="background:#fff;color:var(--navy-800)">상품 보러가기 <x-icon name="arrow-right"/></a>
                    </div>
                    @if(! $b->image && $heroPics->count())
                        <div class="slide-visual" aria-hidden="true">
                            @foreach([0,1,2] as $k)
                                @php($src = $heroPics[($i * 3 + $k) % $heroPics->count()] ?? null)
                                @if($src)<div class="hv hv{{ $k + 1 }}"><img src="{{ $src }}" alt="" loading="lazy"></div>@endif
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
            @if($mainBanners->count() > 1)
                <div class="hero-dots">
                    @foreach($mainBanners as $i => $b)<button class="{{ $i===0?'on':'' }}" aria-label="배너 {{ $i+1 }}"></button>@endforeach
                </div>
            @endif
        </div>
        <div class="side-promos">
            @foreach($subBanners as $b)
                <a href="{{ $b->link ?: '#' }}" class="promo" style="background:{{ $b->bg_color ?? '#c0392b' }}">
                    <small>{{ $b->subtitle }}</small>
                    <strong>{{ $b->title }}</strong>
                </a>
            @endforeach
            <div class="promo" style="background:linear-gradient(135deg,#0f8a8a,#0b3d91)">
                <small>신규회원 혜택</small>
                <strong>가입 즉시 {{ number_format($site['signup_point']) }}원 적립</strong>
            </div>
        </div>
    </div>

    {{-- 빠른 카테고리 --}}
    <div class="quick-cats">
        @foreach($navCategories as $cat)
            <a href="{{ route('catalog.category', $cat->slug) }}" class="quick-cat">
                <span class="ic"><x-icon :name="$cat->icon ?? 'box'"/></span>
                <span>{{ $cat->name }}</span>
            </a>
        @endforeach
    </div>

    {{-- 베스트 상품 --}}
    @if($bestProducts->count())
    <section class="section">
        <div class="section-head">
            <h3><x-icon name="fire"/> 베스트 상품</h3>
            <a href="{{ route('catalog.index', ['sort' => 'popular']) }}" class="more">더보기 <x-icon name="chevron-right" :size="14"/></a>
        </div>
        <div class="prod-grid">
            @foreach($bestProducts->take(5) as $p)
                <x-product-card :product="$p"/>
            @endforeach
        </div>
    </section>
    @endif

    {{-- 추천 상품 --}}
    @if($featuredProducts->count())
    <section class="section">
        <div class="section-head">
            <h3><x-icon name="star"/> 추천 상품</h3>
            <a href="{{ route('catalog.index') }}" class="more">더보기 <x-icon name="chevron-right" :size="14"/></a>
        </div>
        <div class="prod-grid cols4">
            @foreach($featuredProducts->take(4) as $p)
                <x-product-card :product="$p"/>
            @endforeach
        </div>
    </section>
    @endif

    {{-- 신상품 --}}
    @if($newProducts->count())
    <section class="section">
        <div class="section-head">
            <h3><x-icon name="tag"/> 신상품</h3>
            <a href="{{ route('catalog.index', ['sort' => 'new']) }}" class="more">더보기 <x-icon name="chevron-right" :size="14"/></a>
        </div>
        <div class="prod-grid cols4">
            @foreach($newProducts->take(4) as $p)
                <x-product-card :product="$p"/>
            @endforeach
        </div>
    </section>
    @endif

    {{-- 브랜드 --}}
    @if($brands->count())
    <section class="section">
        <div class="section-head"><h3><x-icon name="handshake"/> 총판·대리점 브랜드</h3></div>
        <div class="brand-grid">
            @foreach($brands as $br)
                <div class="brand-cell"><x-icon name="package" :size="28"/>{{ $br->name }}</div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- 공지 + 입금계좌 --}}
    <div class="home-foot">
        <div class="box">
            <h4><x-icon name="doc"/> 공지사항</h4>
            <ul class="notice-list">
                @forelse($notices as $n)
                    <li>
                        @if($n->is_pinned)<span class="pin">[공지]</span>@endif
                        <a href="{{ route('community.notice', $n) }}">{{ $n->title }}</a>
                        <span class="date">{{ optional($n->published_at)->format('Y.m.d') }}</span>
                    </li>
                @empty
                    <li><span class="muted">등록된 공지가 없습니다.</span></li>
                @endforelse
            </ul>
        </div>
        <div class="box">
            <h4><x-icon name="coin"/> 무통장 입금계좌</h4>
            <ul class="bank-list">
                @foreach($site['banks'] as $b)
                    <li><span class="bk">{{ $b['bank'] }}</span><span class="ac">{{ $b['account'] }}</span><span class="hd">{{ $b['holder'] }}</span></li>
                @endforeach
            </ul>
        </div>
    </div>

</div>
@endsection
