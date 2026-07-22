@extends('layouts.admin')
@section('title', $cfg['label'])
@section('heading', $cfg['label'].' 관리')

@section('content')
<div class="toolbar">
    <form method="GET" class="search-mini" style="display:flex;align-items:center;gap:10px">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ $cfg['label'] }} 검색">
        <button type="submit"><x-icon name="search" :size="16"/></button>
        @if($cfg['key'] === 'products')
            <label style="display:inline-flex;align-items:center;gap:5px;font-size:13px;color:var(--a-navy,#12459e);white-space:nowrap;cursor:pointer">
                <input type="checkbox" name="no_image" value="1" onchange="this.form.submit()" {{ request()->boolean('no_image') ? 'checked' : '' }}>
                이미지 없는 상품
            </label>
        @endif
    </form>
    <div class="spacer"></div>
    @if($cfg['key'] === 'products')
        <a href="{{ route('admin.export.products') }}" class="abtn abtn-ghost"><x-icon name="doc" :size="15"/> CSV</a>
    @endif
    <a href="{{ route('admin.create', $cfg['key']) }}" class="abtn abtn-pri"><x-icon name="plus"/> {{ $cfg['label'] }} 등록</a>
</div>

<div class="adm-card">
    <table class="atable">
        <thead>
            <tr>
                @foreach($cfg['columns'] as $col => $label)<th>{{ $label }}</th>@endforeach
                <th style="width:1%;white-space:nowrap">관리</th>
            </tr>
        </thead>
        <tbody>
        @forelse($items as $item)
            <tr>
                @foreach($cfg['columns'] as $col => $label)
                    <td>
                        @if($loop->first && isset($item->_depth) && $item->_depth > 0)
                            <span style="display:inline-block;color:#c7cedd">{!! str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $item->_depth) !!}└</span>
                        @endif
                        @php($val = data_get($item, $col))
                        @if(in_array($col, ['thumbnail', 'image']))
                            @if($val)
                                <img src="{{ $val }}" alt="" loading="lazy" style="width:46px;height:46px;object-fit:contain;border:1px solid var(--a-line);border-radius:6px;background:#fff">
                            @else
                                <span style="display:inline-flex;width:46px;height:46px;align-items:center;justify-content:center;border:1px solid var(--a-line);border-radius:6px;background:#f6f8fc;color:#c7cedd"><x-icon name="box" :size="18"/></span>
                            @endif
                        @elseif(is_bool($val))
                            @if($val)<span class="tick">●</span>@else<span class="cross">○</span>@endif
                        @elseif(in_array($col, ['price','member_price']) && is_numeric($val))
                            {{ number_format($val) }}원
                        @elseif($val instanceof \Illuminate\Support\Carbon)
                            {{ $val->format('Y.m.d') }}
                        @elseif($loop->first && isset($item->_depth) && $item->_depth === 0)
                            <b>{{ Str::limit((string) $val, 40) ?: '-' }}</b>
                        @else
                            {{ Str::limit((string) $val, 40) ?: '-' }}
                        @endif
                    </td>
                @endforeach
                <td style="white-space:nowrap">
                    <div style="display:flex;gap:6px;align-items:center;flex-wrap:nowrap">
                        @if($cfg['key'] === 'coupons')
                            <a href="{{ route('admin.coupons.issue', $item->id) }}" class="abtn abtn-pri abtn-sm">발행</a>
                        @endif
                        <a href="{{ route('admin.edit', [$cfg['key'], $item->id]) }}" class="abtn abtn-ghost abtn-sm">수정</a>
                        <form method="POST" action="{{ route('admin.destroy', [$cfg['key'], $item->id]) }}" style="margin:0" onsubmit="return confirm('삭제하시겠습니까?')">
                            @csrf @method('DELETE')
                            <button class="abtn abtn-red abtn-sm">삭제</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="{{ count($cfg['columns'])+1 }}" style="text-align:center;color:#97a0b8;padding:40px">등록된 항목이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

@if($items instanceof \Illuminate\Contracts\Pagination\Paginator)
    {{ $items->links('pagination.simple') }}
@else
    <div style="margin-top:12px;font-size:12.5px;color:#97a0b8">계층 구조로 전체 {{ $items->count() }}개 표시 (검색 시 평면 목록)</div>
@endif
@endsection
