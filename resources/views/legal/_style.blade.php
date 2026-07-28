{{-- 약관·방침 공통 스타일 (조문 번호, 본문 여백) --}}
@once
<style>
    .legal{max-width:900px;margin:0 auto;padding:26px 20px 60px}
    .legal .meta{font-size:13px;color:var(--slate-500);margin-bottom:22px;padding-bottom:16px;border-bottom:1px solid var(--line)}
    .legal h2{font-size:17px;font-weight:800;color:var(--navy-800);margin:32px 0 10px;padding-top:6px}
    .legal h2:first-of-type{margin-top:8px}
    .legal h3{font-size:15px;font-weight:700;color:var(--slate-700);margin:20px 0 8px}
    .legal p{font-size:14px;line-height:1.85;color:var(--slate-700);margin:0 0 10px}
    .legal ol,.legal ul{margin:0 0 12px;padding-left:20px}
    .legal li{font-size:14px;line-height:1.85;color:var(--slate-700);margin-bottom:4px}
    .legal ol.circled{list-style:none;padding-left:0;counter-reset:c}
    .legal ol.circled>li{counter-increment:c;padding-left:26px;position:relative}
    .legal ol.circled>li::before{content:counter(c);position:absolute;left:0;top:2px;width:18px;height:18px;
        border:1px solid var(--slate-300);border-radius:50%;font-size:11px;line-height:17px;text-align:center;color:var(--slate-500)}
    .legal table{width:100%;border-collapse:collapse;font-size:13.5px;margin:10px 0 16px}
    .legal th,.legal td{border:1px solid var(--line);padding:11px 14px;text-align:left;vertical-align:top;line-height:1.7}
    .legal th{background:var(--slate-50);font-weight:700;color:var(--slate-700);white-space:nowrap}
    .legal .box{border:1px solid var(--line);border-radius:var(--radius);background:var(--slate-50);padding:18px 20px;margin:16px 0}
    .legal .box.warn{background:#fff7ed;border-color:#fed7aa}
    .legal .toc{display:flex;flex-wrap:wrap;gap:6px 14px;margin:0 0 8px}
    .legal .toc a{font-size:13px;color:var(--slate-600)}
    .legal .toc a:hover{color:var(--navy-800);text-decoration:underline}
    @media (max-width:640px){
        .legal th{white-space:normal}
        .legal table,.legal tbody,.legal tr,.legal th,.legal td{display:block;width:100%}
        .legal th{border-bottom:0}
    }
</style>
@endonce
