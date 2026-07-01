@extends('layouts.admin')
@section('title', '문의관리')
@section('heading', '문의관리')

@section('content')
<div class="toolbar">
    <div class="filter-tabs">
        <a href="{{ route('admin.inquiries.index') }}" class="{{ !request('type') && !request('status') ? 'on' : '' }}">전체</a>
        @foreach($types as $k=>$v)
            <a href="{{ route('admin.inquiries.index', ['type'=>$k]) }}" class="{{ request('type')===$k ? 'on' : '' }}">{{ $v }}</a>
        @endforeach
        <a href="{{ route('admin.inquiries.index', ['status'=>'pending']) }}" class="{{ request('status')==='pending' ? 'on' : '' }}">미답변</a>
    </div>
</div>

<div class="adm-card">
    <table class="atable">
        <thead><tr><th>유형</th><th>제목</th><th>작성자</th><th>연락처</th><th>상태</th><th>작성일</th><th></th></tr></thead>
        <tbody>
        @forelse($inquiries as $q)
            <tr>
                <td><span class="pill pill-b">{{ $q->typeLabel() }}</span></td>
                <td>{{ Str::limit($q->subject, 32) }} @if($q->is_secret)<x-icon name="shield" :size="13"/>@endif</td>
                <td>{{ $q->name }}</td>
                <td>{{ $q->phone ?? $q->email ?? '-' }}</td>
                <td>@if($q->status==='answered')<span class="pill pill-y">답변완료</span>@else<span class="pill pill-w">미답변</span>@endif</td>
                <td>{{ $q->created_at->format('m.d H:i') }}</td>
                <td><a href="{{ route('admin.inquiries.show', $q) }}" class="abtn abtn-ghost abtn-sm">보기</a></td>
            </tr>
        @empty
            <tr><td colspan="7" style="text-align:center;color:#97a0b8;padding:40px">문의가 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $inquiries->links('pagination.simple') }}
@endsection
