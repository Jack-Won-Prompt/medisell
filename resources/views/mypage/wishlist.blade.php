@extends('layouts.app')
@section('title', '관심상품 — 메디셀')

@section('content')
<div class="page-head"><div class="container"><h1>관심상품</h1></div></div>
<div class="container" style="padding-top:26px">
    <div class="my-layout">
        @include('partials.mynav')
        <div>
            @if($items->count())
                <div class="prod-grid cols4">
                    @foreach($items as $w)
                        <x-product-card :product="$w->product"/>
                    @endforeach
                </div>
            @else
                <div class="empty">
                    <x-icon name="heart"/>
                    <h3>관심상품이 없습니다</h3>
                    <p>상품의 하트(♥)를 눌러 관심상품으로 담아보세요.</p>
                    <a href="{{ route('catalog.index') }}" class="btn btn-primary" style="margin-top:16px">상품 보러가기</a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
