@extends('layouts.app')
@section('title', '상품후기 — 메디셀')

@section('content')
<div class="page-head"><div class="container"><h1>상품후기</h1></div></div>
<div class="container" style="padding:26px 20px;max-width:920px">
    @forelse($reviews as $rv)
        <div class="review">
            <div class="top">
                <span class="stars">@for($i=0;$i<5;$i++)<x-icon :name="$i < $rv->rating ? 'star' : 'star-o'"/>@endfor</span>
                <span class="who">{{ $rv->author_name ?? '구매자' }}</span>
                <span class="date">{{ $rv->created_at->format('Y.m.d') }}</span>
            </div>
            <strong style="display:block;margin-bottom:4px">{{ $rv->title }}</strong>
            <p class="muted" style="font-size:14px;margin-bottom:8px">{{ $rv->body }}</p>
            @if($rv->product)
                <a href="{{ route('catalog.show', $rv->product->slug) }}" class="chip"><x-icon name="package" :size="14"/> {{ $rv->product->name }}</a>
            @endif
        </div>
    @empty
        <p class="muted">등록된 후기가 없습니다.</p>
    @endforelse
    <div style="margin-top:24px">{{ $reviews->links('pagination.simple') }}</div>
</div>
@endsection
