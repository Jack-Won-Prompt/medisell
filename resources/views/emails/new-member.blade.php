@php $m = $member; $biz = $m->member_type === 'business'; @endphp
<div style="font-family:'Malgun Gothic',sans-serif;font-size:14px;color:#1f2937;line-height:1.7">
    <p>새 회원이 가입했습니다.@if($biz) <b style="color:#b45309">병원 회원 — 서류 확인 후 승인이 필요합니다.</b>@endif</p>
    <table style="border-collapse:collapse;font-size:13px">
        <tr><td style="padding:3px 12px 3px 0;color:#6b7280">구분</td><td>{{ $biz ? '병원 회원 (승인 대기)' : '일반 회원' }} · {{ $channel }} 가입</td></tr>
        <tr><td style="padding:3px 12px 3px 0;color:#6b7280">이름</td><td>{{ $m->name }}</td></tr>
        <tr><td style="padding:3px 12px 3px 0;color:#6b7280">이메일</td><td>{{ $m->email }}</td></tr>
        <tr><td style="padding:3px 12px 3px 0;color:#6b7280">연락처</td><td>{{ $m->phone ?: '-' }}</td></tr>
        @if($biz)
        <tr><td style="padding:3px 12px 3px 0;color:#6b7280">병원/상호</td><td>{{ $m->company_name ?: '-' }}</td></tr>
        <tr><td style="padding:3px 12px 3px 0;color:#6b7280">사업자번호</td><td>{{ $m->biz_no ?: '-' }}</td></tr>
        <tr><td style="padding:3px 12px 3px 0;color:#6b7280">종별</td><td>{{ $m->biz_type ?: '-' }}</td></tr>
        <tr><td style="padding:3px 12px 3px 0;color:#6b7280">요양기관기호</td><td>{{ $m->care_code ?: '-' }}</td></tr>
        <tr><td style="padding:3px 12px 3px 0;color:#6b7280">사업자등록증</td><td>{{ $m->biz_cert_path ? '첨부됨 (관리자 화면에서 확인)' : '미첨부' }}</td></tr>
        @endif
        <tr><td style="padding:3px 12px 3px 0;color:#6b7280">가입일시</td><td>{{ $m->created_at?->format('Y-m-d H:i') }}</td></tr>
    </table>
    <p style="margin-top:16px">
        <a href="{{ route('admin.users.show', $m) }}" style="display:inline-block;background:#1f3a6b;color:#fff;text-decoration:none;padding:8px 16px;border-radius:6px">관리자 화면에서 보기{{ $biz ? ' · 승인하기' : '' }}</a>
    </p>
    <p style="color:#9ca3af;font-size:12px;margin-top:18px">이 메일은 메디셀 회원가입 시 자동 발송됩니다.</p>
</div>
