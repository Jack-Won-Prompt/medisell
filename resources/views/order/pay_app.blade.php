<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
<title>결제하기 — 메디셀</title>
@php($provider = $order->pay_provider ?? 'toss')
<style>
  :root { --navy:#0b3d91; --red:#e11d48; --line:#e5e7eb; --ink:#111827; --muted:#6b7280; }
  * { box-sizing:border-box; -webkit-tap-highlight-color:transparent; }
  body { margin:0; font-family:-apple-system,'Noto Sans KR',Roboto,sans-serif; color:var(--ink); background:#f8fafc; }
  .wrap { max-width:600px; margin:0 auto; padding:18px 16px 40px; }
  .card { background:#fff; border:1px solid var(--line); border-radius:16px; padding:18px; margin-bottom:14px; }
  .row { display:flex; justify-content:space-between; font-size:14px; padding:5px 0; }
  .muted { color:var(--muted); }
  .total { border-top:1px solid var(--line); margin-top:10px; padding-top:12px; align-items:center; }
  .total b { color:var(--red); font-size:22px; }
  h1 { font-size:22px; margin:6px 0 16px; }
  h3 { font-size:15px; margin:0 0 10px; }
  .btn { width:100%; border:0; border-radius:12px; padding:16px; font-size:16px; font-weight:700;
         color:#fff; background:var(--red); cursor:pointer; }
  .btn:disabled { opacity:.5; }
  .hint { color:var(--muted); font-size:12px; text-align:center; margin-top:10px; }
</style>
</head>
<body>
<div class="wrap">
  <h1>결제하기</h1>
  <div class="card">
    <h3>주문 정보</h3>
    <div class="row"><span class="muted">주문번호</span><b>{{ $order->order_no }}</b></div>
    <div class="row"><span class="muted">상품</span><span>{{ $orderName }}</span></div>
    <div class="row total"><span>결제금액</span><b>{{ number_format($order->total) }}원</b></div>
  </div>

  @if($provider === 'portone')
    <div class="card">
      @if($portone['simulate'])
        <form method="POST" action="{{ route('pay.app.portone.simulate', $order) }}">@csrf
          <button class="btn">{{ number_format($order->total) }}원 결제하기</button>
        </form>
        <p class="hint">포트원 시뮬레이트 모드 — 실제 결제창 없이 완료됩니다.</p>
      @else
        <button id="pay-btn" class="btn">{{ number_format($order->total) }}원 결제하기</button>
        <form id="poVerify" method="POST" action="{{ route('pay.app.portone.verify') }}" style="display:none">@csrf
          <input type="hidden" name="imp_uid"><input type="hidden" name="merchant_uid" value="{{ $order->order_no }}">
        </form>
      @endif
    </div>
  @else
    <div class="card">
      <div id="payment-method"></div>
      <div id="agreement"></div>
      <button id="pay-btn" class="btn" style="margin-top:16px" disabled>{{ number_format($order->total) }}원 결제하기</button>
      <p class="hint">테스트 모드입니다. 실제 청구되지 않습니다.</p>
    </div>
  @endif
</div>

@if($provider === 'portone' && ! $portone['simulate'])
<script src="https://cdn.iamport.kr/v1/iamport.js"></script>
<script>
(function () {
  var IMP = window.IMP; IMP.init(@json($portone['imp_code']));
  var btn = document.getElementById('pay-btn'), form = document.getElementById('poVerify');
  btn.addEventListener('click', function () {
    IMP.request_pay({
      pg: @json($portone['pg']), pay_method: @json($portone['pay_method']),
      merchant_uid: @json($order->order_no), name: @json($orderName),
      amount: {{ (int) $order->total }},
      buyer_name: @json($order->receiver_name), buyer_tel: @json($order->receiver_phone)
    }, function (rsp) {
      if (rsp.success || rsp.imp_uid) { form.querySelector('[name=imp_uid]').value = rsp.imp_uid; form.submit(); }
      else { location.href = @json(route('pay.app.result')) + '?status=fail&message=' + encodeURIComponent(rsp.error_msg || '결제 취소'); }
    });
  });
})();
</script>
@elseif($provider !== 'portone')
<script src="https://js.tosspayments.com/v2/standard"></script>
<script>
(function () {
  var clientKey = @json($clientKey), customerKey = @json($customerKey), amount = {{ (int) $order->total }};
  var payload = {
    orderId: @json($order->order_no), orderName: @json($orderName),
    successUrl: @json(route('pay.app.toss.success')),
    failUrl: @json(route('pay.app.toss.fail')),
    customerName: @json($order->receiver_name), windowTarget: 'self'
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
      catch (e) { btn.disabled = false; }
    });
  })();
})();
</script>
@endif
</body>
</html>
