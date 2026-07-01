@extends('layouts.admin')
@section('title', '문의상세')
@section('heading', '문의상세')

@section('content')
<div class="adm-card" style="max-width:820px">
    <div class="h">
        <span><span class="pill pill-b">{{ $inquiry->typeLabel() }}</span> {{ $inquiry->subject }}</span>
        @if($inquiry->status==='answered')<span class="pill pill-y">답변완료</span>@else<span class="pill pill-w">미답변</span>@endif
    </div>
    <div style="padding:20px">
        <div style="font-size:13px;color:#6b7794;margin-bottom:14px">
            {{ $inquiry->name }} · {{ $inquiry->phone ?? $inquiry->email ?? '-' }} · {{ $inquiry->created_at->format('Y.m.d H:i') }}
            @if($inquiry->is_secret) · <x-icon name="shield" :size="13"/> 비밀글 @endif
        </div>
        <div style="background:#f7f9fc;border:1px solid var(--a-line);border-radius:10px;padding:18px;font-size:14px;line-height:1.9;white-space:pre-line">{{ $inquiry->body }}</div>

        <form method="POST" action="{{ route('admin.inquiries.answer', $inquiry) }}" style="margin-top:22px">
            @csrf @method('PUT')
            <div class="afield">
                <label>답변</label>
                <textarea name="answer" class="atextarea" rows="6" placeholder="답변 내용을 입력하세요">{{ old('answer', $inquiry->answer) }}</textarea>
            </div>
            <div style="display:flex;gap:10px;align-items:center">
                <a href="{{ route('admin.inquiries.index') }}" class="abtn abtn-ghost">목록</a>
                <button class="abtn abtn-pri">{{ $inquiry->status==='answered' ? '답변 수정' : '답변 등록' }}</button>
            </div>
        </form>

        <form method="POST" action="{{ route('admin.inquiries.destroy', $inquiry) }}" onsubmit="return confirm('삭제하시겠습니까?')" style="margin-top:12px;text-align:right">
            @csrf @method('DELETE')
            <button class="abtn abtn-red">문의 삭제</button>
        </form>
    </div>
</div>
@endsection
