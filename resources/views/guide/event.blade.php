@extends('layouts.app')
@section('title', '신규회원 이벤트 — 메디셀')

@section('content')
<div class="page-head"><div class="container"><h1>신규회원 이벤트</h1></div></div>
<div class="container" style="padding:26px 20px;max-width:860px">

    <div class="form-card" style="text-align:center;background:linear-gradient(135deg,#0b3d91,#06255b);color:#fff;padding:44px 24px">
        <div style="font-size:14px;font-weight:700;letter-spacing:.06em;opacity:.9">MEDISELL WELCOME</div>
        <h2 style="font-size:30px;font-weight:800;margin:12px 0 8px">가입 즉시 {{ number_format($site['signup_point'] ?? 0) }}원 적립</h2>
        <p style="font-size:15px;opacity:.92">신규회원가입만 해도 바로 사용 가능한 적립금을 드립니다.</p>
        <a href="{{ route('register') }}" class="btn" style="margin-top:22px;background:#fff;color:var(--navy-800)">회원가입하고 혜택받기 <x-icon name="arrow-right" :size="15"/></a>
    </div>

    <h3 style="font-size:18px;font-weight:700;color:var(--navy-800);margin:30px 0 14px">이벤트 혜택</h3>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px">
        <div class="form-card" style="text-align:center;padding:26px 14px">
            <div style="color:var(--navy-800);margin-bottom:10px"><x-icon name="coin" :size="30"/></div>
            <b style="display:block;font-size:15px">가입 적립금</b>
            <p class="muted" style="font-size:13px;margin-top:6px">가입 즉시 {{ number_format($site['signup_point'] ?? 0) }}원 지급</p>
        </div>
        <div class="form-card" style="text-align:center;padding:26px 14px">
            <div style="color:var(--navy-800);margin-bottom:10px"><x-icon name="certificate" :size="30"/></div>
            <b style="display:block;font-size:15px">사업자 전용가</b>
            <p class="muted" style="font-size:13px;margin-top:6px">사업자 회원 승인 시 전용 특별가 적용</p>
        </div>
        <div class="form-card" style="text-align:center;padding:26px 14px">
            <div style="color:var(--navy-800);margin-bottom:10px"><x-icon name="tag" :size="30"/></div>
            <b style="display:block;font-size:15px">구매 적립</b>
            <p class="muted" style="font-size:13px;margin-top:6px">구매금액의 {{ $site['point_rate'] ?? 1 }}% 적립</p>
        </div>
    </div>

    <h3 style="font-size:18px;font-weight:700;color:var(--navy-800);margin:30px 0 14px">참여 방법</h3>
    <ol style="counter-reset:step;padding-left:0">
        @foreach(['메디셀 회원가입 (일반/사업자 선택)', '가입 완료 시 적립금 자동 지급', '사업자 회원은 사업자등록증 확인 후 전용가 적용', '적립금은 주문 시 현금처럼 사용'] as $s)
            <li class="form-card" style="display:flex;align-items:center;gap:14px;margin-bottom:8px;padding:16px 20px">
                <span style="flex:none;width:28px;height:28px;border-radius:50%;background:var(--navy-800);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px">{{ $loop->iteration }}</span>
                <span style="font-size:14.5px">{{ $s }}</span>
            </li>
        @endforeach
    </ol>

    <p class="muted" style="font-size:12.5px;margin-top:18px;line-height:1.7">
        · 적립금 유효기간 및 사용 조건은 마이페이지 &gt; 적립금에서 확인하실 수 있습니다.<br>
        · 사업자 회원 승인은 영업일 기준 1일 내 처리됩니다.
    </p>
</div>
@endsection
