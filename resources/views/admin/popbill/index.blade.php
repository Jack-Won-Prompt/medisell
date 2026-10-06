@extends('layouts.admin')
@section('title', '팝빌 테스트')
@section('heading', '팝빌 테스트 (문자 · 현금영수증 · 세금계산서)')

@section('content')
@php($testServer = (bool) config('popbill.IsTest'))
<div class="adm-card">
    <div class="h">
        <span>연동 상태</span>
        @if($testServer)<span class="pill pill-w">팝빌 테스트 서버</span>@else<span class="pill pill-y">팝빌 운영 서버</span>@endif
    </div>
    <div style="padding:16px 20px">
        <table style="width:100%;font-size:13px;border-collapse:collapse">
            @foreach($status as $k => $v)
                <tr style="border-bottom:1px solid var(--a-line)">
                    <td style="padding:7px 10px 7px 0;color:#6b7794;width:140px;white-space:nowrap">{{ $k }}</td>
                    <td style="padding:7px 0;{{ str_starts_with((string) $v, '오류') ? 'color:#e0322d' : '' }}">{{ $v }}</td>
                </tr>
            @endforeach
        </table>
        <div class="ahint" style="margin-top:10px">
            이 화면의 버튼은 사이트의 시뮬레이트 설정과 상관없이 <b>팝빌을 실제로 호출</b>합니다.
            @unless($testServer)<b style="color:#e0322d">운영 서버 — 문자는 실제 휴대폰으로, 현금영수증·세금계산서는 국세청으로 갑니다.</b>@endunless
            발행 금액은 최대 {{ number_format($max) }}원입니다.
        </div>
    </div>
</div>

<div style="margin-top:20px">
<div class="utabs" data-tabs="popbill" data-default="sms">
    <button data-tab="sms">문자 보내기</button>
    <button data-tab="cashbill">현금영수증 발행</button>
    <button data-tab="taxinvoice">세금계산서 발행</button>
</div>
<div class="upanel" data-panel="sms">
    <div style="max-width:560px">
    <div class="adm-card">
        <div class="h">문자 보내기</div>
        <form method="POST" action="{{ route('admin.popbill.sms') }}" style="padding:20px" onsubmit="return confirm('문자를 실제로 보냅니다. 계속할까요?')">
            @csrf
            <div class="afield"><label>받는 번호</label><input type="text" name="to" class="ainput" value="{{ old('to', '010-5799-0084') }}" required></div>
            <div class="afield"><label>내용 <span class="muted" style="font-weight:400">(90바이트 넘으면 LMS)</span></label>
                <textarea name="text" class="ainput" rows="3" required>{{ old('text', '[메디셀] 팝빌 문자 발송 테스트입니다.') }}</textarea></div>
            <button class="abtn abtn-pri" style="width:100%;justify-content:center">문자 보내기</button>
        </form>
    </div>
    </div>
</div>
<div class="upanel" data-panel="cashbill">
    <div style="max-width:560px">
    <div class="adm-card">
        <div class="h">현금영수증 발행</div>
        <form method="POST" action="{{ route('admin.popbill.cashbill') }}" style="padding:20px" onsubmit="return confirm('현금영수증을 실제로 발행합니다. 테스트 후 아래 목록에서 [취소]를 눌러 주세요. 계속할까요?')">
            @csrf
            <div class="afield"><label>금액(부가세 포함)</label><input type="number" name="amount" class="ainput" value="{{ old('amount', 1100) }}" min="100" max="{{ $max }}" required></div>
            <div class="afield"><label>용도</label>
                <select name="usage" class="aselect">
                    <option value="소득공제용">소득공제용 (휴대폰번호)</option>
                    <option value="지출증빙용">지출증빙용 (사업자번호)</option>
                </select></div>
            <div class="afield"><label>휴대폰 / 사업자번호</label><input type="text" name="identity" class="ainput" value="{{ old('identity', '010-5799-0084') }}" required></div>
            <button class="abtn abtn-pri" style="width:100%;justify-content:center">현금영수증 발행</button>
        </form>
    </div>
    </div>
</div>
<div class="upanel" data-panel="taxinvoice">
    <div style="max-width:560px">
    <div class="adm-card">
        <div class="h">세금계산서 발행</div>
        <form method="POST" action="{{ route('admin.popbill.taxinvoice') }}" style="padding:20px" onsubmit="return confirm('세금계산서를 실제로 발행합니다. 오늘 안에 아래 목록에서 [취소]를 누르면 국세청에 남지 않습니다. 계속할까요?')">
            @csrf
            <div class="afield"><label>금액(부가세 포함)</label><input type="number" name="amount" class="ainput" value="{{ old('amount', 1100) }}" min="100" max="{{ $max }}" required></div>
            <div class="afield"><label>공급받는자 사업자번호 <span class="muted" style="font-weight:400">(비우면 발행 사업자 자신)</span></label>
                <input type="text" name="corp_num" class="ainput" value="{{ old('corp_num') }}" placeholder="{{ config('popbill.corp_num') }}"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <div class="afield"><label>상호</label><input type="text" name="corp_name" class="ainput" value="{{ old('corp_name') }}" placeholder="{{ config('popbill.supplier.corp_name') }}"></div>
                <div class="afield"><label>대표자</label><input type="text" name="ceo_name" class="ainput" value="{{ old('ceo_name') }}"></div>
            </div>
            <div class="afield"><label>받는 이메일</label><input type="email" name="email" class="ainput" value="{{ old('email') }}" placeholder="{{ config('popbill.supplier.email') }}"></div>
            <button class="abtn abtn-pri" style="width:100%;justify-content:center">세금계산서 발행</button>
        </form>
    </div>
    </div>
</div>
</div>

<div class="adm-card" style="margin-top:20px">
    <div class="h">테스트 기록 (최근 50건)</div>
    <div style="padding:0 20px 10px;overflow-x:auto">
        <table class="atable" style="width:100%;font-size:13px">
            <thead><tr><th>일시</th><th>종류</th><th>서버</th><th>받는 곳</th><th>금액</th><th>번호</th><th>상태</th><th></th></tr></thead>
            <tbody>
            @forelse($tests as $t)
                <tr>
                    <td style="white-space:nowrap">{{ $t->created_at->format('m.d H:i') }}</td>
                    <td>{{ $t->kindLabel() }}@if($t->usage)<br><span class="muted" style="font-size:12px">{{ $t->usage }}</span>@endif</td>
                    <td>{{ $t->is_test_server ? '테스트' : '운영' }}</td>
                    <td>{{ $t->receiver }}@if($t->receiver_name)<br><span class="muted" style="font-size:12px">{{ $t->receiver_name }}</span>@endif</td>
                    <td>{{ $t->amount ? number_format($t->amount).'원' : '-' }}</td>
                    <td style="font-size:12px">{{ $t->confirm_num ?? '-' }}@if($t->content)<br><span class="muted">{{ \Illuminate\Support\Str::limit($t->content, 30) }}</span>@endif</td>
                    <td>
                        <span class="pill {{ $t->status==='failed' ? 'pill-n' : ($t->status==='cancelled' ? 'pill-n' : 'pill-y') }}">{{ $t->statusLabel() }}</span>
                        @if($t->popbill_state)<br><span class="muted" style="font-size:11.5px">{{ $t->popbill_state }}</span>@endif
                        @if($t->error_message)<br><span style="color:#e0322d;font-size:11.5px">{{ $t->error_message }}</span>@endif
                    </td>
                    <td>
                        @if($t->cancellable())
                            <form method="POST" action="{{ route('admin.popbill.cancel', $t) }}" onsubmit="return confirm('{{ $t->kindLabel() }}를 취소합니다. 계속할까요?')">
                                @csrf
                                <button class="abtn abtn-red abtn-sm">취소</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="muted" style="text-align:center;padding:20px">아직 테스트 기록이 없습니다.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
