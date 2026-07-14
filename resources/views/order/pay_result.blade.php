<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>결제결과</title>
<style>
  body { margin:0; font-family:-apple-system,'Noto Sans KR',Roboto,sans-serif; background:#f8fafc;
         display:flex; align-items:center; justify-content:center; min-height:100vh; }
  .box { text-align:center; padding:30px; }
  .icon { font-size:56px; }
  h1 { font-size:20px; margin:14px 0 6px; color:#111827; }
  p { color:#6b7280; font-size:14px; }
</style>
</head>
<body>
{{-- 앱은 URL(/pay/app/result?status=)로 결과를 감지한다. data 속성도 함께 제공. --}}
<div class="box" id="result" data-status="{{ $status }}" data-order="{{ $orderNo }}">
  <div class="icon">{{ $status === 'success' ? '✅' : '⚠️' }}</div>
  <h1>{{ $status === 'success' ? '결제가 완료되었습니다' : '결제가 취소되었습니다' }}</h1>
  <p>{{ $message ?? ($status === 'success' ? '주문이 정상 접수되었습니다.' : '다시 시도해 주세요.') }}</p>
</div>
</body>
</html>
