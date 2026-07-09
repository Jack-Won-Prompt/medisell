@extends('layouts.app')
@section('title', '간편결제 안내 — 메디셀')

@section('content')
<div class="page-head"><div class="container"><h1>간편결제 안내</h1></div></div>
<div class="container" style="padding:26px 20px;max-width:860px">

    <div class="form-card" style="display:flex;align-items:center;gap:18px;padding:30px 28px">
        <div style="flex:none;width:64px;height:64px;border-radius:16px;background:var(--navy-50);color:var(--navy-800);display:flex;align-items:center;justify-content:center"><x-icon name="certificate" :size="34"/></div>
        <div>
            <h2 style="font-size:22px;font-weight:800;color:var(--navy-800)">안전하고 빠른 간편결제</h2>
            <p class="muted" style="font-size:14px;margin-top:6px">{{ $site['payment_pg'] === 'portone' ? '포트원(아임포트)' : '토스페이먼츠' }} 결제창을 통해 카드·간편결제·가상계좌를 지원합니다.</p>
        </div>
    </div>

    <h3 style="font-size:18px;font-weight:700;color:var(--navy-800);margin:30px 0 14px">지원 결제수단</h3>
    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:14px">
        <div class="form-card" style="display:flex;gap:12px;padding:20px"><span style="color:var(--navy-800)"><x-icon name="tag" :size="24"/></span><div><b>신용·체크카드</b><p class="muted" style="font-size:13px;margin-top:4px">전 카드사 결제 · 무이자 할부(카드사별)</p></div></div>
        <div class="form-card" style="display:flex;gap:12px;padding:20px"><span style="color:var(--navy-800)"><x-icon name="phone" :size="24"/></span><div><b>간편결제</b><p class="muted" style="font-size:13px;margin-top:4px">토스페이 · 카카오페이 · 네이버페이 등</p></div></div>
        <div class="form-card" style="display:flex;gap:12px;padding:20px"><span style="color:var(--navy-800)"><x-icon name="building" :size="24"/></span><div><b>가상계좌 / 계좌이체</b><p class="muted" style="font-size:13px;margin-top:4px">발급 계좌로 입금 시 자동 확인</p></div></div>
        <div class="form-card" style="display:flex;gap:12px;padding:20px"><span style="color:var(--navy-800)"><x-icon name="coin" :size="24"/></span><div><b>무통장입금</b><p class="muted" style="font-size:13px;margin-top:4px">안내 계좌로 직접 입금 후 확인</p></div></div>
    </div>

    <h3 style="font-size:18px;font-weight:700;color:var(--navy-800);margin:30px 0 14px">무통장 입금계좌</h3>
    <table class="info-table" style="width:100%;border-collapse:collapse;font-size:14px">
        <tbody>
            @foreach($site['banks'] as $b)
                <tr>
                    <th style="width:130px;text-align:left;padding:13px 18px;background:var(--slate-50);border:1px solid var(--line);font-weight:700">{{ $b['bank'] }}</th>
                    <td style="padding:13px 18px;border:1px solid var(--line)"><b>{{ $b['account'] }}</b> <span class="muted" style="margin-left:8px">예금주 {{ $b['holder'] }}</span></td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="muted" style="font-size:12.5px;margin-top:18px;line-height:1.7">
        · 모든 결제는 PG사의 보안 결제창을 통해 안전하게 처리됩니다.<br>
        · 무통장입금은 입금자명과 주문자명이 다를 경우 1:1 문의로 알려주세요.<br>
        · 가상계좌는 발급된 계좌로 입금하면 자동으로 입금 확인됩니다.
    </p>
</div>
@endsection
