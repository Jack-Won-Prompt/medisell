@extends('layouts.app')
@section('title', '적립금 — 메디셀')

@section('content')
<div class="page-head"><div class="container"><h1>적립금</h1></div></div>
<div class="container" style="padding-top:26px">
    <div class="my-layout">
        @include('partials.mynav')
        <div>
            <div style="background:linear-gradient(120deg,var(--teal),var(--navy-700));color:#fff;border-radius:var(--radius);padding:24px;margin-bottom:24px">
                <div style="font-size:13px;opacity:.85">보유 적립금</div>
                <div style="font-size:30px;font-weight:800">{{ number_format(auth()->user()->point) }}원</div>
            </div>
            @if($logs->count())
                <table class="dtable">
                    <thead><tr><th>일시</th><th>내역</th><th style="text-align:right">변동</th><th style="text-align:right">잔액</th></tr></thead>
                    <tbody>
                    @foreach($logs as $log)
                        <tr>
                            <td>{{ $log->created_at->format('Y.m.d') }}</td>
                            <td>{{ $log->reason }}</td>
                            <td style="text-align:right;font-weight:600;color:{{ $log->amount >= 0 ? 'var(--navy-800)' : 'var(--red)' }}">{{ $log->amount >= 0 ? '+' : '' }}{{ number_format($log->amount) }}원</td>
                            <td style="text-align:right">{{ number_format($log->balance) }}원</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <div style="margin-top:24px">{{ $logs->links('pagination.simple') }}</div>
            @else
                <p class="muted">적립금 내역이 없습니다.</p>
            @endif
        </div>
    </div>
</div>
@endsection
