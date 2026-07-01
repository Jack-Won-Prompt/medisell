@extends('layouts.app')
@section('title', '주문내역 — 메디셀')

@section('content')
<div class="page-head"><div class="container"><h1>주문내역</h1></div></div>
<div class="container" style="padding-top:26px">
    <div class="my-layout">
        @include('partials.mynav')
        <div>
            @if($orders->count())
                <table class="dtable">
                    <thead><tr><th>주문번호</th><th>주문일</th><th>상품</th><th>결제금액</th><th>상태</th></tr></thead>
                    <tbody>
                    @foreach($orders as $o)
                        <tr>
                            <td><a href="{{ route('mypage.order', $o) }}" style="font-weight:700;color:var(--navy-800)">{{ $o->order_no }}</a></td>
                            <td>{{ $o->created_at->format('Y.m.d') }}</td>
                            <td>{{ $o->items_count }}건</td>
                            <td><b>{{ number_format($o->total) }}원</b></td>
                            <td><span class="status-pill st-{{ $o->status }}">{{ $o->statusLabel() }}</span></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <div style="margin-top:24px">{{ $orders->links('pagination.simple') }}</div>
            @else
                <div class="empty"><x-icon name="package"/><h3>주문 내역이 없습니다</h3><a href="{{ route('catalog.index') }}" class="btn btn-primary" style="margin-top:16px">쇼핑하러 가기</a></div>
            @endif
        </div>
    </div>
</div>
@endsection
