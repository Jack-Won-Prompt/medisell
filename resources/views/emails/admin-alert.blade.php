<div style="font-family:'Malgun Gothic',sans-serif;font-size:14px;color:#1f2937;line-height:1.7">
    <p style="font-size:15px"><b>{{ $headline }}</b></p>
    <table style="border-collapse:collapse;font-size:13px">
        @foreach($rows as $label => $value)
            <tr><td style="padding:3px 14px 3px 0;color:#6b7280;white-space:nowrap;vertical-align:top">{{ $label }}</td><td>{{ $value !== null && $value !== '' ? $value : '-' }}</td></tr>
        @endforeach
    </table>
    @if($body)
        <div style="margin-top:12px;padding:12px 14px;background:#f7f9fc;border-radius:8px;white-space:pre-line;font-size:13px">{{ $body }}</div>
    @endif
    @if($url)
        <p style="margin-top:16px"><a href="{{ $url }}" style="display:inline-block;background:#1f3a6b;color:#fff;text-decoration:none;padding:8px 16px;border-radius:6px">관리자 화면에서 보기</a></p>
    @endif
    <p style="color:#9ca3af;font-size:12px;margin-top:18px">이 메일은 메디셀에서 자동 발송됩니다.</p>
</div>
