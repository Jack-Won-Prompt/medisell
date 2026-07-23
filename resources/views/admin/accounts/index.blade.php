@extends('layouts.admin')
@section('title', '거래처 단가')
@section('heading', '거래처 단가 관리')

@section('content')
@if(session('ok'))<div class="alert alert-ok" style="margin-bottom:14px">{{ session('ok') }}</div>@endif

<div class="toolbar">
    <form method="GET" class="search-mini">
        <input type="text" name="q" value="{{ $q }}" placeholder="거래처명 / 코드">
        <button type="submit"><x-icon name="search" :size="16"/></button>
    </form>
    <div class="spacer"></div>
    <a href="{{ route('admin.accounts.create') }}" class="abtn abtn-pri"><x-icon name="plus"/> 거래처 등록</a>
</div>

<div class="adm-card">
    <p class="muted" style="padding:14px 18px 0;margin:0;font-size:13px">여러 회원 계정이 하나의 거래처를 공유하며, 거래처 단위로 <b>등급 할인율</b>·<b>제품 전용가</b>가 적용됩니다. 로그인한 소속 회원에게 자동 반영됩니다.</p>
    <table class="atable">
        <thead><tr><th>거래처명</th><th>코드</th><th>등급 할인율</th><th>소속 회원</th><th>전용가 제품</th><th>상태</th><th></th></tr></thead>
        <tbody>
        @forelse($accounts as $a)
            <tr>
                <td><a href="{{ route('admin.accounts.edit', $a) }}" style="font-weight:600;color:var(--a-navy)">{{ $a->name }}</a>@if($a->memo)<div style="font-size:11.5px;color:#97a0b8">{{ $a->memo }}</div>@endif</td>
                <td>{{ $a->code ?? '—' }}</td>
                <td>{{ rtrim(rtrim(number_format($a->discount_rate, 2), '0'), '.') }}%</td>
                <td>{{ $a->users_count }}명</td>
                <td>{{ $a->prices_count }}개</td>
                <td>@if($a->is_active)<span class="status-pill st-done">활성</span>@else<span class="status-pill st-cancelled">비활성</span>@endif</td>
                <td style="text-align:right"><a href="{{ route('admin.accounts.edit', $a) }}" class="abtn abtn-ghost abtn-sm">관리</a></td>
            </tr>
        @empty
            <tr><td colspan="7" style="text-align:center;color:#97a0b8;padding:40px">등록된 거래처가 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $accounts->links('pagination.simple') }}
@endsection
