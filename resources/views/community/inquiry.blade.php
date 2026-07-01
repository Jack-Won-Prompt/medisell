@extends('layouts.app')
@section('title', '문의하기 — 메디셀')

@section('content')
<div class="page-head"><div class="container"><h1>문의하기</h1></div></div>
<div class="container" style="padding:26px 20px;max-width:760px">
    <form method="POST" action="{{ route('community.inquiry.store') }}" class="form-card">
        @csrf
        <div class="field">
            <label>문의 유형 <span class="req">*</span></label>
            <select name="type" class="select">
                <option value="qna" {{ $type==='qna'?'selected':'' }}>1:1 문의</option>
                <option value="quote" {{ $type==='quote'?'selected':'' }}>견적 문의</option>
                <option value="request" {{ $type==='request'?'selected':'' }}>상품 요청</option>
            </select>
        </div>
        <div class="row2">
            <div class="field"><label>이름 <span class="req">*</span></label><input type="text" name="name" class="input" value="{{ old('name', auth()->user()?->name) }}" required></div>
            <div class="field"><label>연락처</label><input type="text" name="phone" class="input" value="{{ old('phone', auth()->user()?->phone) }}"></div>
        </div>
        <div class="field"><label>이메일</label><input type="email" name="email" class="input" value="{{ old('email', auth()->user()?->email) }}"></div>
        <div class="field"><label>제목 <span class="req">*</span></label><input type="text" name="subject" class="input" value="{{ old('subject') }}" required></div>
        <div class="field"><label>내용 <span class="req">*</span></label><textarea name="body" class="textarea" required placeholder="문의 내용을 입력해 주세요. 견적문의의 경우 품목·수량을 함께 적어주시면 빠른 안내가 가능합니다.">{{ old('body') }}</textarea></div>
        <label class="inline" style="font-size:13px;margin-bottom:16px"><input type="checkbox" name="is_secret" value="1"> 비밀글로 작성</label>
        <div style="display:flex;gap:10px">
            <a href="{{ route('community.qna') }}" class="btn btn-ghost">취소</a>
            <button class="btn btn-primary">문의 등록</button>
        </div>
    </form>
</div>
@endsection
