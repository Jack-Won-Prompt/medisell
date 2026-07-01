@extends('layouts.admin')
@section('title', '입금확인')
@section('heading', '무통장 입금확인')

@section('content')
<div class="adm-card">
    <div class="h">
        <span>계좌 입금내역 수집 @if($simulate)<span class="pill pill-w">시뮬레이트</span>@else<span class="pill pill-y">실연동</span>@endif</span>
        <span class="muted" style="font-size:12.5px">무통장 대기주문 {{ $pendingCount }}건</span>
    </div>
    <div style="padding:20px">
        <form method="POST" action="{{ route('admin.bank.collect') }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
            @csrf
            <div class="afield" style="margin:0"><label>시작일</label><input type="date" name="s_date" class="ainput" value="{{ now()->subDays(7)->format('Y-m-d') }}"></div>
            <div class="afield" style="margin:0"><label>종료일</label><input type="date" name="e_date" class="ainput" value="{{ now()->format('Y-m-d') }}"></div>
            <button class="abtn abtn-pri">입금내역 수집 + 자동확인</button>
        </form>
        <div class="ahint" style="margin-top:8px">
            @if($simulate)시뮬레이트 모드: 무통장 <b>대기</b> 주문을 '입금됨'으로 가정한 가상 입금건을 생성해 매칭 흐름을 검증합니다.
            @else팝빌 계좌조회로 실제 입금내역을 수집합니다.@endif
            수집 후 <b>입금자명 + 금액</b> 일치 주문을 자동 결제확인합니다.
        </div>
        <form method="POST" action="{{ route('admin.bank.automatch') }}" style="margin-top:10px">
            @csrf
            <button class="abtn abtn-ghost abtn-sm">미매칭 재매칭 실행</button>
        </form>
    </div>
</div>

<div class="adm-card">
    <div class="h">입금 내역</div>
    <table class="atable">
        <thead><tr><th>거래일</th><th>입금자</th><th style="text-align:right">입금액</th><th>매칭</th><th style="width:220px">수동확인</th></tr></thead>
        <tbody>
        @forelse($deposits as $d)
            <tr>
                <td>{{ optional($d->trade_date)->format('Y.m.d') }} <span style="color:#97a0b8">{{ $d->trade_time }}</span></td>
                <td><b>{{ $d->depositor ?? '-' }}</b></td>
                <td style="text-align:right"><b>{{ number_format($d->amount) }}</b>원</td>
                <td>
                    @if($d->isMatched())
                        <span class="pill pill-y">확인</span>
                        <a href="{{ route('admin.orders.show', $d->matched_order_id) }}" style="color:var(--a-navy);font-size:12.5px">{{ $d->matchedOrder?->order_no }}</a>
                    @else
                        <span class="pill pill-w">미매칭</span>
                    @endif
                </td>
                <td>
                    @unless($d->isMatched())
                        <form method="POST" action="{{ route('admin.bank.match', $d) }}" style="display:flex;gap:6px">
                            @csrf
                            <select name="order_id" class="aselect" style="padding:6px" required>
                                <option value="">주문 선택</option>
                                @foreach($pendingOrders->where('total', $d->amount) as $o)
                                    <option value="{{ $o->id }}">{{ $o->order_no }} · {{ $o->depositor }} · {{ number_format($o->total) }}원</option>
                                @endforeach
                            </select>
                            <button class="abtn abtn-pri abtn-sm">확인</button>
                        </form>
                    @endunless
                </td>
            </tr>
        @empty
            <tr><td colspan="5" style="text-align:center;color:#97a0b8;padding:40px">수집된 입금내역이 없습니다. 위에서 수집을 실행하세요.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $deposits->links('pagination.simple') }}
@endsection
