@extends('layouts.admin')
@section('title', '회원상세')
@section('heading', '회원상세 · '.$user->name)

@section('content')
@php
    $isBiz = $user->member_type === 'business';
@endphp

{{-- 요약 --}}
<div class="adm-card" style="padding:16px 20px;margin-bottom:16px;display:flex;flex-wrap:wrap;gap:10px 18px;align-items:center;font-size:13.5px">
    <b style="font-size:16px">{{ $user->name }}</b>
    <span class="muted">{{ $user->email }}</span>
    <span class="pill {{ $isBiz ? 'pill-b' : 'pill-n' }}">{{ $isBiz ? '병원 회원' : '일반 회원' }}</span>
    @if($isBiz)
        @if($user->biz_status==='approved')<span class="pill pill-y">승인</span>
        @elseif($user->biz_status==='pending')<span class="pill pill-w">승인 대기</span>
        @elseif($user->biz_status==='rejected')<span class="pill pill-n">거절</span>@endif
        @if($user->company_name)<span>{{ $user->company_name }}</span>@endif
    @endif
    @if($user->is_admin)<span class="pill pill-b">관리자</span>@endif
    <span class="muted" style="margin-left:auto">적립금 {{ number_format($user->point) }}원 · 주문 {{ $user->orders_count }}건 · 가입 {{ $user->created_at->format('Y.m.d') }}</span>
    <a href="{{ route('admin.users.index') }}" class="abtn abtn-ghost abtn-sm">목록으로</a>
</div>

{{-- 탭 --}}
<div class="utabs" role="tablist" data-tabs="user" data-default="{{ $isBiz && $user->biz_status === 'pending' ? 'approve' : 'info' }}">
    <button data-tab="info" role="tab">회원 정보</button>
    <button data-tab="approve" role="tab">승인 · 등급 @if($isBiz && $user->biz_status==='pending')<span class="dot" title="승인 대기"></span>@endif</button>
    <button data-tab="edit" role="tab">정보 수정</button>
    <button data-tab="account" role="tab">계정 관리</button>
    @if($isBiz)<button data-tab="prices" role="tab">개별 전용가 <span class="muted" style="font-weight:400">{{ $prices->count() }}</span></button>@endif
</div>

{{-- 1. 회원 정보 --}}
<div class="upanel" data-panel="info">
    <div class="ugrid">
        <div class="adm-card">
            <div class="h">회원 정보</div>
            <div style="padding:18px 20px;font-size:14px;line-height:2.1">
                <b>이름</b> {{ $user->name }}<br>
                <b>이메일</b> {{ $user->email }}<br>
                <b>연락처</b> {{ $user->phone ?? '-' }}<br>
                <b>회원구분</b> {{ $isBiz ? '병원 회원' : '일반 회원' }}<br>
                <b>주소</b> ({{ $user->postcode }}) {{ $user->address1 }} {{ $user->address2 }}<br>
                <b>적립금</b> {{ number_format($user->point) }}원<br>
                <b>주문수</b> {{ $user->orders_count }}건<br>
                <b>가입일</b> {{ $user->created_at->format('Y.m.d H:i') }}
            </div>
        </div>
        <div class="adm-card">
            <div class="h">적립금 수동조정</div>
            <div style="padding:20px">
                <div style="font-size:13px;margin-bottom:10px">보유 <b>{{ number_format($user->point) }}원</b></div>
                <form method="POST" action="{{ route('admin.users.points', $user) }}" style="display:flex;gap:8px;align-items:flex-end">
                    @csrf
                    <div class="afield" style="width:130px;margin:0"><label>금액(±)</label><input type="number" name="amount" class="ainput" placeholder="예: 1000 / -500" required></div>
                    <div class="afield" style="flex:1;margin:0"><label>사유</label><input type="text" name="reason" class="ainput" placeholder="적립/차감 사유" required></div>
                    <button class="abtn abtn-pri">반영</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- 2. 승인 · 등급 --}}
<div class="upanel" data-panel="approve">
    <div class="ugrid">
        <div class="adm-card">
            <div class="h">사업자 정보 · 사업자등록증</div>
            <div style="padding:20px">
                @if($isBiz)
                    <div style="background:#f7f9fc;border-radius:10px;padding:14px;margin-bottom:16px;font-size:13.5px;line-height:1.9">
                        <b>병원/상호</b> {{ $user->company_name }}<br>
                        <b>사업자번호</b> {{ $user->biz_no }}<br>
                        <b>종별</b> {{ $user->biz_type ?? '-' }}<br>
                        <b>요양기관기호</b> {{ $user->care_code ?: '-' }}
                        @if($user->company_name)
                            <a href="https://www.hira.or.kr" target="_blank" rel="noopener" style="font-size:12px;margin-left:6px">심평원(병원찾기) ↗</a>
                        @endif
                    </div>

                    {{-- 사업자등록증 (승인 근거) --}}
                    <div style="border:1px solid #e5e9f2;border-radius:10px;padding:12px">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                            <b style="font-size:13.5px">사업자등록증</b>
                            @if($user->biz_cert_path)
                                <a href="{{ route('admin.users.biz-cert', $user) }}" target="_blank" class="abtn abtn-ghost" style="padding:4px 10px;font-size:12px">새 창으로 크게 보기</a>
                            @endif
                        </div>
                        @if($user->biz_cert_path)
                            @if(str_ends_with(strtolower($user->biz_cert_path), '.pdf'))
                                <iframe src="{{ route('admin.users.biz-cert', $user) }}" style="width:100%;height:480px;border:1px solid #eef1f6;border-radius:6px" title="사업자등록증"></iframe>
                            @else
                                <a href="{{ route('admin.users.biz-cert', $user) }}" target="_blank"><img src="{{ route('admin.users.biz-cert', $user) }}" alt="사업자등록증" style="width:100%;max-height:480px;object-fit:contain;background:#f7f9fc;border-radius:6px"></a>
                            @endif
                        @else
                            <p class="muted" style="font-size:13px;margin:0 0 8px">첨부된 사업자등록증이 없습니다. (앱 가입 등) 서류를 받으셨다면 아래에서 올려 주세요.</p>
                        @endif
                        <form method="POST" action="{{ route('admin.users.biz-cert.upload', $user) }}" enctype="multipart/form-data" style="display:flex;gap:8px;margin-top:10px">
                            @csrf
                            <input type="file" name="biz_cert" class="ainput" accept="image/jpeg,image/png,image/webp,application/pdf" required style="flex:1">
                            <button class="abtn abtn-ghost">{{ $user->biz_cert_path ? '교체' : '올리기' }}</button>
                        </form>
                    </div>
                @else
                    <p class="muted" style="margin:0;font-size:13.5px">일반 회원입니다. 승인 및 전용가는 병원·기업(사업자) 회원에만 적용됩니다.</p>
                @endif
            </div>
        </div>

        <div class="adm-card">
            <div class="h">거래처(병원·기업) 승인 / 등급</div>
            <div style="padding:20px">
                <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                    @csrf @method('PUT')
                    <div class="afield">
                        <label>거래처 승인상태</label>
                        <select name="biz_status" class="aselect">
                            @foreach(['pending'=>'승인대기','approved'=>'승인','rejected'=>'거절'] as $k=>$v)
                                <option value="{{ $k }}" {{ $user->biz_status===$k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                        <div class="ahint">승인·거절로 바꾸면 회원에게 안내 문자가 갑니다.</div>
                    </div>
                    <div class="afield">
                        <label>회원 등급</label>
                        <select name="grade" class="aselect">
                            @foreach(['basic'=>'일반','silver'=>'실버','gold'=>'골드'] as $k=>$v)
                                <option value="{{ $k }}" {{ $user->grade===$k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                        <div class="ahint">'승인' 병원·기업 회원에게 적용. 거래처 단가보다 우선합니다.</div>
                    </div>
                    <button class="abtn abtn-pri" style="width:100%;justify-content:center">저장</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- 3. 정보 수정 --}}
<div class="upanel" data-panel="edit">
    <div class="adm-card" style="max-width:820px">
        <div class="h">회원 정보 수정</div>
        <div style="padding:20px">
            <form method="POST" action="{{ route('admin.users.update', $user) }}">
                @csrf @method('PUT')
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="afield" style="margin:0"><label>이름</label><input type="text" name="name" class="ainput" value="{{ old('name', $user->name) }}" required></div>
                    <div class="afield" style="margin:0"><label>연락처</label><input type="text" name="phone" class="ainput" value="{{ old('phone', $user->phone) }}"></div>
                </div>
                <div class="afield" style="margin-top:12px"><label>이메일</label><input type="email" name="email" class="ainput" value="{{ old('email', $user->email) }}" required></div>
                <div class="afield">
                    <label>회원구분</label>
                    <select name="member_type" class="aselect">
                        <option value="general" {{ $user->member_type==='general'?'selected':'' }}>일반</option>
                        <option value="business" {{ $user->member_type==='business'?'selected':'' }}>병원</option>
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="afield" style="margin:0"><label>병원/상호</label><input type="text" name="company_name" class="ainput" value="{{ old('company_name', $user->company_name) }}"></div>
                    <div class="afield" style="margin:0"><label>사업자번호</label><input type="text" name="biz_no" class="ainput" value="{{ old('biz_no', $user->biz_no) }}"></div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px">
                    <div class="afield" style="margin:0"><label>대표자명(계산서용)</label><input type="text" name="biz_ceo" class="ainput" value="{{ old('biz_ceo', $user->biz_ceo) }}"></div>
                    <div class="afield" style="margin:0"><label>종별</label><input type="text" name="biz_type" class="ainput" value="{{ old('biz_type', $user->biz_type) }}"></div>
                </div>
                <div class="afield" style="margin-top:12px"><label>요양기관기호</label><input type="text" name="care_code" class="ainput" value="{{ old('care_code', $user->care_code) }}" maxlength="20"></div>
                {{-- 소속 거래처 (전용가/할인 그룹) --}}
                <div style="border-top:1px solid var(--line,#e5e7eb);margin-top:14px;padding-top:12px">
                    <div class="afield" style="margin:0"><label>소속 거래처 (전용가·할인 그룹)</label>
                        <select name="account_id" class="aselect">
                            <option value="">— 없음 —</option>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}" {{ (string) old('account_id', $user->account_id) === (string) $acc->id ? 'selected' : '' }}>{{ $acc->name }}</option>
                            @endforeach
                        </select>
                        <div class="ahint" style="margin-top:4px">거래처에 배정하면 <a href="{{ route('admin.accounts.index') }}" style="color:var(--a-navy)">거래처 단가</a>가 이 회원에게 적용됩니다.</div>
                    </div>
                </div>

                {{-- 구매 대행자 --}}
                <div style="border-top:1px solid var(--line,#e5e7eb);margin-top:14px;padding-top:12px">
                    <label class="acheck" style="display:flex;align-items:center;gap:6px;margin-bottom:10px">
                        <input type="hidden" name="is_agent" value="0">
                        <input type="checkbox" name="is_agent" value="1" {{ $user->is_agent ? 'checked' : '' }}> <b>구매 대행자로 지정</b>
                    </label>
                    <div class="afield" style="margin:0"><label>캐쉬백 비율(%) — 주문 총액 대비</label>
                        <input type="number" name="cashback_rate" class="ainput" step="0.1" min="0" max="100" value="{{ old('cashback_rate', rtrim(rtrim(number_format($user->cashback_rate, 2, '.', ''), '0'), '.')) }}" placeholder="예: 3">
                    </div>
                </div>
                <button class="abtn abtn-pri" style="width:100%;justify-content:center;margin-top:12px">정보 저장</button>
            </form>
        </div>
    </div>
</div>

{{-- 4. 계정 관리 --}}
<div class="upanel" data-panel="account">
    <div class="adm-card" style="max-width:820px">
        <div class="h">계정 관리</div>
        <div style="padding:20px">
            {{-- 비밀번호 초기화 --}}
            <form method="POST" action="{{ route('admin.users.reset', $user) }}">
                @csrf
                <div class="afield"><label>비밀번호 초기화 (8자 이상)</label>
                    <div style="display:flex;gap:8px">
                        <input type="text" name="password" class="ainput" placeholder="새 비밀번호" required minlength="8">
                        <button class="abtn abtn-pri" style="white-space:nowrap">변경</button>
                    </div>
                </div>
            </form>

            <div style="border-top:1px solid var(--a-line);margin:16px 0;padding-top:16px;display:flex;flex-direction:column;gap:10px">
                {{-- 관리자 권한 --}}
                <div style="display:flex;align-items:center;justify-content:space-between">
                    <span style="font-size:13.5px">관리자 권한 @if($user->is_admin)<span class="pill pill-b">관리자</span>@endif</span>
                    <form method="POST" action="{{ route('admin.users.admin', $user) }}">
                        @csrf @method('PUT')
                        <button class="abtn {{ $user->is_admin ? 'abtn-ghost' : 'abtn-pri' }} abtn-sm">{{ $user->is_admin ? '권한 해제' : '관리자 지정' }}</button>
                    </form>
                </div>
                {{-- 강제 탈퇴 --}}
                <div style="display:flex;align-items:center;justify-content:space-between">
                    <span style="font-size:13.5px;color:#e0322d">회원 강제 탈퇴</span>
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('정말 이 회원을 삭제하시겠습니까? 되돌릴 수 없습니다.')">
                        @csrf @method('DELETE')
                        <button class="abtn abtn-red abtn-sm">탈퇴 처리</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- 5. 회원 개별 전용가 (병원 회원만) --}}
@if($isBiz)
<div class="upanel" data-panel="prices">
    <div class="adm-card">
        <div class="h">
            <span>회원 개별 전용가 <span class="pill pill-b">{{ $prices->count() }}개 제품</span></span>
            <span style="display:flex;gap:8px;align-items:center">
                @unless($user->biz_status==='approved')<span class="pill pill-w">승인 후 적용됨</span>@endunless
                <a href="{{ route('admin.users.prices.export', $user) }}" class="abtn abtn-ghost abtn-sm"><x-icon name="doc" :size="14"/> 양식 다운로드</a>
            </span>
        </div>
        <div style="padding:20px">
            {{-- CSV 일괄 업로드 --}}
            <form method="POST" action="{{ route('admin.users.prices.import', $user) }}" enctype="multipart/form-data"
                  style="display:flex;gap:10px;align-items:center;background:#f7f9fc;border:1px dashed #c7cedd;border-radius:10px;padding:12px 14px;margin-bottom:16px">
                @csrf
                <span style="font-size:13px;font-weight:600;color:#33415c">엑셀(CSV) 일괄 등록</span>
                <input type="file" name="file" accept=".csv,text/csv" class="ainput" style="flex:1;padding:7px" required>
                <button class="abtn abtn-pri">업로드</button>
            </form>
            <div class="ahint" style="margin:-8px 0 16px">「양식 다운로드」로 받은 CSV의 <b>전용가</b> 열만 수정해 업로드하세요. 값이 비면 해당 제품 전용가가 해제됩니다.</div>

            {{-- 등록 폼 --}}
            <form method="POST" action="{{ route('admin.users.prices.store', $user) }}" style="display:flex;gap:10px;align-items:flex-end;margin-bottom:18px;flex-wrap:wrap">
                @csrf
                <div class="afield" style="flex:1;min-width:280px;margin:0">
                    <label>제품</label>
                    <select name="product_id" class="aselect" required>
                        <option value="">— 제품 선택 —</option>
                        @foreach($products as $p)
                            <option value="{{ $p->id }}">{{ $p->name }} (정가 {{ number_format($p->price) }}원)</option>
                        @endforeach
                    </select>
                </div>
                <div class="afield" style="width:160px;margin:0">
                    <label>회원 개별 전용가(원)</label>
                    <input type="number" name="price" class="ainput" min="0" required>
                </div>
                <button class="abtn abtn-pri">전용가 등록/수정</button>
            </form>
            <div class="ahint" style="margin:-8px 0 16px">같은 제품을 다시 등록하면 가격이 갱신됩니다. 매핑되지 않은 제품은 기본 병원가(없으면 정가)로 판매됩니다.</div>

            {{-- 목록 --}}
            <table class="atable">
                <thead><tr><th>제품</th><th>상품코드</th><th style="text-align:right">정가</th><th style="text-align:right">개별 전용가</th><th style="text-align:right">할인율</th><th></th></tr></thead>
                <tbody>
                @forelse($prices as $hp)
                    @php
                        $rate = $hp->product->price > 0 ? round(($hp->product->price - $hp->price) / $hp->product->price * 100) : 0;
                    @endphp
                    <tr>
                        <td>{{ $hp->product->name }}</td>
                        <td>{{ $hp->product->code }}</td>
                        <td style="text-align:right">{{ number_format($hp->product->price) }}원</td>
                        <td style="text-align:right"><b style="color:var(--a-navy)">{{ number_format($hp->price) }}원</b></td>
                        <td style="text-align:right">{{ $rate > 0 ? $rate.'%' : '-' }}</td>
                        <td style="text-align:right">
                            <form method="POST" action="{{ route('admin.users.prices.destroy', [$user, $hp]) }}" onsubmit="return confirm('삭제하시겠습니까?')">
                                @csrf @method('DELETE')
                                <button class="abtn abtn-red abtn-sm">삭제</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" style="text-align:center;color:#97a0b8;padding:30px">등록된 회원 개별 전용가가 없습니다.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@endsection
