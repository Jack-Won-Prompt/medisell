@extends('layouts.app')
@section('title', $title.' — 메디셀')

@section('content')
<div class="page-head">
    <div class="container">
        <h1>{{ $title }}</h1>
        <div class="breadcrumb">
            <a href="{{ route('home') }}">홈</a> <x-icon name="chevron-right"/>
            @if($category && $category->parent)
                <a href="{{ route('catalog.category', $category->parent->slug) }}">{{ $category->parent->name }}</a> <x-icon name="chevron-right"/>
            @endif
            <span>{{ $category->name ?? $title }}</span>
        </div>
    </div>
</div>

<div class="shell">
    @include('partials.sidebar')

    <div class="content">
        {{-- 필터: 브랜드 / 가격 --}}
        @if($brands->count() || $selBrands || $priceMin || $priceMax)
        <form method="GET" class="filter-bar">
            @isset($keyword)<input type="hidden" name="q" value="{{ $keyword }}">@endisset
            <input type="hidden" name="sort" value="{{ $sort }}">
            @if($brands->count())
            <div class="fb-group">
                <span class="fb-label">브랜드</span>
                <div class="fb-brands">
                    @foreach($brands as $b)
                        <label class="fb-chk {{ in_array($b->id, $selBrands) ? 'on' : '' }}">
                            <input type="checkbox" name="brand[]" value="{{ $b->id }}" {{ in_array($b->id, $selBrands) ? 'checked' : '' }}>
                            {{ $b->name }} <span class="fb-cnt">{{ $b->products_count }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
            @endif
            <div class="fb-group">
                <span class="fb-label">가격</span>
                <input type="number" name="price_min" value="{{ $priceMin }}" placeholder="최소" class="fb-price">
                <span>~</span>
                <input type="number" name="price_max" value="{{ $priceMax }}" placeholder="최대" class="fb-price">
                <span style="font-size:13px;color:var(--slate-500)">원</span>
                <button class="btn btn-primary btn-sm" type="submit">적용</button>
                @if($selBrands || $priceMin || $priceMax)
                    <a href="{{ url()->current() }}{{ isset($keyword) ? '?q='.urlencode($keyword) : '' }}" class="btn btn-ghost btn-sm">초기화</a>
                @endif
            </div>
        </form>
        @endif

        <div class="list-toolbar">
            <div class="cnt">총 <b>{{ number_format($products->total()) }}</b>개 상품</div>
            <div class="sort-tabs">
                @foreach(['new' => '신상품순', 'popular' => '인기순', 'price_low' => '낮은가격', 'price_high' => '높은가격', 'name' => '이름순'] as $key => $label)
                    <a href="{{ request()->fullUrlWithQuery(['sort' => $key, 'page' => null]) }}" class="{{ $sort === $key ? 'on' : '' }}">{{ $label }}</a>
                @endforeach
            </div>
        </div>

        @if($products->count())
            <div class="prod-grid cols4">
                @foreach($products as $p)
                    <x-product-card :product="$p"/>
                @endforeach
            </div>
            <div style="margin-top:30px">{{ $products->links('pagination.simple') }}</div>
        @else
            <div class="empty">
                <x-icon name="search"/>
                <h3>상품이 없습니다</h3>
                <p>다른 카테고리나 검색어로 찾아보세요.</p>
            </div>
        @endif
    </div>
</div>
@endsection
