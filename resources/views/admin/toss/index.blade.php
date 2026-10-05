@extends('layouts.admin')
@section('title', '토스 결제 이력')
@section('heading', '토스페이먼츠 결제 이력')

@section('content')
<div class="adm-card">
    <div class="h">
        <span>거래 조회 @if($testMode)<span class="pill pill-w">테스트 키</span>@else<span class="pill pill-y">운영 키</span>@endif</span>
        <span class="muted" style="font-size:12.5px">{{ $from->format('Y.m.d') }} ~ {{ $to->format('Y.m.d') }} · 토스에서 실시간 조회</span>
    </div>
    <div style="padding:16px 20px">
        <form method="GET" action="{{ route('admin.toss.index') }}" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
            <div class="afield" style="margin:0"><label>시작일</label><input type="date" name="from" class="ainput" value="{{ $from->toDateString() }}"></div>
            <div class="afield" style="margin:0"><label>종료일</label><input type="date" name="to" class="ainput" value="{{ $to->toDateString() }}"></div>
            <div class="afield" style="margin:0"><label>상태</label>
                <select name="status" class="aselect">
                    <option value="">전체</option>
                    @foreach($labels as $k => $v)<option value="{{ $k }}" {{ ($filters['status'] ?? '')===$k ? 'selected' : '' }}>{{ $v }}</option>@endforeach
                </select></div>
            <label class="acheck" style="display:flex;align-items:center;gap:6px;margin-bottom:8px"><input type="checkbox" name="issue" value="1" {{ ! empty($filters['issue']) ? 'checked' : '' }}> 불일치만</label>
            <label class="acheck" style="display:flex;align-items:center;gap:6px;margin-bottom:8px"><input type="checkbox" name="all" value="1" {{ ! empty($filters['all']) ? 'checked' : '' }}> 주문 없는 거래도 보기</label>
            <button class="abtn abtn-pri">조회</button>
        </form>
        @if($hidden)
            <div class="ahint" style="margin-top:8px;color:#b45309">메디셀 주문이 없는 토스 거래 {{ number_format($hidden) }}건(테스트·정리된 주문)은 숨겼습니다. '주문 없는 거래도 보기'로 볼 수 있습니다.</div>
        @endif
        <div class="ahint" style="margin-top:8px">최대 92일. 토스 거래 내역(주문번호 = 메디셀 주문번호)을 메디셀 주문과 맞춰 보고 금액·상태가 다르면 표시합니다. 취소·환불은 주문 관리에서 하세요.</div>
    </div>
</div>

@if($error)
    <div class="adm-card" style="margin-top:16px"><div style="padding:16px 20px;color:#e0322d">토스 조회 실패: {{ $error }}</div></div>
@endif

<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-top:16px">
    <div class="adm-card" style="padding:14px 18px"><div class="muted" style="font-size:12px">거래 건수</div><b style="font-size:20px">{{ number_format($summary['count']) }}</b></div>
    <div class="adm-card" style="padding:14px 18px"><div class="muted" style="font-size:12px">결제 완료 합계</div><b style="font-size:20px">{{ number_format($summary['done']) }}원</b></div>
    <div class="adm-card" style="padding:14px 18px"><div class="muted" style="font-size:12px">취소 합계</div><b style="font-size:20px">{{ number_format($summary['canceled']) }}원</b></div>
    <div class="adm-card" style="padding:14px 18px"><div class="muted" style="font-size:12px">주문 불일치</div><b style="font-size:20px;{{ $summary['issues'] ? 'color:#e0322d' : '' }}">{{ number_format($summary['issues']) }}건</b></div>
</div>

<div class="adm-card" style="margin-top:16px">
    <div style="padding:0 20px 10px;overflow-x:auto">
        <table class="atable" style="width:100%;font-size:13px">
            <thead><tr><th>거래일시</th><th>주문번호</th><th>주문자</th><th>결제수단</th><th style="text-align:right">금액</th><th>토스 상태</th><th>대사</th><th></th></tr></thead>
            <tbody>
            @forelse($rows as $r)
                <tr>
                    <td style="white-space:nowrap">{{ \Illuminate\Support\Carbon::parse($r['transactionAt'] ?? null)->format('m.d H:i') }}</td>
                    <td>
                        @if($r['order'])<a href="{{ route('admin.orders.show', $r['order']) }}">{{ $r['orderId'] }}</a>
                        @else{{ $r['orderId'] ?? '-' }}@endif
                    </td>
                    <td>{{ $r['order'] ? ($r['order']->buyer_hospital ?: ($r['order']->user?->company_name ?: ($r['order']->user?->name ?? $r['order']->receiver_name))) : '-' }}</td>
                    <td>{{ $r['method'] ?? '-' }}</td>
                    <td style="text-align:right">{{ number_format((int) ($r['amount'] ?? 0)) }}원</td>
                    <td><span class="pill {{ ($r['status'] ?? '')==='DONE' ? 'pill-y' : (in_array($r['status'] ?? '', ['CANCELED','PARTIAL_CANCELED','ABORTED','EXPIRED']) ? 'pill-n' : 'pill-w') }}">{{ $labels[$r['status'] ?? ''] ?? ($r['status'] ?? '-') }}</span></td>
                    <td>
                        @if($r['issues'])
                            @foreach($r['issues'] as $i)<div style="color:#e0322d;font-size:12px">{{ $i }}</div>@endforeach
                        @else<span style="color:#16a34a;font-size:12px">일치</span>@endif
                    </td>
                    <td style="white-space:nowrap">
                        @if(! empty($r['paymentKey']))<a href="{{ route('admin.toss.show', $r['paymentKey']) }}" class="abtn abtn-ghost abtn-sm">상세</a>@endif
                        @if(! empty($r['receiptUrl']))<a href="{{ $r['receiptUrl'] }}" target="_blank" rel="noopener" class="abtn abtn-ghost abtn-sm">영수증</a>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted" style="text-align:center;padding:24px">{{ $error ? '조회하지 못했습니다.' : '이 기간에 토스 거래가 없습니다.' }}</td></tr>
            @endforelse
            </tbody>
        </table>
        @if($nextAfter)
            <div style="text-align:center;padding:12px">
                <a href="{{ route('admin.toss.index', array_merge(request()->except('after'), ['after' => $nextAfter])) }}" class="abtn abtn-ghost">다음 100건</a>
            </div>
        @endif
    </div>
</div>
@endsection
