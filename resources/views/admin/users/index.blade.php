@extends('layouts.admin')
@section('title', '회원관리')
@section('heading', '회원관리')

@section('content')
<div class="toolbar">
    <div class="filter-tabs">
        <a href="{{ route('admin.users.index') }}" class="{{ !request('filter') ? 'on' : '' }}">전체</a>
        <a href="{{ route('admin.users.index', ['filter'=>'business']) }}" class="{{ request('filter')==='business' ? 'on' : '' }}">병원회원</a>
        <a href="{{ route('admin.users.index', ['filter'=>'pending']) }}" class="{{ request('filter')==='pending' ? 'on' : '' }}">승인대기</a>
    </div>
    <div class="spacer"></div>
    <a href="{{ route('admin.export.users') }}" class="abtn abtn-ghost abtn-sm"><x-icon name="doc" :size="15"/> CSV</a>
    <form method="GET" class="search-mini">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="이름/이메일/상호">
        <button><x-icon name="search" :size="16"/></button>
    </form>
</div>

<div class="adm-card">
    <table class="atable">
        <thead><tr><th>이름</th><th>이메일</th><th>구분</th><th>병원/상호</th><th>승인</th><th>적립금</th><th>가입일</th><th></th></tr></thead>
        <tbody>
        @forelse($users as $u)
            <tr>
                <td><b>{{ $u->name }}</b> @if($u->is_admin)<span class="pill pill-b">관리자</span>@endif</td>
                <td>{{ $u->email }}</td>
                <td>{{ $u->member_type==='business' ? '병원' : '일반' }}</td>
                <td>{{ $u->company_name ?? '-' }} <span style="color:#97a0b8;font-size:12px">{{ $u->biz_no }}</span></td>
                <td>
                    @if($u->member_type!=='business')<span style="color:#cbd2e0">—</span>
                    @elseif($u->biz_status==='approved')<span class="pill pill-y">승인</span>
                    @elseif($u->biz_status==='pending')<span class="pill pill-w">대기</span>
                    @else<span class="pill pill-n">거절</span>@endif
                </td>
                <td>{{ number_format($u->point) }}원</td>
                <td>{{ $u->created_at->format('Y.m.d') }}</td>
                <td><a href="{{ route('admin.users.show', $u) }}" class="abtn abtn-ghost abtn-sm">상세</a></td>
            </tr>
        @empty
            <tr><td colspan="8" style="text-align:center;color:#97a0b8;padding:40px">회원이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $users->links('pagination.simple') }}
@endsection
