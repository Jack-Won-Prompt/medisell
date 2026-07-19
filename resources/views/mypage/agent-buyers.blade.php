@extends('layouts.app')
@section('title', '대행 구매자 관리 — 메디셀')

@section('content')
<div class="page-head"><div class="container"><h1>대행 구매자 관리</h1></div></div>
<div class="container" style="padding-top:26px">
    <div class="my-layout">
        @include('partials.mynav')
        <div>
            @if(session('ok'))<div class="alert alert-ok" style="margin-bottom:14px">{{ session('ok') }}</div>@endif
            @if($errors->any())<div class="alert alert-red" style="margin-bottom:14px">{{ $errors->first() }}</div>@endif

            {{-- 캐쉬백 요약 --}}
            <div class="form-card" style="margin-bottom:14px;display:flex;gap:24px;flex-wrap:wrap;align-items:center">
                <div><div class="muted" style="font-size:13px">캐쉬백 비율</div><b style="font-size:18px">{{ rtrim(rtrim(number_format($user->cashback_rate, 2), '0'), '.') }}%</b></div>
                <div><div class="muted" style="font-size:13px">정산 대기</div><b style="font-size:18px;color:var(--navy-700)">{{ number_format($pending) }}원</b></div>
                <div><div class="muted" style="font-size:13px">정산 완료</div><b style="font-size:18px">{{ number_format($paid) }}원</b></div>
                <a href="{{ route('mypage.agent.cashbacks') }}" class="btn btn-ghost btn-sm" style="margin-left:auto">캐쉬백 내역 →</a>
            </div>

            {{-- 등록된 구매자 목록 --}}
            @forelse($buyers as $b)
                <div class="form-card" style="margin-bottom:14px">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px">
                        <div>
                            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                                <b>{{ $b->hospital_name }}</b>
                                @unless($b->is_active)<span class="badge">비활성</span>@endunless
                            </div>
                            <div>{{ $b->buyer_name }} · {{ $b->buyer_phone }}</div>
                        </div>
                        <div style="display:flex;gap:6px;flex:none">
                            <button type="button" class="btn btn-ghost btn-sm" onclick="toggleEdit({{ $b->id }})">수정</button>
                            <form method="POST" action="{{ route('mypage.agent.buyer.delete', $b) }}" onsubmit="return confirm('삭제하시겠습니까?')">
                                @csrf @method('DELETE')<button class="btn btn-red btn-sm">삭제</button>
                            </form>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('mypage.agent.buyer.update', $b) }}" id="edit-{{ $b->id }}" style="display:none;margin-top:14px;border-top:1px solid var(--line);padding-top:14px">
                        @csrf @method('PUT')
                        <div class="field"><label>병원명 <span class="req">*</span></label><input type="text" name="hospital_name" class="input" value="{{ $b->hospital_name }}" required></div>
                        <div class="field"><label>구매자 이름 <span class="req">*</span></label><input type="text" name="buyer_name" class="input" value="{{ $b->buyer_name }}" required></div>
                        <div class="field"><label>구매자 전화번호 <span class="req">*</span></label><input type="text" name="buyer_phone" class="input" value="{{ $b->buyer_phone }}" required></div>
                        <label class="acheck" style="display:flex;align-items:center;gap:6px;margin-top:2px"><input type="checkbox" name="is_active" value="1" {{ $b->is_active ? 'checked' : '' }}> 활성(주문 시 선택 가능)</label>
                        <div style="margin-top:12px"><button class="btn btn-primary">수정 저장</button></div>
                    </form>
                </div>
            @empty
                <div class="form-card muted" style="margin-bottom:14px">등록된 구매자가 없습니다. 아래에서 추가하세요.</div>
            @endforelse

            {{-- 새 구매자 등록 --}}
            <form method="POST" action="{{ route('mypage.agent.buyer.store') }}" class="form-card">
                @csrf
                <h3><x-icon name="user"/> 새 구매자 등록</h3>
                <div class="field"><label>병원명 <span class="req">*</span></label><input type="text" name="hospital_name" class="input" value="{{ old('hospital_name') }}" placeholder="예) 메디셀의원" required></div>
                <div class="field"><label>구매자 이름 <span class="req">*</span></label><input type="text" name="buyer_name" class="input" value="{{ old('buyer_name') }}" placeholder="예) 홍길동" required></div>
                <div class="field"><label>구매자 전화번호 <span class="req">*</span></label><input type="text" name="buyer_phone" class="input" value="{{ old('buyer_phone') }}" placeholder="예) 010-1234-5678" required></div>
                <div style="margin-top:14px"><button class="btn btn-primary">구매자 등록</button></div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleEdit(id) {
    var f = document.getElementById('edit-' + id);
    f.style.display = (f.style.display === 'none' || !f.style.display) ? 'block' : 'none';
}
</script>
@endpush
