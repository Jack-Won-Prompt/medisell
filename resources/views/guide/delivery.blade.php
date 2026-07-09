@extends('layouts.app')
@section('title', '2시 이전 주문 당일출고 — 메디셀')

@section('content')
<div class="page-head"><div class="container"><h1>2시 이전 주문 당일출고</h1></div></div>
<div class="container" style="padding:26px 20px;max-width:860px">

    <div class="form-card" style="display:flex;align-items:center;gap:18px;padding:30px 28px">
        <div style="flex:none;width:64px;height:64px;border-radius:16px;background:var(--navy-50);color:var(--navy-800);display:flex;align-items:center;justify-content:center"><x-icon name="truck" :size="34"/></div>
        <div>
            <h2 style="font-size:22px;font-weight:800;color:var(--navy-800)">평일 오후 2시 이전 결제 완료 시 당일출고</h2>
            <p class="muted" style="font-size:14px;margin-top:6px">병의원 진료에 차질 없도록 빠르게 발송합니다. (재고 보유 상품 기준)</p>
        </div>
    </div>

    <h3 style="font-size:18px;font-weight:700;color:var(--navy-800);margin:30px 0 14px">배송 안내</h3>
    <table class="info-table" style="width:100%;border-collapse:collapse;font-size:14px">
        <tbody>
            <tr><th style="width:150px;text-align:left;padding:14px 18px;background:var(--slate-50);border:1px solid var(--line);font-weight:700">당일출고 기준</th><td style="padding:14px 18px;border:1px solid var(--line)">평일 <b>오후 2:00 이전</b> 결제 완료 건 (재고 보유 상품)</td></tr>
            <tr><th style="text-align:left;padding:14px 18px;background:var(--slate-50);border:1px solid var(--line);font-weight:700">배송 소요</th><td style="padding:14px 18px;border:1px solid var(--line)">출고 후 영업일 기준 <b>1~3일</b> 내 수령</td></tr>
            <tr><th style="text-align:left;padding:14px 18px;background:var(--slate-50);border:1px solid var(--line);font-weight:700">배송비</th><td style="padding:14px 18px;border:1px solid var(--line)">기본 <b>{{ number_format($site['shipping_fee'] ?? 0) }}원</b> / <b>{{ number_format($site['free_ship_over'] ?? 0) }}원</b> 이상 구매 시 무료배송</td></tr>
            <tr><th style="text-align:left;padding:14px 18px;background:var(--slate-50);border:1px solid var(--line);font-weight:700">휴무</th><td style="padding:14px 18px;border:1px solid var(--line)">주말 · 공휴일 (해당일 주문은 다음 영업일 출고)</td></tr>
        </tbody>
    </table>

    <p class="muted" style="font-size:12.5px;margin-top:18px;line-height:1.7">
        · 오후 2시 이후 결제 건은 다음 영업일 출고됩니다.<br>
        · 재고 소진 · 일부 대량/특수 주문 상품은 당일출고에서 제외될 수 있습니다.<br>
        · 무통장입금은 <b>입금 확인 완료 시각</b> 기준으로 당일출고가 적용됩니다.
    </p>

    <div style="text-align:center;margin-top:26px">
        <a href="{{ route('catalog.index') }}" class="btn btn-primary">상품 보러가기 <x-icon name="arrow-right" :size="15"/></a>
    </div>
</div>
@endsection
