{{-- 토스 결제 상세 — 팝업(목록의 [상세])과 전체 페이지 공용 --}}
@php($fmt = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('Y.m.d H:i:s') : '-')

@if(! empty($p['error']))
    <div class="adm-card"><div style="padding:18px 20px;color:#e0322d">토스 조회 실패: [{{ $p['code'] ?? '' }}] {{ $p['message'] ?? '' }}</div></div>
@else
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
    <div class="adm-card">
        <div class="h"><span>결제 정보</span>@if($testMode)<span class="pill pill-w">테스트 키</span>@endif</div>
        <div style="padding:16px 20px;font-size:13.5px;line-height:2">
            <b>주문번호</b> @if($order)<a href="{{ route('admin.orders.show', $order) }}">{{ $p['orderId'] }}</a>@else{{ $p['orderId'] ?? '-' }} <span style="color:#e0322d">(메디셀 주문 없음)</span>@endif<br>
            <b>주문명</b> {{ $p['orderName'] ?? '-' }}<br>
            <b>상태</b> {{ $labels[$p['status'] ?? ''] ?? ($p['status'] ?? '-') }}<br>
            <b>결제수단</b> {{ $p['method'] ?? '-' }}<br>
            <b>결제금액</b> {{ number_format((int) ($p['totalAmount'] ?? 0)) }}원 · 잔액 {{ number_format((int) ($p['balanceAmount'] ?? 0)) }}원<br>
            <b>공급가/부가세</b> {{ number_format((int) ($p['suppliedAmount'] ?? 0)) }} / {{ number_format((int) ($p['vat'] ?? 0)) }}원<br>
            <b>요청</b> {{ $fmt($p['requestedAt'] ?? null) }}<br>
            <b>승인</b> {{ $fmt($p['approvedAt'] ?? null) }}<br>
            <b>paymentKey</b> <span style="font-size:12px;color:#6b7794">{{ $p['paymentKey'] ?? '' }}</span>
            @if(! empty($p['receipt']['url']))<br><a href="{{ $p['receipt']['url'] }}" target="_blank" rel="noopener" class="abtn abtn-ghost abtn-sm" style="margin-top:6px">영수증 보기</a>@endif
            @if($issues)<div style="margin-top:8px">@foreach($issues as $i)<div style="color:#e0322d;font-size:12.5px">⚠ {{ $i }}</div>@endforeach</div>@endif
        </div>
    </div>

    <div class="adm-card">
        <div class="h">결제수단 상세</div>
        <div style="padding:16px 20px;font-size:13.5px;line-height:2">
            @if(! empty($p['card']))
                <b>카드</b> <span title="카드사 코드 {{ $p['card']['issuerCode'] ?? '' }}">{{ \App\Support\TossCodes::card($p['card']['issuerCode'] ?? null) }}</span> {{ $p['card']['number'] ?? '' }} · {{ $p['card']['cardType'] ?? '' }}/{{ $p['card']['ownerType'] ?? '' }}<br>
                <b>할부</b> {{ (int) ($p['card']['installmentPlanMonths'] ?? 0) ?: '일시불' }}{{ ($p['card']['installmentPlanMonths'] ?? 0) ? '개월' : '' }} · 승인번호 {{ $p['card']['approveNo'] ?? '-' }}<br>
            @endif
            @if(! empty($p['easyPay']))<b>간편결제</b> {{ $p['easyPay']['provider'] ?? '' }} {{ number_format((int) ($p['easyPay']['amount'] ?? 0)) }}원<br>@endif
            @if(! empty($p['virtualAccount']))
                <b>가상계좌</b> <span title="은행 코드 {{ $p['virtualAccount']['bankCode'] ?? '' }}">{{ \App\Support\TossCodes::bank($p['virtualAccount']['bankCode'] ?? null) }}</span> {{ $p['virtualAccount']['accountNumber'] ?? '' }}<br>
                <b>입금자</b> {{ $p['virtualAccount']['customerName'] ?? '-' }} · 기한 {{ $fmt($p['virtualAccount']['dueDate'] ?? null) }} · {{ $p['virtualAccount']['settlementStatus'] ?? '' }}<br>
            @endif
            @if(! empty($p['transfer']))<b>계좌이체</b> <span title="은행 코드 {{ $p['transfer']['bankCode'] ?? '' }}">{{ \App\Support\TossCodes::bank($p['transfer']['bankCode'] ?? null) }}</span> · {{ $p['transfer']['settlementStatus'] ?? '' }}<br>@endif
            @if(! empty($p['cashReceipt']))<b>현금영수증</b> {{ $p['cashReceipt']['type'] ?? '' }} · {{ $p['cashReceipt']['issueNumber'] ?? '' }}<br>@endif
            @if(! empty($p['failure']))<b style="color:#e0322d">실패</b> [{{ $p['failure']['code'] ?? '' }}] {{ $p['failure']['message'] ?? '' }}<br>@endif
            @if(empty($p['card']) && empty($p['easyPay']) && empty($p['virtualAccount']) && empty($p['transfer']))<span class="muted">추가 정보 없음</span>@endif
        </div>
    </div>
</div>

<div class="adm-card" style="margin-top:20px">
    <div class="h">취소 내역</div>
    <div style="padding:0 20px 10px">
        <table class="atable" style="width:100%;font-size:13px">
            <thead><tr><th>취소일시</th><th style="text-align:right">취소금액</th><th>사유</th><th>상태</th></tr></thead>
            <tbody>
            @forelse($p['cancels'] ?? [] as $c)
                <tr>
                    <td>{{ $fmt($c['canceledAt'] ?? null) }}</td>
                    <td style="text-align:right">{{ number_format((int) ($c['cancelAmount'] ?? 0)) }}원</td>
                    <td>{{ $c['cancelReason'] ?? '-' }}</td>
                    <td>{{ $c['cancelStatus'] ?? '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted" style="text-align:center;padding:16px">취소 내역이 없습니다.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif
