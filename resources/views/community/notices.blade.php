@extends('layouts.app')
@section('title', '공지사항 — 메디셀')

@section('content')
<div class="page-head"><div class="container"><h1>공지사항</h1></div></div>
<div class="container" style="padding:26px 20px;max-width:920px">
    <table class="dtable">
        <thead><tr><th style="width:70px">번호</th><th>제목</th><th style="width:110px">작성일</th><th style="width:80px">조회</th></tr></thead>
        <tbody>
        @forelse($notices as $n)
            <tr>
                <td>@if($n->is_pinned)<span class="badge badge-best">공지</span>@else{{ $n->id }}@endif</td>
                <td><a href="{{ route('community.notice', $n) }}" style="font-weight:600">{{ $n->title }}</a></td>
                <td>{{ optional($n->published_at)->format('Y.m.d') }}</td>
                <td>{{ $n->views }}</td>
            </tr>
        @empty
            <tr><td colspan="4" style="text-align:center;padding:40px" class="muted">등록된 공지가 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div style="margin-top:24px">{{ $notices->links('pagination.simple') }}</div>
</div>
@endsection
