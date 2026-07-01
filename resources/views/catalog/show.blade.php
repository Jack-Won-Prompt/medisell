@extends('layouts.app')
@section('title', $product->name.' — 메디셀')

@php
    $user = auth()->user();
    $sell = $product->priceFor($user);
    $isHospital = $user && $user->isApprovedBusiness();
    $special = $isHospital && $sell < $product->price;     // 병원 전용가 적용
    $rate = $special ? $product->discountRateFor($sell) : 0;
    $soldout = $product->stock <= 0;
@endphp

@section('content')
<div class="page-head">
    <div class="container">
        <div class="breadcrumb" style="margin-top:0">
            <a href="{{ route('home') }}">홈</a> <x-icon name="chevron-right"/>
            <a href="{{ route('catalog.category', $product->category->slug) }}">{{ $product->category->name }}</a> <x-icon name="chevron-right"/>
            <span>{{ $product->name }}</span>
        </div>
    </div>
</div>

<div class="container" style="padding-top:30px">
    <div class="detail">
        @php
            $gallery = collect([$product->thumbnail])->merge($product->images ?? [])->filter()->unique()->values();
        @endphp
        <div class="gallery">
            <div class="main-img">
                @if($gallery->isNotEmpty())
                    <img id="mainImg" src="{{ $gallery->first() }}" alt="{{ $product->name }}">
                @else
                    <x-icon :name="$product->category->icon ?? 'box'"/>
                @endif
            </div>
            @if($gallery->count() > 1)
                <div class="thumbs">
                    @foreach($gallery as $g)
                        <button type="button" class="thumb-btn {{ $loop->first ? 'on' : '' }}" onclick="document.getElementById('mainImg').src='{{ $g }}';document.querySelectorAll('.thumb-btn').forEach(b=>b.classList.remove('on'));this.classList.add('on')">
                            <img src="{{ $g }}" alt="">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="summary">
            <div style="display:flex;gap:6px;margin-bottom:10px">
                @if($product->is_best)<span class="badge badge-best">BEST</span>@endif
                @if($product->is_new)<span class="badge badge-new">NEW</span>@endif
                @if($product->badge)<span class="badge badge-plan">{{ $product->badge }}</span>@endif
            </div>
            <h1>{{ $product->name }}</h1>
            <div class="maker">제조사 {{ $product->maker ?? '-' }} · 상품코드 {{ $product->code ?? '-' }} · 판매단위 {{ $product->unit }}</div>

            <div class="price-panel">
                @if($special)
                    <div class="row"><span class="lbl">정가</span><span class="o-price" style="text-decoration:line-through;color:var(--slate-400)">{{ number_format($product->price) }}원</span></div>
                @endif
                <div class="row" style="align-items:flex-end">
                    <span class="lbl">{{ $special ? '병원 전용가' : '판매가' }}</span>
                    <span class="big-price">{{ number_format($sell) }}<span class="won">원</span></span>
                </div>
                @if($special)
                    <div style="text-align:right;margin-top:6px"><span class="mtag"><x-icon name="check" :size="14"/> {{ $user->company_name ?? '병원' }} 전용가 적용중 ({{ $rate }}%↓)</span></div>
                @elseif($isHospital)
                    <div style="font-size:12.5px;color:var(--slate-500);margin-top:4px">※ 이 제품은 병원 전용가가 설정되어 있지 않아 정가로 판매됩니다.</div>
                @else
                    <div style="font-size:12.5px;color:var(--slate-500);margin-top:4px">※ 병원 회원으로 로그인하면 병원별 전용가가 적용됩니다.</div>
                @endif
                <div class="row"><span class="lbl">배송비</span><span>{{ $sell >= $site['free_ship_over'] ? '무료배송' : number_format($site['shipping_fee']).'원 (5만원 이상 무료)' }}</span></div>
                <div class="row"><span class="lbl">재고</span><span>{{ $soldout ? '품절' : number_format($product->stock).$product->unit }}</span></div>
            </div>

            @auth
                @if($soldout)
                    <button class="btn btn-dark btn-lg btn-block" disabled>품절된 상품입니다</button>
                @else
                <form method="POST" action="{{ route('cart.add', $product) }}">
                    @csrf
                    <div style="display:flex;align-items:center;gap:14px">
                        <span style="font-weight:700;font-size:14px">수량</span>
                        <div class="qty">
                            <button type="button" data-dec><x-icon name="minus" :size="16"/></button>
                            <input type="number" name="quantity" value="1" min="1" max="{{ $product->stock }}">
                            <button type="button" data-inc><x-icon name="plus" :size="16"/></button>
                        </div>
                    </div>
                    <div class="buy-actions">
                        <button type="submit" class="btn btn-ghost btn-lg"><x-icon name="cart"/> 장바구니</button>
                        <button type="submit" name="buy_now" value="1" class="btn btn-red btn-lg">바로 구매</button>
                    </div>
                </form>
                @php($inWish = in_array($product->id, $wishlistIds ?? []))
                <form method="POST" action="{{ route('wishlist.toggle', $product) }}" style="margin-top:10px">
                    @csrf
                    <button class="btn btn-ghost btn-block {{ $inWish ? 'wish-active' : '' }}">
                        <x-icon name="heart"/> {{ $inWish ? '관심상품에 담김' : '관심상품 담기' }}
                    </button>
                </form>
                @endif
            @else
                <div class="buy-actions">
                    <a href="{{ route('login') }}" class="btn btn-primary btn-lg btn-block">로그인 후 구매하기</a>
                </div>
                <p class="muted" style="font-size:13px;margin-top:10px;text-align:center">회원 로그인 후 장바구니·구매가 가능합니다.</p>
            @endauth
        </div>
    </div>

    {{-- 상세/스펙/후기 --}}
    <div class="tabs">
        <a href="#desc" class="on">상세정보</a>
        <a href="#review">상품후기 ({{ $product->reviews->count() }})</a>
    </div>

    <div id="desc" class="prose" style="margin-bottom:40px">
        {!! $product->description ?: '<p>등록된 상세설명이 없습니다.</p>' !!}
        @if($product->spec)
            <h3 style="margin:24px 0 12px;font-size:18px;font-weight:800;color:var(--ink)">규격 / 사양</h3>
            <pre style="white-space:pre-wrap;font-family:inherit;background:var(--slate-50);border:1px solid var(--line);border-radius:10px;padding:16px">{{ $product->spec }}</pre>
        @endif
    </div>

    {{-- 후기 --}}
    <div id="review" class="section">
        <div class="section-head"><h3><x-icon name="star"/> 상품후기</h3></div>
        @auth
        <form method="POST" action="{{ route('catalog.review', $product) }}" class="form-card" style="margin-bottom:20px">
            @csrf
            <div class="row2">
                <div class="field">
                    <label>평점</label>
                    <select name="rating" class="select">
                        @for($i=5;$i>=1;$i--)<option value="{{ $i }}">{{ $i }}점</option>@endfor
                    </select>
                </div>
                <div class="field">
                    <label>제목 <span class="req">*</span></label>
                    <input type="text" name="title" class="input" required maxlength="100">
                </div>
            </div>
            <div class="field">
                <label>내용 <span class="req">*</span></label>
                <textarea name="body" class="textarea" required maxlength="2000"></textarea>
            </div>
            <button class="btn btn-primary">후기 등록</button>
        </form>
        @endauth

        @forelse($product->reviews as $rv)
            <div class="review">
                <div class="top">
                    <span class="stars">@for($i=0;$i<5;$i++)<x-icon :name="$i < $rv->rating ? 'star' : 'star-o'"/>@endfor</span>
                    <span class="who">{{ $rv->author_name ?? '구매자' }}</span>
                    <span class="date">{{ $rv->created_at->format('Y.m.d') }}</span>
                </div>
                <strong style="display:block;margin-bottom:4px">{{ $rv->title }}</strong>
                <p class="muted" style="font-size:14px">{{ $rv->body }}</p>
            </div>
        @empty
            <p class="muted">아직 등록된 후기가 없습니다.</p>
        @endforelse
    </div>

    {{-- 관련 상품 --}}
    @if($related->count())
    <section class="section">
        <div class="section-head"><h3>같은 카테고리 상품</h3></div>
        <div class="prod-grid cols4">
            @foreach($related as $p)<x-product-card :product="$p"/>@endforeach
        </div>
    </section>
    @endif
</div>
@endsection
