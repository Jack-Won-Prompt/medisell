<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<title>주문서 {{ $order->order_no }}</title>
<style>
    @font-face { font-family: 'NanumGothic'; font-weight: normal; src: url('{{ $fontPath }}/NanumGothic-Regular.ttf') format('truetype'); }
    @font-face { font-family: 'NanumGothic'; font-weight: bold; src: url('{{ $fontPath }}/NanumGothic-Bold.ttf') format('truetype'); }
    @page { margin: 18mm 14mm; }
    * { font-family: 'NanumGothic', sans-serif; }
    body { font-size: 10pt; color: #1f2937; }
    h1 { font-size: 20pt; margin: 0 0 4px; letter-spacing: 6px; text-align: center; }
    .sub { text-align: center; color: #6b7280; font-size: 9pt; margin-bottom: 16px; }
    table { width: 100%; border-collapse: collapse; }
    .info td { border: 1px solid #cbd2e0; padding: 5px 7px; vertical-align: top; }
    .info th { border: 1px solid #cbd2e0; background: #eef2f8; padding: 5px 7px; width: 17%; text-align: left; font-weight: bold; }
    .sec { font-weight: bold; font-size: 11pt; margin: 16px 0 6px; }
    .items th { background: #1f3a6b; color: #fff; padding: 6px 4px; font-size: 9pt; border: 1px solid #1f3a6b; }
    .items td { border: 1px solid #cbd2e0; padding: 5px 4px; font-size: 9pt; }
    .r { text-align: right; } .c { text-align: center; }
    .muted { color: #6b7280; font-size: 8.5pt; }
    .sum td { padding: 4px 7px; }
    .sum .total td { border-top: 2px solid #1f3a6b; font-weight: bold; font-size: 12pt; padding-top: 7px; }
    .foot { margin-top: 22px; font-size: 8.5pt; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 8px; }
</style>
</head>
<body>
    <h1>주 문 서</h1>
    <div class="sub">{{ $site['name'] ?? '메디셀' }} · 주문번호 {{ $order->order_no }}</div>

    <table class="info">
        <tr>
            <th>주문일시</th><td>{{ $order->created_at?->format('Y-m-d H:i') }}</td>
            <th>결제일시</th><td>{{ $order->paid_at?->format('Y-m-d H:i') ?? '-' }}</td>
        </tr>
        <tr>
            <th>결제수단</th><td>{{ $payLabel }}</td>
            <th>주문상태</th><td>{{ $order->statusLabel() }}</td>
        </tr>
    </table>

    <div class="sec">주문자</div>
    @php $u = $order->user; @endphp
    <table class="info">
        <tr>
            <th>병원/상호</th><td>{{ $order->buyer_hospital ?: ($u?->company_name ?: '-') }}</td>
            <th>사업자번호</th><td>{{ $u?->biz_no ?: '-' }}</td>
        </tr>
        <tr>
            <th>주문자</th><td>{{ $order->buyer_name ?: ($u?->name ?: '-') }}</td>
            <th>연락처</th><td>{{ $order->buyer_phone ?: ($u?->phone ?: '-') }}</td>
        </tr>
        <tr>
            <th>이메일</th><td>{{ $u?->email ?: '-' }}</td>
            <th>회원구분</th><td>{{ $u ? ($u->member_type === 'business' ? '병원 회원'.($u->biz_status === 'approved' ? '(승인)' : '') : '일반 회원') : '비회원' }}</td>
        </tr>
    </table>

    <div class="sec">배송지</div>
    <table class="info">
        <tr>
            <th>받는 분</th><td>{{ $order->receiver_name }}</td>
            <th>연락처</th><td>{{ $order->receiver_phone }}</td>
        </tr>
        <tr>
            <th>주소</th><td colspan="3">{{ $order->postcode ? "({$order->postcode}) " : '' }}{{ $order->address1 }} {{ $order->address2 }}</td>
        </tr>
        @if($order->memo)
        <tr><th>배송메모</th><td colspan="3">{{ $order->memo }}</td></tr>
        @endif
    </table>

    <div class="sec">주문 상품</div>
    <table class="items">
        <thead>
            <tr>
                <th style="width:5%">No</th>
                <th style="width:12%">품목코드</th>
                <th>품목명 / 규격</th>
                <th style="width:7%">단위</th>
                <th style="width:8%">수량</th>
                <th style="width:12%">단가</th>
                <th style="width:14%">금액</th>
            </tr>
        </thead>
        <tbody>
        @foreach($order->items as $i => $it)
            <tr>
                <td class="c">{{ $i + 1 }}</td>
                <td class="c">{{ $it->product?->code ?? '-' }}</td>
                <td>{{ $it->product_name }}@if($it->product?->spec)<br><span class="muted">{{ $it->product->spec }}</span>@endif</td>
                <td class="c">{{ $it->unit ?: ($it->product?->unit ?? '') }}</td>
                <td class="r">{{ number_format($it->quantity) }}</td>
                <td class="r">{{ number_format($it->price) }}</td>
                <td class="r">{{ number_format($it->subtotal) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <table class="sum" style="width:45%;margin-left:55%;margin-top:10px">
        <tr><td>상품금액</td><td class="r">{{ number_format($order->subtotal) }}원</td></tr>
        <tr><td>배송비</td><td class="r">{{ number_format($order->shipping_fee) }}원</td></tr>
        @if($order->discount)<tr><td>할인{{ $order->coupon_code ? " ({$order->coupon_code})" : '' }}</td><td class="r">-{{ number_format($order->discount) }}원</td></tr>@endif
        @if($order->point_used)<tr><td>적립금 사용</td><td class="r">-{{ number_format($order->point_used) }}원</td></tr>@endif
        <tr class="total"><td>결제금액</td><td class="r">{{ number_format($order->total) }}원</td></tr>
    </table>

    <div class="foot">
        {{ $site['company'] ?? '' }} · 대표 {{ $site['ceo'] ?? '' }} · 사업자등록번호 {{ $site['biz_no'] ?? '' }} · 의료기기판매업 {{ $site['med_device'] ?? '' }}<br>
        {{ $site['address'] ?? '' }} · 고객센터 {{ $site['cs_tel'] ?? '' }} · {{ $site['email'] ?? '' }}
    </div>
</body>
</html>
