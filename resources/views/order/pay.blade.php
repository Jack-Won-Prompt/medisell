@extends('layouts.app')
@section('title', '결제하기 — 메디셀')

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
        <div style="display:flex;justify-content:space-between;align-items:center;border-top:1px solid var(--line);margin-top:10px;padding-top:12px">
            <span>결제금액</span><b class="text-red" style="font-size:22px">{{ number_format($order->total) }}원</b>
        </div>
    </div>

    <div class="form-card">
        <div id="payment-method"></div>
        <div id="agreement"></div>
        <button id="pay-btn" class="btn btn-red btn-lg btn-block" style="margin-top:16px" disabled>
            {{ number_format($order->total) }}원 결제하기
        </button>
        <p class="muted" style="font-size:12px;margin-top:10px;text-align:center">테스트 모드입니다. 실제 청구되지 않습니다.</p>
    </div>
</div>
@endsection

@push('scripts')
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
        customerName: @json($order->receiver_name)
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
            try {
                await widgets.requestPayment(payload);
            } catch (e) {
                btn.disabled = false;
                console.error(e);
            }
        });
    })();
})();
</script>
@endpush
