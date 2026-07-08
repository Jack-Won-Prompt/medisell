@extends('layouts.app')
@section('title', '결제하기 — 메디셀')

@php($provider = $order->pay_provider ?? 'toss')

@section('content')
<div class="container" style="max-width:680px;padding:30px 20px">
    <div class="page-head" style="background:none;color:var(--ink);padding:0 0 18px">
        <h1 style="font-size:24px">결제하기</h1>
    </div>

    <div class="form-card">
        <h3 style="border:0;margin:0 0 10px"><x-icon name="package"/> 주문 정보</h3>
        <div style="display:flex;justify-content:space-between;font-size:14px;padding:4px 0">
            <span class="muted">주문번호</span><b>{{ $order->order_no }}</b>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:14px;padding:4px 0">
            <span class="muted">상품</span><span>{{ $orderName }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:13px;padding:4px 0">
            <span class="muted">결제수단</span><span>{{ $provider === 'portone' ? '포트원(아임포트)' : '토스페이먼츠' }}</span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--line);margin-top:10px;padding-top:12px">
            <span>결제금액</span><b class="text-red" style="font-size:22px">{{ number_format($order->total) }}원</b>
        </div>
    </div>

    @if($provider === 'portone')
        {{-- 포트원(아임포트) --}}
        <div class="form-card">
            @if($portone['simulate'])
                <form method="POST" action="{{ route('payment.portone.simulate', $order) }}">
                    @csrf
                    <button class="btn btn-red btn-lg btn-block">{{ number_format($order->total) }}원 결제하기</button>
                </form>
                <p class="muted" style="font-size:12px;margin-top:10px;text-align:center">포트원 시뮬레이트 모드 — 실제 결제창 없이 완료 처리됩니다.</p>
            @else
                <button id="pay-btn" class="btn btn-red btn-lg btn-block">{{ number_format($order->total) }}원 결제하기</button>
                <form id="poVerify" method="POST" action="{{ route('payment.portone.verify') }}" style="display:none">
                    @csrf
                    <input type="hidden" name="imp_uid">
                    <input type="hidden" name="merchant_uid" value="{{ $order->order_no }}">
                </form>
                <p class="muted" style="font-size:12px;margin-top:10px;text-align:center">포트원 결제창으로 진행됩니다.</p>
            @endif
        </div>
    @else
        {{-- 토스페이먼츠 위젯 --}}
        <div class="form-card">
            <div id="payment-method"></div>
            <div id="agreement"></div>
            <button id="pay-btn" class="btn btn-red btn-lg btn-block" style="margin-top:16px" disabled>
                {{ number_format($order->total) }}원 결제하기
            </button>
            <p class="muted" style="font-size:12px;margin-top:10px;text-align:center">테스트 모드입니다. 실제 청구되지 않습니다.</p>
        </div>
    @endif
</div>
@endsection

@push('scripts')
@if($provider === 'portone' && ! $portone['simulate'])
<script src="https://cdn.iamport.kr/v1/iamport.js"></script>
<script>
(function () {
    var IMP = window.IMP; IMP.init(@json($portone['imp_code']));
    var btn = document.getElementById('pay-btn');
    var form = document.getElementById('poVerify');
    btn.addEventListener('click', function () {
        IMP.request_pay({
            pg: @json($portone['pg']),
            pay_method: @json($portone['pay_method']),
            merchant_uid: @json($order->order_no),
            name: @json($orderName),
            amount: {{ (int) $order->total }},
            buyer_name: @json($order->receiver_name),
            buyer_tel: @json($order->receiver_phone)
        }, function (rsp) {
            if (rsp.success || rsp.imp_uid) {
                form.querySelector('[name=imp_uid]').value = rsp.imp_uid;
                form.submit();
            } else {
                alert('결제 실패: ' + (rsp.error_msg || ''));
            }
        });
    });
})();
</script>
@elseif($provider !== 'portone')
<script src="https://js.tosspayments.com/v2/standard"></script>
<script>
(function () {
    var clientKey   = @json($clientKey);
    var customerKey = @json($customerKey);
    var amount      = {{ (int) $order->total }};
    var payload = {
        orderId:      @json($order->order_no),
        orderName:    @json($orderName),
        successUrl:   @json(route('payment.success')),
        failUrl:      @json(route('payment.fail')),
        customerName: @json($order->receiver_name),
        // PC: 별도 브라우저 창 대신 인페이지 모달(iframe)로 결제창 표시.
        // (카드사 본인인증 ISP/앱카드 등 일부 인증창은 카드사가 별도 창을 강제할 수 있음)
        windowTarget: 'iframe'
    };
    var btn = document.getElementById('pay-btn');
    var toss = TossPayments(clientKey);
    var widgets = toss.widgets({ customerKey: customerKey });
    (async function () {
        await widgets.setAmount({ currency: 'KRW', value: amount });
        await Promise.all([
            widgets.renderPaymentMethods({ selector: '#payment-method', variantKey: 'DEFAULT' }),
            widgets.renderAgreement({ selector: '#agreement', variantKey: 'AGREEMENT' })
        ]);
        btn.disabled = false;
        btn.addEventListener('click', async function () {
            btn.disabled = true;
            try { await widgets.requestPayment(payload); }
            catch (e) { btn.disabled = false; console.error(e); }
        });
    })();
})();
</script>
@endif
@endpush
