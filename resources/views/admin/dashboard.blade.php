@extends('layouts.admin')
@section('title', '대시보드')
@section('heading', '대시보드')

@section('content')
<div class="adm-stats">
    @foreach($stats as $s)
        @php($card = "<span class='ic'><svg width='24' height='24'><use href='#i-{$s['icon']}'/></svg></span><div><div class='v'>".number_format($s['value'])."</div><div class='l'>{$s['label']}</div></div>")
        @if($s['route'])
            <a href="{{ route($s['route']) }}" class="adm-stat">{!! $card !!}</a>
        @else
            <div class="adm-stat">{!! $card !!}</div>
        @endif
    @endforeach
</div>

<div class="adm-card">
    <div class="h">오늘 매출 <span style="color:var(--a-navy);font-size:20px;font-weight:900">{{ number_format($todaySales) }}원</span></div>
</div>

<div style="display:grid;grid-template-columns:1.4fr 1fr;gap:20px">
    <div class="adm-card">
        <div class="h">최근 주문 <a href="{{ route('admin.orders.index') }}" class="abtn abtn-ghost abtn-sm">전체보기</a></div>
        <table class="atable">
            <thead><tr><th>주문번호</th><th>주문자</th><th>금액</th><th>상태</th></tr></thead>
            <tbody>
            @forelse($recentOrders as $o)
                <tr>
                    <td><a href="{{ route('admin.orders.show', $o) }}" style="font-weight:700;color:var(--a-navy)">{{ $o->order_no }}</a></td>
                    <td>{{ $o->receiver_name }}</td>
                    <td>{{ number_format($o->total) }}원</td>
                    <td><span class="status-pill st-{{ $o->status }}">{{ $o->statusLabel() }}</span></td>
                </tr>
            @empty
                <tr><td colspan="4" style="text-align:center;color:#97a0b8;padding:24px">주문이 없습니다.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="adm-card">
        <div class="h">최근 문의 <a href="{{ route('admin.inquiries.index') }}" class="abtn abtn-ghost abtn-sm">전체보기</a></div>
        <table class="atable">
            <thead><tr><th>유형</th><th>제목</th><th>상태</th></tr></thead>
            <tbody>
            @forelse($recentInquiries as $q)
                <tr>
                    <td><span class="pill pill-b">{{ $q->typeLabel() }}</span></td>
                    <td><a href="{{ route('admin.inquiries.show', $q) }}">{{ Str::limit($q->subject, 20) }}</a></td>
                    <td>@if($q->status==='answered')<span class="pill pill-y">완료</span>@else<span class="pill pill-w">대기</span>@endif</td>
                </tr>
            @empty
                <tr><td colspan="3" style="text-align:center;color:#97a0b8;padding:24px">문의가 없습니다.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
