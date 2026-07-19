@extends('layouts.admin')
@section('title', '로그인 이력')
@section('heading', '로그인 이력')

@section('content')
{{-- 요약 --}}
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:16px">
    <div class="adm-card" style="padding:14px 16px"><div style="font-size:12px;color:#97a0b8">전체</div><b style="font-size:20px">{{ number_format($stats['total']) }}</b></div>
    <div class="adm-card" style="padding:14px 16px"><div style="font-size:12px;color:#97a0b8">오늘</div><b style="font-size:20px">{{ number_format($stats['today']) }}</b></div>
    <div class="adm-card" style="padding:14px 16px"><div style="font-size:12px;color:#97a0b8">성공</div><b style="font-size:20px;color:#12805c">{{ number_format($stats['success']) }}</b></div>
    <div class="adm-card" style="padding:14px 16px"><div style="font-size:12px;color:#97a0b8">실패</div><b style="font-size:20px;color:#c0392b">{{ number_format($stats['fail']) }}</b></div>
</div>

<div class="toolbar">
    <div class="filter-tabs">
        <a href="{{ route('admin.login-logs.index', ['result'=>'all', 'q'=>$q]) }}" class="{{ $result==='all' ? 'on' : '' }}">전체</a>
        <a href="{{ route('admin.login-logs.index', ['result'=>'success', 'q'=>$q]) }}" class="{{ $result==='success' ? 'on' : '' }}">성공</a>
        <a href="{{ route('admin.login-logs.index', ['result'=>'fail', 'q'=>$q]) }}" class="{{ $result==='fail' ? 'on' : '' }}">실패</a>
    </div>
    <div class="spacer"></div>
    <form method="GET" class="search-mini">
        <input type="hidden" name="result" value="{{ $result }}">
        <input type="text" name="q" value="{{ $q }}" placeholder="이메일 / IP">
        <button><x-icon name="search" :size="16"/></button>
    </form>
</div>

<div class="adm-card">
    <table class="atable">
        <thead><tr><th>일시</th><th>결과</th><th>사용자</th><th>이메일</th><th>IP</th><th>Guard</th><th>브라우저</th></tr></thead>
        <tbody>
        @forelse($logs as $log)
            <tr>
                <td style="white-space:nowrap">{{ $log->created_at?->format('Y.m.d H:i:s') }}</td>
                <td>
                    @if($log->success)<span class="status-pill st-done">성공</span>
                    @else<span class="status-pill st-cancelled">실패</span>@endif
                </td>
                <td>
                    @if($log->user)
                        <a href="{{ route('admin.users.show', $log->user) }}" style="color:var(--a-navy)">{{ $log->user->name }}</a>
                        @if($log->user->is_admin)<span class="status-pill st-pending" style="margin-left:4px">관리자</span>@endif
                    @else <span style="color:#cbd2e0">—</span> @endif
                </td>
                <td>{{ $log->email ?? '—' }}</td>
                <td style="font-family:monospace;font-size:12.5px">{{ $log->ip ?? '—' }}</td>
                <td><span style="font-size:12px;color:#97a0b8">{{ $log->guard ?? '—' }}</span></td>
                <td style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:11.5px;color:#97a0b8" title="{{ $log->user_agent }}">{{ $log->user_agent ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="7" style="text-align:center;color:#97a0b8;padding:40px">로그인 이력이 없습니다.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
{{ $logs->links('pagination.simple') }}
@endsection
