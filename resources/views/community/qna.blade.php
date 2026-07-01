@extends('layouts.app')
@section('title', '문의게시판 — 메디셀')

@section('content')
<div class="page-head"><div class="container"><h1>견적 · 1:1 문의</h1></div></div>
<div class="container" style="padding:26px 20px;max-width:920px">
    <div class="list-toolbar">
        <div class="cnt">총 <b>{{ $inquiries->total() }}</b>건</div>
        <div style="display:flex;gap:8px">
            <a href="{{ route('community.inquiry', ['type'=>'quote']) }}" class="btn btn-ghost btn-sm">견적문의</a>
            <a href="{{ route('community.inquiry', ['type'=>'qna']) }}" class="btn btn-primary btn-sm">＋ 문의하기</a>
        </div>
    </div>
    <table class="dtable">
        <thead><tr><th style="width:90px">유형</th><th>제목</th><th style="width:110px">작성자</th><th style="width:110px">작성일</th><th style="width:90px">상태</th></tr></thead>
        <tbody>
        @forelse($inquiries as $q)
            <tr>
                <td><span class="chip" style="padding:3px 9px;font-size:12px">{{ $q->typeLabel() }}</span></td>
                <td>
                    @if($q->is_secret)<x-icon name="shield" :size="14"/> <span class="muted">비밀글입니다</span>
                    @else {{ $q->subject }} @endif
                </td>
                <td>{{ $q->name }}</td>
                <td>{{ $q->created_at->format('Y.m.d') }}</td>
                <td>
                    @if($q->status==='answered')<span class="status-pill st-done">답변완료</span>
                    @else<span class="status-pill st-pending">접수</span>@endif
                </td>
            </tr>
        @empty
            <tr><td colspan="5" style="text-align:center;padding:40px" class="muted">등록된 문의가 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div style="margin-top:24px">{{ $inquiries->links('pagination.simple') }}</div>
</div>
@endsection
