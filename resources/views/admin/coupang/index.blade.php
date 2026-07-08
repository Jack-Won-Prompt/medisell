@extends('layouts.admin')
@section('title', '쿠팡 경쟁가')
@section('heading', '쿠팡 경쟁가 조회')

@section('content')
<div class="adm-card">
    <div class="h">
        <span>제품 선택 / 키워드 검색
            @if($simulate)<span class="pill pill-w">시뮬레이트</span>
            @elseif($engine==='serp')<span class="pill pill-y">실연동 · 구글쇼핑(SERP)</span>
            @elseif($engine==='partners')<span class="pill pill-y">실연동 · 쿠팡 파트너스</span>
            @else<span class="pill pill-n">실연동 키 미설정</span>@endif
        </span>
    </div>
    <div style="padding:20px">
        <form method="GET" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
            <div class="afield" style="margin:0;min-width:320px;flex:1">
                <label>메디셀 제품</label>
                <select name="product_id" class="aselect" onchange="this.form.q.value='';this.form.submit()">
                    <option value="">— 제품 선택 —</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" {{ optional($product)->id===$p->id ? 'selected' : '' }}>{{ $p->name }} ({{ number_format($p->price) }}원)</option>
                    @endforeach
                </select>
            </div>
            <div class="afield" style="margin:0;min-width:220px">
                <label>또는 직접 키워드</label>
                <input type="text" name="q" class="ainput" value="{{ $product ? '' : $keyword }}" placeholder="예: 멸균거즈">
            </div>
            <button class="abtn abtn-pri">쿠팡 조회</button>
        </form>
        <div class="ahint" style="margin-top:8px">
            @if($simulate)
                <b>시뮬레이트 모드</b> — 제품명 기반 모의 경쟁가입니다. 실연동 방법(택1):<br>
                • <b>구글쇼핑(SERP) API</b>: <code>.env</code>에 <code>COUPANG_SERP_API_KEY</code>(SerpAPI 등) + <code>COUPANG_SIMULATE=false</code> → 쿠팡 포함 마켓 경쟁가 조회<br>
                • <b>쿠팡 파트너스 API</b>: <code>COUPANG_PARTNERS_ACCESS_KEY/SECRET_KEY</code> + <code>COUPANG_SIMULATE=false</code>
            @elseif($engine==='serp')
                실연동(구글쇼핑) — 구글 쇼핑 색인에서 쿠팡·타 마켓 판매가를 조회합니다.
                @if(!config('coupang.serp.coupang_only'))<span class="muted">(쿠팡 외 마켓 포함. 쿠팡만 보려면 <code>COUPANG_SERP_COUPANG_ONLY=true</code>)</span>@endif
            @elseif($engine==='partners')
                실연동 — 쿠팡 파트너스 검색 API로 조회합니다.
            @else
                <b style="color:#e0322d">실연동 키 미설정</b> — <code>COUPANG_SIMULATE=false</code> 인데 SERP/파트너스 키가 없어 결과가 비어 있습니다.
            @endif
        </div>
    </div>
</div>

@if($keyword !== '')
    <div class="adm-card">
        <div class="h">
            <span>「{{ $keyword }}」 쿠팡 검색결과 <span class="pill pill-b">{{ count($results) }}건</span></span>
            @if($refPrice)<span class="muted" style="font-size:13px">메디셀 판매가 <b style="color:var(--a-navy)">{{ number_format($refPrice) }}원</b></span>@endif
        </div>

        @if($stats)
        <div class="adm-stats" style="grid-template-columns:repeat(4,1fr);margin:16px 20px">
            <div class="adm-stat"><div><div class="v">{{ number_format($stats['min']) }}</div><div class="l">최저가(원)</div></div></div>
            <div class="adm-stat"><div><div class="v">{{ number_format($stats['avg']) }}</div><div class="l">평균가(원)</div></div></div>
            <div class="adm-stat"><div><div class="v">{{ number_format($stats['max']) }}</div><div class="l">최고가(원)</div></div></div>
            @if($refPrice)
                @php($diff = $refPrice - $stats['min'])
                <div class="adm-stat"><div>
                    <div class="v" style="color:{{ $diff>0 ? '#e0322d' : '#16a34a' }}">{{ $diff>0 ? '+' : '' }}{{ number_format($diff) }}</div>
                    <div class="l">메디셀−최저가 차이</div>
                </div></div>
            @endif
        </div>
        @endif

        <table class="atable">
            <thead><tr><th style="width:50px">순위</th><th>판매자(스토어)</th><th>상품명</th><th style="width:120px;text-align:right">판매가</th><th style="width:90px">배송</th><th style="width:110px">평점/리뷰</th><th style="width:90px">메디셀比</th><th style="width:60px"></th></tr></thead>
            <tbody>
            @forelse($results as $i => $r)
                <tr>
                    <td><b>{{ $i+1 }}</b></td>
                    <td><b>{{ $r['seller'] }}</b></td>
                    <td>{{ $r['title'] }}</td>
                    <td style="text-align:right"><b>{{ number_format($r['price']) }}</b>원</td>
                    <td>@if($r['rocket'])<span class="pill pill-b">로켓</span>@else{{ $r['delivery'] }}@endif</td>
                    <td>★ {{ $r['rating'] }} <span class="muted">({{ number_format($r['review']) }})</span></td>
                    <td>
                        @if($refPrice)
                            @php($d = $r['price'] - $refPrice)
                            <span style="color:{{ $d<0 ? '#e0322d' : ($d>0 ? '#16a34a' : '#6b7794') }};font-weight:600">{{ $d>0?'+':'' }}{{ number_format($d) }}</span>
                        @else - @endif
                    </td>
                    <td><a href="{{ $r['url'] }}" target="_blank" class="abtn abtn-ghost abtn-sm">쿠팡</a></td>
                </tr>
            @empty
                <tr><td colspan="8" style="text-align:center;color:#97a0b8;padding:40px">검색결과가 없습니다.</td></tr>
            @endforelse
            </tbody>
        </table>
        @if($refPrice && $stats)
            <div style="padding:14px 20px;border-top:1px solid var(--a-line);font-size:13.5px;color:#6b7794">
                @if($refPrice <= $stats['min'])
                    ✅ 메디셀 판매가가 <b style="color:#16a34a">최저가 수준</b>입니다.
                @elseif($refPrice <= $stats['avg'])
                    메디셀 판매가가 평균 이하입니다. 최저가({{ number_format($stats['min']) }}원)보다 {{ number_format($refPrice-$stats['min']) }}원 높습니다.
                @else
                    ⚠️ 메디셀 판매가가 <b style="color:#e0322d">평균보다 높습니다</b>. 가격 조정 검토가 필요할 수 있습니다.
                @endif
            </div>
        @endif
    </div>
@endif
@endsection
