@extends('layouts.app')
@section('title', '결제 실패 — 메디셀')

@section('content')
<div class="container" style="max-width:560px;padding:50px 20px;text-align:center">
    <div style="width:72px;height:72px;border-radius:50%;background:var(--red-50);color:var(--red);display:flex;align-items:center;justify-content:center;margin:0 auto 16px">
        <x-icon name="close" :size="40"/>
    </div>
    <h1 style="font-size:23px;font-weight:900">결제가 완료되지 않았습니다</h1>
    <p class="muted" style="margin-top:10px">{{ $message }}</p>
    @if($code)<p class="muted" style="font-size:12.5px;margin-top:4px">오류코드: {{ $code }}</p>@endif

    <div style="display:flex;gap:10px;justify-content:center;margin-top:26px">
        @if($order)
            <a href="{{ route('order.pay', $order) }}" class="btn btn-red btn-lg">다시 결제하기</a>
        @endif
        <a href="{{ route('cart.index') }}" class="btn btn-ghost btn-lg">장바구니로</a>
    </div>
</div>
@endsection
