@extends('layouts.admin')
@section('title', '대행 캐쉬백 정산')
@section('heading', '대행 캐쉬백 정산')

@section('content')
@if(session('ok'))<div class="alert alert-ok" style="margin-bottom:14px">{{ session('ok') }}</div>@endif

{{-- 대행자별 미정산 요약 --}}
<div class="adm-card" style="margin-bottom:16px">
    <table class="atable">
        <thead><tr><th>대행자</th><th>비율</th><th style="text-align:right">미정산</th><th style="text-align:right">정산완료 누적</th><th></th></tr></thead>
        <tbody>
        @forelse($agents as $a)
            <tr class="{{ $agentId===$a->id ? 'on' : '' }}">
                <td><a href="{{ route('admin.cashbacks.index', ['agent_id'=>$a->id, 'status'=>$status]) }}" style="font-weight:600;color:var(--a-navy)">{{ $a->name }}</a><div style="font-size:11.5px;color:#97a0b8">{{ $a->email }}</div></td>
                <td>{{ rtrim(rtrim(number_format($a->cashback_rate, 2), '0'), '.') }}%</td>
                <td style="text-align:right"><b style="color:var(--a-navy)">{{ number_format($a->pending_sum) }}원</b></td>
                <td style="text-align:right">{{ number_format($a->paid_sum) }}원</td>
                <td style="text-align:right">
                    @if($a->pending_sum > 0)
                    <form method="POST" action="{{ route('admin.cashbacks.settle.agent', $a) }}" onsubmit="return confirm('{{ $a->name }} 대행자의 미정산 전체를 정산완료 처리할까요?')">
                        @csrf<button class="abtn abtn-pri abtn-sm">일괄 정산</button>
                    </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" style="text-align:center;color:#97a0b8;padding:30px">구매 대행자가 없습니다. 회원관리에서 대행자로 지정하세요.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

<div class="toolbar">
    <div class="filter-tabs">
        @foreach(['pending'=>'적립대기','paid'=>'정산완료','cancelled'=>'취소','all'=>'전체'] as $k => $label)
            <a href="{{ route('admin.cashbacks.index', array_merge(['status'=>$k], $agentId ? ['agent_id'=>$agentId] : [])) }}" class="{{ $status===$k ? 'on' : '' }}">{{ $label }}</a>
        @endforeach
    </div>
    <div class="spacer"></div>
    @if($agentId)<a href="{{ route('admin.cashbacks.index', ['status'=>$status]) }}" class="abtn abtn-ghost abtn-sm">대행자 필터 해제</a>@endif
</div>

<div class="adm-card">
    <table class="atable">
        <thead><tr><th>일자</th><th>대행자</th><th>구매자(병원)</th><th>주문번호</th><th style="text-align:right">주문액</th><th style="text-align:right">캐쉬백</th><th>상태</th><th></th></tr></thead>
        <tbody>
        @forelse($cashbacks as $c)
            <tr>
                <td style="color:#97a0b8;font-size:12px">{{ $c->created_at->format('Y.m.d') }}</td>
                <td>{{ $c->agent?->name ?? '—' }}</td>
                <td>{{ $c->order?->buyer_hospital ?? '—' }}<div style="font-size:11.5px;color:#97a0b8">{{ $c->order?->buyer_name }}</div></td>
                <td>@if($c->order)<a href="{{ route('admin.orders.show', $c->order) }}" style="color:var(--a-navy)">{{ $c->order->order_no }}</a>@else — @endif</td>
                <td style="text-align:right">{{ number_format($c->order?->total ?? 0) }}원</td>
                <td style="text-align:right"><b>{{ number_format($c->amount) }}원</b> <span style="font-size:11px;color:#97a0b8">({{ rtrim(rtrim(number_format($c->rate, 2), '0'), '.') }}%)</span></td>
                <td>
                    @if($c->status==='paid')<span class="status-pill st-done">정산완료</span>
                    @elseif($c->status==='cancelled')<span class="status-pill st-cancelled">취소</span>
                    @else<span class="status-pill st-pending">적립대기</span>@endif
                </td>
                <td style="text-align:right">
                    @if($c->status==='pending')
                    <form method="POST" action="{{ route('admin.cashbacks.settle', $c) }}">
                        @csrf<button class="abtn abtn-ghost abtn-sm">정산완료</button>
                    </form>
                    @elseif($c->status==='paid' && $c->paid_at)
                    <span style="font-size:11.5px;color:#97a0b8">{{ $c->paid_at->format('m.d') }} 정산</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="8" style="text-align:center;color:#97a0b8;padding:40px">해당 내역이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $cashbacks->links('pagination.simple') }}
@endsection
