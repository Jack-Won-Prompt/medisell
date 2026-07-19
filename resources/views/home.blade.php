@extends('layouts.app')

@section('content')
<div class="container" style="padding-top:24px">

    {{-- 1. 풀와이드 메인 배너 (슬라이드) --}}
    @php($heroPics = $bestProducts->pluck('thumbnail')->filter()->values())
    <div class="home-top">
        <div class="hero">
            @foreach($mainBanners as $i => $b)
                <div class="slide {{ $i === 0 ? 'on' : '' }}"
                     style="{{ $b->image ? "background-image:linear-gradient(100deg,rgba(247,249,253,.78),rgba(247,249,253,.2)),url('{$b->image}');background-size:cover;background-position:center" : '' }}">
                    <div class="slide-text">
                        <small>MEDISELL</small>
                        <h2>{{ $b->title }}</h2>
                        @if($b->subtitle)<p>{{ $b->subtitle }}</p>@endif
                        <a href="{{ $b->link ?: route('catalog.index') }}" class="btn" style="background:var(--navy-800);color:#fff">상품 보러가기 <x-icon name="arrow-right"/></a>
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
    </div>

    {{-- 2. 탭 바 (슬라이드 하단) --}}
    <nav class="home-tabbar">
        <a href="{{ route('home') }}" class="on">메디셀</a>
        <a href="{{ route('guide.event') }}">신규회원 이벤트</a>
        <a href="{{ route('guide.delivery') }}">2시 이전 주문 당일출고</a>
        <a href="{{ route('guide.payment') }}">간편결제</a>
    </nav>

    {{-- 3. 3단 정보: 고객센터 | 배너 | 달력 --}}
    <div class="info-3col">
        {{-- 좌: 고객센터 --}}
        <div class="ic-cs">
            <span class="cs-label">고객 만족 센터</span>
            <a href="tel:{{ $site['cs_tel'] }}" class="cs-tel"><x-icon name="headset"/> {{ $site['cs_tel'] }}</a>
            <p class="cs-hours">{{ $site['cs_hours'] }}</p>
            <div class="cs-icons">
                <a href="{{ route('community.inquiry', ['type' => 'qna']) }}"><x-icon name="headset"/><span>1:1 문의</span></a>
                <a href="{{ route('community.faq') }}"><x-icon name="question"/><span>자주묻는질문</span></a>
                <a href="{{ route('community.notices') }}"><x-icon name="doc"/><span>공지사항</span></a>
            </div>
        </div>
        {{-- 중: 배너 2개 --}}
        <div class="ic-banners">
            <a href="{{ route('register') }}" class="ic-banner ic-banner-dark">
                <div><strong>사업자 회원이신가요?</strong><span>승인 후 사업자 전용가로 구매하세요</span></div>
                <x-icon name="building" :size="40"/>
            </a>
            <a href="{{ route('community.inquiry', ['type' => 'quote']) }}" class="ic-banner ic-banner-soft">
                <div><strong>장바구니에서 빠른 견적 받으세요</strong><span>담아둔 상품으로 대량 견적 요청</span></div>
                <x-icon name="cart" :size="40"/>
            </a>
        </div>
        {{-- 우: 달력 --}}
        @php($today = now())
        <div class="ic-cal">
            <div class="cal-head">{{ $today->year }}년 {{ $today->month }}월</div>
            <div class="cal-grid">
                @foreach(['일','월','화','수','목','금','토'] as $dow)<span class="cal-dow">{{ $dow }}</span>@endforeach
                @for($i = 0; $i < $today->copy()->startOfMonth()->dayOfWeek; $i++)<span></span>@endfor
                @for($d = 1; $d <= $today->daysInMonth; $d++)
                    <span class="{{ $d === $today->day ? 'cal-today' : '' }}">{{ $d }}</span>
                @endfor
            </div>
            <p class="cal-note">영업일 기준 1~3일 내 출고</p>
        </div>
    </div>

    {{-- 4. 오늘의 특가 (타이머 + 가로 슬라이더) --}}
    @if($dealProducts->count())
    <section class="section deal-section">
        <div class="section-head">
            <h3><x-icon name="fire"/> 오늘의 특가</h3>
            <div class="deal-timer" aria-label="마감까지 남은 시간">
                <x-icon name="clock" :size="16"/> <span id="dealCountdown">00:00:00</span> 남음
            </div>
        </div>
        <div class="deal-slider">
            @foreach($dealProducts as $p)
                <div class="deal-item"><x-product-card :product="$p"/></div>
            @endforeach
        </div>
    </section>
    @endif

    {{-- 5. 대형 프로모 배너 (풀와이드) --}}
    @php($wide = $bestProducts->first())
    @if($wide)
    <a href="{{ route('catalog.show', $wide->slug) }}" class="wide-promo">
        <div class="wp-text">
            <small>BEST ITEM</small>
            <h3>병의원이 신뢰하는 의료소모품</h3>
            <p>메디셀 인기 상품을 사업자 전용가로 만나보세요</p>
            <span class="wp-btn">구매하러 가기 <x-icon name="arrow-right" :size="15"/></span>
        </div>
        @if($wide->thumbnail)<img src="{{ $wide->thumbnail }}" alt="" class="wp-img" loading="lazy">@endif
    </a>
    @endif

    {{-- 6. 카테고리별 베스트 (탭 + 그리드) --}}
    @if($categoryTabs->count())
    <section class="section">
        <div class="section-head">
            <h3><x-icon name="grid"/> 카테고리별 베스트</h3>
        </div>
        <div class="cat-tabs">
            @foreach($categoryTabs as $i => $t)
                <button type="button" class="cat-tab {{ $i === 0 ? 'on' : '' }}" data-idx="{{ $i }}">{{ $t['category']->name }}</button>
            @endforeach
        </div>
        @foreach($categoryTabs as $i => $t)
            <div class="cat-panel {{ $i === 0 ? 'on' : '' }}" id="cat-panel-{{ $i }}">
                <div class="prod-grid">
                    @foreach($t['products']->take(10) as $p)
                        <x-product-card :product="$p"/>
                    @endforeach
                </div>
                <div style="text-align:center;margin-top:20px">
                    <a href="{{ route('catalog.category', $t['category']->slug) }}" class="btn btn-ghost btn-lg">{{ $t['category']->name }} 전체 보기 <x-icon name="arrow-right" :size="16"/></a>
                </div>
            </div>
        @endforeach
    </section>
    @endif

    {{-- 7. 추천 브랜드 --}}
    @if($brands->count())
    <section class="section">
        <div class="section-head">
            <h3><x-icon name="handshake"/> 추천 브랜드</h3>
            <a href="{{ route('catalog.index') }}" class="more">전체보기 <x-icon name="chevron-right" :size="14"/></a>
        </div>
        <div class="brand-grid brand-grid-card">
            @foreach($brands->take(10) as $br)
                <a href="{{ route('catalog.index', ['brand' => $br->id]) }}" class="brand-cell">
                    <span class="br-star"><x-icon name="star" :size="13"/></span>
                    <x-icon name="package" :size="30"/>
                    <b>{{ $br->name }}</b>
                    <em>의료소모품 브랜드</em>
                </a>
            @endforeach
        </div>
        <div style="text-align:center;margin-top:20px">
            <a href="{{ route('catalog.index') }}" class="btn btn-primary">모든 브랜드 보기 <x-icon name="arrow-right" :size="15"/></a>
        </div>
    </section>
    @endif

</div>
@endsection

@push('scripts')
<script>
(function () {
    // 오늘의 특가 카운트다운 (매일 자정 리셋)
    var cd = document.getElementById('dealCountdown');
    if (cd) {
        var pad = function (n) { return String(n).padStart(2, '0'); };
        var tick = function () {
            var now = new Date();
            var end = new Date(now); end.setHours(24, 0, 0, 0);
            var s = Math.max(0, Math.floor((end - now) / 1000));
            cd.textContent = pad(Math.floor(s / 3600)) + ':' + pad(Math.floor(s % 3600 / 60)) + ':' + pad(s % 60);
        };
        tick(); setInterval(tick, 1000);
    }

    // 카테고리별 베스트 탭 전환
    document.querySelectorAll('.cat-tab').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.cat-tab').forEach(function (b) { b.classList.remove('on'); });
            document.querySelectorAll('.cat-panel').forEach(function (p) { p.classList.remove('on'); });
            btn.classList.add('on');
            var panel = document.getElementById('cat-panel-' + btn.dataset.idx);
            if (panel) panel.classList.add('on');
        });
    });
})();
</script>
@endpush
