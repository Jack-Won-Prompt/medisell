@extends('layouts.admin')
@section('title', '매출 리포트')
@section('heading', '매출 리포트')

@section('content')
<form method="GET" class="toolbar" style="align-items:flex-end">
    <div class="afield" style="margin:0"><label>시작일</label><input type="date" name="from" value="{{ $from }}" class="ainput"></div>
    <div class="afield" style="margin:0"><label>종료일</label><input type="date" name="to" value="{{ $to }}" class="ainput"></div>
    <button class="abtn abtn-pri">조회</button>
    <div class="spacer"></div>
    <a href="{{ route('admin.export.orders') }}" class="abtn abtn-ghost"><x-icon name="doc" :size="15"/> 주문 CSV</a>
</form>

{{-- 요약 --}}
<div class="adm-stats">
    <div class="adm-stat"><span class="ic"><x-icon name="coin"/></span><div><div class="v">{{ number_format($summary['sales']) }}</div><div class="l">매출 합계(원)</div></div></div>
    <div class="adm-stat"><span class="ic"><x-icon name="cart"/></span><div><div class="v">{{ number_format($summary['orders']) }}</div><div class="l">결제 주문수</div></div></div>
    <div class="adm-stat"><span class="ic"><x-icon name="chart"/></span><div><div class="v">{{ number_format($summary['avg']) }}</div><div class="l">평균 객단가(원)</div></div></div>
    <div class="adm-stat"><span class="ic"><x-icon name="close"/></span><div><div class="v">{{ number_format($summary['cancelled']) }}</div><div class="l">취소 주문</div></div></div>
</div>

<div style="display:grid;grid-template-columns:1.6fr 1fr;gap:20px;align-items:start">
    {{-- 일별 매출 차트 --}}
    <div class="adm-card">
        <div class="h">일별 매출 ({{ $from }} ~ {{ $to }})</div>
        <div style="padding:20px;overflow-x:auto">
            <div class="bars">
                @foreach($series as $s)
                    <div class="bar-col" title="{{ $s['date'] }} · {{ number_format($s['amt']) }}원 · {{ $s['cnt'] }}건">
                        <div class="bar" style="height:{{ $s['amt'] > 0 ? max(3, round($s['amt'] / $maxAmt * 160)) : 0 }}px"></div>
                        <span class="bar-x">{{ \Illuminate\Support\Carbon::parse($s['date'])->format('m/d') }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- 상태 분포 --}}
    <div class="adm-card">
        <div class="h">주문 상태 분포</div>
        <table class="atable">
            <tbody>
            @foreach($statuses as $k => $label)
                <tr><td>{{ $label }}</td><td style="text-align:right"><b>{{ number_format($statusDist[$k] ?? 0) }}</b> 건</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

{{-- 인기 상품 --}}
<div class="adm-card" style="margin-top:20px">
    <div class="h">인기 상품 TOP 10 (매출 기준)</div>
    <table class="atable">
        <thead><tr><th style="width:50px">순위</th><th>상품명</th><th style="width:120px;text-align:right">판매수량</th><th style="width:140px;text-align:right">매출액</th></tr></thead>
        <tbody>
        @forelse($topProducts as $i => $p)
            <tr>
                <td><b>{{ $i + 1 }}</b></td>
                <td>{{ $p->product_name }}</td>
                <td style="text-align:right">{{ number_format($p->qty) }}</td>
                <td style="text-align:right"><b style="color:var(--a-navy)">{{ number_format($p->amt) }}원</b></td>
            </tr>
        @empty
            <tr><td colspan="4" style="text-align:center;color:#97a0b8;padding:30px">기간 내 매출이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<style>
.bars{display:flex;align-items:flex-end;gap:4px;min-height:190px;height:190px}
.bar-col{flex:1;min-width:18px;display:flex;flex-direction:column;align-items:center;justify-content:flex-end;height:100%}
.bar{width:70%;max-width:26px;background:linear-gradient(180deg,#2563eb,#0b3d91);border-radius:4px 4px 0 0;transition:.15s}
.bar-col:hover .bar{background:#e0322d}
.bar-x{font-size:10px;color:#97a0b8;margin-top:5px;white-space:nowrap}
</style>
@endsection
