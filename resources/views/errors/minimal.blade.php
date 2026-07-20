<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('code') @yield('title') — 메디셀</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0}
        body{font-family:'Pretendard','Apple SD Gothic Neo','Malgun Gothic',system-ui,sans-serif;
            background:linear-gradient(160deg,#f3f7ff 0%,#e5edff 100%);color:#0b3d91;
            min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
        .box{background:#fff;border:1px solid #e3e8f1;border-radius:20px;
            box-shadow:0 20px 60px rgba(6,37,107,.10);max-width:520px;width:100%;
            padding:48px 40px;text-align:center}
        .mark{width:56px;height:56px;margin:0 auto 20px;display:block}
        .code{font-size:64px;font-weight:800;letter-spacing:-2px;color:#1857c4;line-height:1;margin-bottom:8px}
        .title{font-size:22px;font-weight:700;color:#0b3d91;margin-bottom:12px}
        .desc{font-size:15px;line-height:1.7;color:#4b5878;margin-bottom:28px}
        .actions{display:flex;gap:10px;justify-content:center;flex-wrap:wrap}
        .btn{display:inline-flex;align-items:center;gap:6px;padding:12px 22px;border-radius:12px;
            font-size:15px;font-weight:600;text-decoration:none;transition:.15s;cursor:pointer;border:0}
        .btn-primary{background:#1857c4;color:#fff}
        .btn-primary:hover{background:#12459e}
        .btn-ghost{background:#f3f7ff;color:#12459e;border:1px solid #c7cedd}
        .btn-ghost:hover{background:#e5edff}
        .foot{margin-top:28px;font-size:13px;color:#97a0b8}
        .foot a{color:#6b7794;text-decoration:none}
        .foot a:hover{text-decoration:underline}
        @media(max-width:480px){.box{padding:36px 24px}.code{font-size:52px}}
    </style>
</head>
<body>
    <div class="box">
        <svg class="mark" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <rect x="4" y="4" width="40" height="40" rx="10" fill="#1857c4"/>
            <path d="M24 13v22M13 24h22" stroke="#fff" stroke-width="4" stroke-linecap="round"/>
        </svg>
        <div class="code">@yield('code')</div>
        <div class="title">@yield('title')</div>
        <p class="desc">@yield('desc')</p>
        <div class="actions">
            <a href="{{ url('/') }}" class="btn btn-primary">홈으로 가기</a>
            <a href="javascript:history.back()" class="btn btn-ghost">이전 페이지</a>
        </div>
        <div class="foot">
            메디셀(MEDISELL) 의료소모품 쇼핑몰 · <a href="{{ url('/community/inquiry') }}">고객문의</a>
        </div>
    </div>
</body>
</html>
