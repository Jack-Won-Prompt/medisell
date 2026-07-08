@extends('layouts.admin')
@section('title', '주문관리')
@section('heading', '주문관리')

@section('content')
<div class="toolbar">
    <div class="filter-tabs">
        <a href="{{ route('admin.orders.index') }}" class="{{ !$cur ? 'on' : '' }}">전체</a>
        @foreach($statuses as $k => $label)
            <a href="{{ route('admin.orders.index', ['status'=>$k]) }}" class="{{ $cur===$k ? 'on' : '' }}">{{ $label }}</a>
        @endforeach
    </div>
    <div class="spacer"></div>
    <a href="{{ route('admin.export.orders', request()->only('status')) }}" class="abtn abtn-ghost abtn-sm"><x-icon name="doc" :size="15"/> CSV</a>
    <form method="GET" class="search-mini">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="주문번호/주문자/입금자">
        <button><x-icon name="search" :size="16"/></button>
    </form>
</div>

<div class="adm-card">
    <table class="atable">
        <thead><tr><th>주문번호</th><th>주문자</th><th>입금자</th><th>금액</th><th>상태</th><th>주문일</th><th></th></tr></thead>
        <tbody>
        @forelse($orders as $o)
            <tr>
                <td><a href="{{ route('admin.orders.show', $o) }}" style="font-weight:600;color:var(--a-navy)">{{ $o->order_no }}</a></td>
                <td>{{ $o->receiver_name }}<div style="font-size:11.5px;color:#97a0b8">{{ $o->user?->email }}</div></td>
                <td>{{ $o->depositor }} <span style="font-size:11.5px;color:#97a0b8">({{ $o->bank }})</span></td>
                <td><b>{{ number_format($o->total) }}원</b></td>
                <td><span class="status-pill st-{{ $o->status }}">{{ $o->statusLabel() }}</span></td>
                <td>{{ $o->created_at->format('m.d H:i') }}</td>
                <td><a href="{{ route('admin.orders.show', $o) }}" class="abtn abtn-ghost abtn-sm">상세</a></td>
            </tr>
        @empty
            <tr><td colspan="7" style="text-align:center;color:#97a0b8;padding:40px">주문이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $orders->links('pagination.simple') }}
@endsection
