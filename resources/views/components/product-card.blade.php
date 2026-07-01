@props(['product'])
@php
    $user = auth()->user();
    $sell = $product->priceFor($user);
    $isHospital = $user && $user->isApprovedBusiness();
    $special = $isHospital && $sell < $product->price;     // 병원 전용가(정가보다 낮음)
    $rate = $special ? $product->discountRateFor($sell) : $product->discountRate();
    $soldout = $product->stock <= 0;
    $inWish = in_array($product->id, $wishlistIds ?? []);
@endphp
<div class="card">
    @auth
        <form method="POST" action="{{ route('wishlist.toggle', $product) }}" class="wish-form">
            @csrf
            <button type="submit" class="wish {{ $inWish ? 'on' : '' }}" aria-label="관심상품"><x-icon name="heart"/></button>
        </form>
    @else
        <a href="{{ route('login') }}" class="wish-form wish" aria-label="관심상품(로그인)"><x-icon name="heart"/></a>
    @endauth
    <a href="{{ route('catalog.show', $product->slug) }}" class="thumb">
        <div class="badges">
            @if($special)<span class="badge badge-plan">병원가</span>@endif
            @if($product->is_best)<span class="badge badge-best">BEST</span>@endif
            @if($product->is_new)<span class="badge badge-new">NEW</span>@endif
            @if($product->badge)<span class="badge badge-plan">{{ $product->badge }}</span>@endif
            @if($soldout)<span class="badge badge-soldout">품절</span>@endif
        </div>
        @if($product->thumbnail)
            <img src="{{ $product->thumbnail }}" alt="{{ $product->name }}" loading="lazy">
        @else
            <span class="ph"><x-icon :name="$product->category->icon ?? 'box'"/></span>
        @endif
    </a>
    <div class="info">
        <div class="maker">{{ $product->maker ?? $product->brand?->name }}</div>
        <a href="{{ route('catalog.show', $product->slug) }}" class="name">{{ $product->name }}</a>
        <div class="price-row">
            @if($special)
                <span class="rate">{{ $rate }}%</span>
                <span class="price">{{ number_format($sell) }}<span class="won">원</span></span>
                <span class="o-price">{{ number_format($product->price) }}원</span>
            @else
                <span class="price">{{ number_format($sell) }}<span class="won">원</span></span>
            @endif
        </div>
        @if($special)
            <div><span class="mprice">병원 전용가 적용중</span></div>
        @elseif(!$isHospital && ($product->member_price || true))
            <div><span class="mprice">병원 회원 전용가 별도</span></div>
        @endif
    </div>
    <div class="cart-row">
        @if($soldout)
            <button class="btn btn-ghost btn-sm btn-block" disabled>품절</button>
        @else
            <form method="POST" action="{{ route('cart.add', $product) }}" style="flex:1">
                @csrf
                <button class="btn btn-primary btn-sm btn-block" type="submit"><x-icon name="cart"/>담기</button>
            </form>
        @endif
    </div>
</div>
