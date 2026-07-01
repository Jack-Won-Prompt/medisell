@extends('layouts.admin')
@section('title', '후기관리')
@section('heading', '후기관리')

@section('content')
<div class="toolbar">
    <div class="filter-tabs">
        <a href="{{ route('admin.reviews.index') }}" class="{{ !request('filter') ? 'on' : '' }}">전체</a>
        <a href="{{ route('admin.reviews.index', ['filter'=>'visible']) }}" class="{{ request('filter')==='visible' ? 'on' : '' }}">노출중</a>
        <a href="{{ route('admin.reviews.index', ['filter'=>'hidden']) }}" class="{{ request('filter')==='hidden' ? 'on' : '' }}">숨김</a>
    </div>
    <div class="spacer"></div>
    <form method="GET" class="search-mini">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="제목/내용/작성자">
        <button><x-icon name="search" :size="16"/></button>
    </form>
</div>

<div class="adm-card">
    <table class="atable">
        <thead><tr><th style="width:70px">평점</th><th>제목/내용</th><th style="width:130px">상품</th><th style="width:90px">작성자</th><th style="width:90px">작성일</th><th style="width:80px">상태</th><th style="width:140px">관리</th></tr></thead>
        <tbody>
        @forelse($reviews as $rv)
            <tr style="{{ $rv->is_hidden ? 'opacity:.55' : '' }}">
                <td><span style="color:#f59e0b;font-weight:800">★ {{ $rv->rating }}</span></td>
                <td>
                    <div style="font-weight:700">{{ $rv->title }}</div>
                    <div style="color:#6b7794;font-size:12.5px">{{ \Illuminate\Support\Str::limit($rv->body, 60) }}</div>
                </td>
                <td>{{ $rv->product?->name ? \Illuminate\Support\Str::limit($rv->product->name, 16) : '-' }}</td>
                <td>{{ $rv->author_name ?? '구매자' }}</td>
                <td>{{ $rv->created_at->format('Y.m.d') }}</td>
                <td>@if($rv->is_hidden)<span class="pill pill-n">숨김</span>@else<span class="pill pill-y">노출</span>@endif</td>
                <td>
                    <form method="POST" action="{{ route('admin.reviews.toggle', $rv) }}" style="display:inline">
                        @csrf @method('PUT')
                        <button class="abtn abtn-ghost abtn-sm">{{ $rv->is_hidden ? '노출' : '숨김' }}</button>
                    </form>
                    <form method="POST" action="{{ route('admin.reviews.destroy', $rv) }}" style="display:inline" onsubmit="return confirm('삭제하시겠습니까?')">
                        @csrf @method('DELETE')
                        <button class="abtn abtn-red abtn-sm">삭제</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="7" style="text-align:center;color:#97a0b8;padding:40px">후기가 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $reviews->links('pagination.simple') }}
@endsection
