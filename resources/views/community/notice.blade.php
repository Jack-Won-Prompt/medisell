@extends('layouts.app')
@section('title', $notice->title.' — 메디셀')

@section('content')
<div class="page-head"><div class="container"><h1>공지사항</h1></div></div>
<div class="container" style="padding:26px 20px;max-width:920px">
    <div class="form-card">
        <div style="border-bottom:2px solid var(--ink);padding-bottom:16px;margin-bottom:20px">
            <h2 style="font-size:21px;font-weight:800">@if($notice->is_pinned)<span class="badge badge-best" style="vertical-align:middle">공지</span> @endif{{ $notice->title }}</h2>
            <div class="muted" style="font-size:13px;margin-top:8px">{{ optional($notice->published_at)->format('Y.m.d') }} · 조회 {{ $notice->views }}</div>
        </div>
        <div class="prose" style="white-space:pre-line">{{ $notice->body }}</div>
    </div>
    <a href="{{ route('community.notices') }}" class="btn btn-ghost">목록으로</a>
</div>
@endsection
