@extends('layouts.app')

@section('content')
<div class="container" style="padding-top:24px">

    {{-- 메인 비주얼: 좌 카테고리 + 중 슬라이드 + 우 배너 (mediversal 구조) --}}
    @php($heroPics = $bestProducts->pluck('thumbnail')->filter()->values())
    <div class="home-top">
        {{-- 좌측 전체 카테고리 --}}
        <aside class="home-cats">
            <div class="hc-head"><x-icon name="grid"/> 전체 카테고리</div>
            <ul class="hc-list">
                @foreach($navCategories as $cat)
                    <li>
                        <a href="{{ route('catalog.category', $cat->slug) }}">
                            <x-icon :name="$cat->icon ?? 'box'"/><span>{{ $cat->name }}</span>
                            @if($cat->children->count())<x-icon name="chevron-right" :size="14" class="hc-arr"/>@endif
                        </a>
                        @if($cat->children->count())
                            <div class="hc-fly">
                                <strong>{{ $cat->name }}</strong>
                                @foreach($cat->children as $sub)
                                    <a href="{{ route('catalog.category', $sub->slug) }}">{{ $sub->name }}</a>
                                @endforeach
                            </div>
                        @endif
                    </li>
                @endforeach
            </ul>
            <a href="{{ route('community.inquiry', ['type' => 'quote']) }}" class="hc-cta">대량구매 견적문의 <x-icon name="arrow-right" :size="14"/></a>
        </aside>

        {{-- 중앙 히어로 슬라이드 --}}
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

        {{-- 우측 배너 (특가·신상·가입) --}}
        <div class="side-promos">
            @foreach($subBanners as $b)
                @php($pt = number_format($site['signup_point'] ?? 0))
                @php($ttl = str_replace('{point}', $pt, $b->title))
                @php($sub = str_replace('{point}', $pt, (string) $b->subtitle))
                <a href="{{ $b->link ?: '#' }}" class="promo"
                   style="{{ $b->image ? "background-image:linear-gradient(135deg,rgba(6,37,107,.74),rgba(6,37,107,.34)),url('{$b->image}');background-size:cover;background-position:center" : 'background:'.($b->bg_color ?: '#c0392b') }}">
                    @if($sub)<small>{{ $sub }}</small>@endif
                    <strong>{{ $ttl }}</strong>
                </a>
            @endforeach
        </div>
    </div>

    {{-- 혜택 아이콘 배너 5개 --}}
    <div class="benefit-strip">
        <a href="{{ route('catalog.index') }}" class="benefit">
            <span class="bi"><x-icon name="truck"/></span>
            <b>당일배송</b><em>오후 2시 이전 주문</em>
        </a>
        <a href="{{ route('community.qna') }}" class="benefit">
            <span class="bi"><x-icon name="tag"/></span>
            <b>대량구매 할인</b><em>병의원 최대 30%</em>
        </a>
        <a href="{{ route('community.qna') }}" class="benefit">
            <span class="bi"><x-icon name="headset"/></span>
            <b>전문 상담</b><em>의료소모품 전담</em>
        </a>
        <a href="{{ route('community.inquiry', ['type' => 'quote']) }}" class="benefit">
            <span class="bi"><x-icon name="doc"/></span>
            <b>견적문의</b><em>대량 납품 견적</em>
        </a>
        <a href="{{ route('community.notices') }}" class="benefit">
            <span class="bi"><x-icon name="fire"/></span>
            <b>이벤트·공지</b><em>혜택 소식 확인</em>
        </a>
    </div>

    {{-- 오늘의 특가 (타이머 + 한 줄 5개) --}}
    @if($dealProducts->count())
    <section class="section deal-section">
        <div class="section-head">
            <h3><x-icon name="fire"/> 오늘의 특가</h3>
            <div class="deal-timer" aria-label="마감까지 남은 시간">
                <x-icon name="clock" :size="16"/> <span id="dealCountdown">00:00:00</span> 남음
            </div>
        </div>
        <div class="prod-grid">
            @foreach($dealProducts->take(5) as $p)
                <x-product-card :product="$p"/>
            @endforeach
        </div>
    </section>
    @endif

    {{-- 카테고리별 베스트 (5탭 + 한 줄 4개) --}}
    @if($categoryTabs->count())
    <section class="section">
        <div class="section-head">
            <h3><x-icon name="grid"/> 카테고리별 베스트</h3>
            <a href="{{ route('catalog.index', ['sort' => 'popular']) }}" class="more">전체보기 <x-icon name="chevron-right" :size="14"/></a>
        </div>
        <div class="cat-tabs">
            @foreach($categoryTabs as $i => $t)
                <button type="button" class="cat-tab {{ $i === 0 ? 'on' : '' }}" data-idx="{{ $i }}">{{ $t['category']->name }}</button>
            @endforeach
        </div>
        @foreach($categoryTabs as $i => $t)
            <div class="cat-panel prod-grid cols4 {{ $i === 0 ? 'on' : '' }}" id="cat-panel-{{ $i }}">
                @foreach($t['products']->take(4) as $p)
                    <x-product-card :product="$p"/>
                @endforeach
            </div>
        @endforeach
    </section>
    @endif

    {{-- 추천 브랜드 --}}
    @if($brands->count())
    <section class="section">
        <div class="section-head">
            <h3><x-icon name="handshake"/> 추천 브랜드</h3>
            <a href="{{ route('catalog.index') }}" class="more">전체보기 <x-icon name="chevron-right" :size="14"/></a>
        </div>
        <div class="brand-grid">
            @foreach($brands as $br)
                <a href="{{ route('catalog.index', ['brand' => $br->id]) }}" class="brand-cell"><x-icon name="package" :size="28"/>{{ $br->name }}</a>
            @endforeach
        </div>
    </section>
    @endif

    {{-- 공지 + 고객센터 + 입금계좌 (3단) --}}
    <div class="home-foot cols3">
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
            <h4><x-icon name="headset"/> 고객센터</h4>
            <div class="cs-box">
                <strong>{{ $site['cs_tel'] }}</strong>
                <p>{{ $site['cs_hours'] }}</p>
                <p class="muted">{{ $site['email'] }}</p>
                <div class="cs-links">
                    <a href="{{ route('community.inquiry', ['type' => 'qna']) }}">1:1 문의</a>
                    <a href="{{ route('community.faq') }}">자주묻는질문</a>
                </div>
            </div>
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
