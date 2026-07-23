@extends('layouts.admin')
@section('title', $account->exists ? '거래처 수정' : '거래처 등록')
@section('heading', $account->exists ? '거래처: '.$account->name : '거래처 등록')

@section('content')
@if(session('ok'))<div class="alert alert-ok" style="margin-bottom:14px">{{ session('ok') }}</div>@endif
@if(session('error'))<div class="alert alert-red" style="margin-bottom:14px">{{ session('error') }}</div>@endif
@if($errors->any())<div class="alert alert-red" style="margin-bottom:14px">{{ $errors->first() }}</div>@endif

<div style="margin-bottom:14px"><a href="{{ route('admin.accounts.index') }}" class="abtn abtn-ghost abtn-sm">← 거래처 목록</a></div>

{{-- 기본 정보 --}}
<div class="adm-card" style="padding:20px 22px;margin-bottom:16px">
    <div class="h" style="margin-bottom:14px">기본 정보</div>
    <form method="POST" action="{{ $account->exists ? route('admin.accounts.update', $account) : route('admin.accounts.store') }}">
        @csrf @if($account->exists)@method('PUT')@endif
        <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:12px">
            <div class="afield" style="margin:0"><label>거래처명 <span class="req">*</span></label><input type="text" name="name" class="ainput" value="{{ old('name', $account->name) }}" placeholder="예) 메디셀병원 / (주)메디셀상사" required></div>
            <div class="afield" style="margin:0"><label>거래처 코드</label><input type="text" name="code" class="ainput" value="{{ old('code', $account->code) }}" placeholder="선택"></div>
            <div class="afield" style="margin:0"><label>등급 일괄 할인율(%)</label><input type="number" name="discount_rate" class="ainput" step="0.1" min="0" max="100" value="{{ old('discount_rate', rtrim(rtrim(number_format($account->discount_rate ?? 0, 2, '.', ''), '0'), '.')) }}" placeholder="예: 15"></div>
        </div>
        <div class="afield" style="margin-top:12px"><label>메모</label><input type="text" name="memo" class="ainput" value="{{ old('memo', $account->memo) }}" placeholder="거래 조건 등"></div>
        <label class="acheck" style="display:flex;align-items:center;gap:6px;margin-top:10px">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $account->is_active ?? true) ? 'checked' : '' }}> 활성 (비활성 시 전용가/할인 미적용)
        </label>
        <div class="ahint" style="margin-top:8px">가격 우선순위: <b>회원 개별 전용가 → 거래처 전용가 → 거래처 할인율 → 공통 회원가 → 정가</b></div>
        <button class="abtn abtn-pri" style="width:100%;justify-content:center;margin-top:14px">{{ $account->exists ? '정보 저장' : '거래처 등록' }}</button>
    </form>
</div>

@if($account->exists)
{{-- 소속 회원 --}}
<div class="adm-card" style="padding:20px 22px;margin-bottom:16px">
    <div class="h" style="margin-bottom:12px">소속 회원 <span class="pill pill-b">{{ $members->count() }}명</span></div>
    <form method="POST" action="{{ route('admin.accounts.members.attach', $account) }}" style="display:flex;gap:10px;align-items:flex-end;margin-bottom:14px;flex-wrap:wrap">
        @csrf
        <div class="afield" style="margin:0;flex:1;min-width:220px"><label>회원 이메일로 배정</label><input type="email" name="email" class="ainput" placeholder="member@example.com" required></div>
        <button class="abtn abtn-pri">배정</button>
    </form>
    @if($members->count())
        <table class="atable">
            <thead><tr><th>이름</th><th>이메일</th><th>구분</th><th>승인</th><th></th></tr></thead>
            <tbody>
            @foreach($members as $m)
                <tr>
                    <td>{{ $m->name }}</td>
                    <td>{{ $m->email }}</td>
                    <td>{{ $m->member_type==='business' ? '병원/기업' : '일반' }}</td>
                    <td>@if($m->biz_status==='approved')<span class="status-pill st-done">승인</span>@else<span class="status-pill st-pending">{{ $m->biz_status ?: '미승인' }}</span>@endif</td>
                    <td style="text-align:right">
                        <form method="POST" action="{{ route('admin.accounts.members.detach', [$account, $m]) }}" onsubmit="return confirm('배정을 해제할까요?')">@csrf @method('DELETE')<button class="abtn abtn-ghost abtn-sm">해제</button></form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="ahint" style="margin-top:8px">※ 전용가는 <b>승인된 병원/기업 회원</b>에게만 적용됩니다. (미승인 회원은 정가)</div>
    @else
        <p class="muted" style="margin:0">배정된 회원이 없습니다. 위에서 이메일로 배정하세요.</p>
    @endif
</div>

{{-- 거래처 전용가 --}}
<div class="adm-card" style="padding:20px 22px">
    <div class="h" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
        <span>거래처 제품 전용가 <span class="pill pill-b">{{ $prices->count() }}개</span></span>
        <span style="display:flex;gap:6px">
            <a href="{{ route('admin.accounts.prices.export', $account) }}" class="abtn abtn-ghost abtn-sm"><x-icon name="doc" :size="14"/> 양식 다운로드</a>
        </span>
    </div>
    <form method="POST" action="{{ route('admin.accounts.prices.import', $account) }}" enctype="multipart/form-data" style="display:flex;gap:10px;align-items:center;margin-bottom:8px;flex-wrap:wrap">
        @csrf
        <input type="file" name="file" accept=".csv,text/csv" class="ainput" style="flex:1;min-width:220px;padding:7px" required>
        <button class="abtn abtn-pri">CSV 일괄 업로드</button>
    </form>
    <div class="ahint" style="margin:-2px 0 16px">「양식 다운로드」 CSV의 <b>전용가</b> 열만 수정해 업로드하세요. 값이 비면 해당 제품 전용가가 해제됩니다.</div>

    <form method="POST" action="{{ route('admin.accounts.prices.store', $account) }}" style="display:flex;gap:10px;align-items:flex-end;margin-bottom:18px;flex-wrap:wrap">
        @csrf
        <div class="afield" style="margin:0;flex:2;min-width:240px"><label>제품</label>
            <select name="product_id" class="aselect" required>
                <option value="">— 제품 선택 —</option>
                @foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }} (정가 {{ number_format($p->price) }}원)</option>@endforeach
            </select>
        </div>
        <div class="afield" style="margin:0;width:160px"><label>거래처 전용가(원)</label><input type="number" name="price" class="ainput" min="0" required></div>
        <button class="abtn abtn-pri">추가</button>
    </form>

    @if($prices->count())
        <table class="atable">
            <thead><tr><th>제품</th><th style="text-align:right">정가</th><th style="text-align:right">거래처 전용가</th><th style="text-align:right">할인율</th><th></th></tr></thead>
            <tbody>
            @foreach($prices as $ap)
                <tr>
                    <td>{{ $ap->product->name }}</td>
                    <td style="text-align:right">{{ number_format($ap->product->price) }}원</td>
                    <td style="text-align:right"><b>{{ number_format($ap->price) }}원</b></td>
                    <td style="text-align:right">{{ $ap->product->price > 0 ? round(($ap->product->price-$ap->price)/$ap->product->price*100) : 0 }}%</td>
                    <td style="text-align:right"><form method="POST" action="{{ route('admin.accounts.prices.destroy', [$account, $ap]) }}" onsubmit="return confirm('삭제할까요?')">@csrf @method('DELETE')<button class="abtn abtn-ghost abtn-sm">삭제</button></form></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <p class="muted" style="margin:0">등록된 거래처 전용가가 없습니다. 개별 추가 또는 CSV로 등록하세요. (할인율만 설정해도 전 제품에 일괄 적용됩니다)</p>
    @endif
</div>

<div style="margin-top:16px;text-align:right">
    <form method="POST" action="{{ route('admin.accounts.destroy', $account) }}" onsubmit="return confirm('거래처를 삭제할까요? 소속 회원은 유지되고 배정만 해제됩니다.')">
        @csrf @method('DELETE')<button class="abtn abtn-red abtn-sm">거래처 삭제</button>
    </form>
</div>
@endif
@endsection
