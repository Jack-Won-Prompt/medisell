@extends('layouts.app')
@section('title', '캐쉬백 내역 — 메디셀')

@section('content')
<div class="page-head"><div class="container"><h1>캐쉬백 내역</h1></div></div>
<div class="container" style="padding-top:26px">
    <div class="my-layout">
        @include('partials.mynav')
        <div>
            {{-- 요약 --}}
            <div class="form-card" style="margin-bottom:14px;display:flex;gap:24px;flex-wrap:wrap;align-items:center">
                <div><div class="muted" style="font-size:13px">정산 대기</div><b style="font-size:18px;color:var(--navy-700)">{{ number_format($pending) }}원</b></div>
                <div><div class="muted" style="font-size:13px">정산 완료</div><b style="font-size:18px">{{ number_format($paid) }}원</b></div>
                <a href="{{ route('mypage.agent.buyers') }}" class="btn btn-ghost btn-sm" style="margin-left:auto">구매자 관리 →</a>
            </div>

            <div class="form-card">
                <table class="dtable">
                    <thead><tr><th>일자</th><th>주문번호</th><th>구매자</th><th style="text-align:right">주문액</th><th style="text-align:right">캐쉬백</th><th>상태</th></tr></thead>
                    <tbody>
                    @forelse($cashbacks as $c)
                        <tr>
                            <td class="muted" style="font-size:13px">{{ $c->created_at->format('Y-m-d') }}</td>
                            <td>{{ $c->order?->order_no ?? '—' }}</td>
                            <td>{{ $c->order?->buyer_hospital ?? '—' }}</td>
                            <td style="text-align:right">{{ number_format($c->order?->total ?? 0) }}원</td>
                            <td style="text-align:right"><b>{{ number_format($c->amount) }}원</b> <span class="muted" style="font-size:12px">({{ rtrim(rtrim(number_format($c->rate, 2), '0'), '.') }}%)</span></td>
                            <td>
                                @if($c->status==='paid')<span class="badge badge-plan">정산완료</span>
                                @elseif($c->status==='cancelled')<span class="badge badge-red">취소</span>
                                @else<span class="badge">적립대기</span>@endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="muted" style="text-align:center;padding:24px">캐쉬백 내역이 없습니다.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div style="margin-top:16px">{{ $cashbacks->links() }}</div>
        </div>
    </div>
</div>
@endsection
