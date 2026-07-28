@extends('layouts.app')
@section('title', '계정 삭제 요청 — '.($site['name'] ?? '메디셀'))

@section('content')
<div class="page-head"><div class="container"><h1>계정 삭제 요청</h1></div></div>
<div class="container legal">
    @include('legal._style')

    <div class="meta">
        {{ $site['name'] ?? '메디셀' }} 계정과 개인정보 삭제를 요청하는 페이지입니다. 로그인 없이 요청할 수 있습니다.
    </div>

    @if(session('ok'))
        <div class="box" style="border-color:#86c79a;background:#f0fdf4">
            <b>{{ session('ok') }}</b>
        </div>
    @endif

    <h2>삭제되는 정보</h2>
    <ul>
        <li>회원 계정 정보 — 이름, 이메일, 비밀번호, 연락처, 주소</li>
        <li>사업자회원 정보 — 상호(기관명), 사업자등록번호, 대표자명, 업태·종목</li>
        <li>배송지 목록, 장바구니, 찜한 상품</li>
        <li>보유 적립금 및 미사용 쿠폰</li>
        <li>작성한 상품 후기 및 1:1 문의 내역</li>
    </ul>

    <h2>보관되는 정보</h2>
    <p>
        아래 정보는 관련 법령에 따른 보존 의무가 있어 계정 삭제 후에도 정해진 기간 동안 분리 보관됩니다.
        보존 목적 외의 용도로는 이용되지 않으며, 기간이 지나면 지체 없이 파기합니다.
    </p>
    <table>
        <thead><tr><th style="width:60%">보관 항목</th><th style="width:90px">기간</th><th>근거</th></tr></thead>
        <tbody>
        <tr><td>주문·결제·배송 및 청약철회에 관한 기록</td><td>5년</td><td>전자상거래법<br>전자금융거래법</td></tr>
        <tr><td>소비자 불만 및 분쟁처리에 관한 기록</td><td>3년</td><td>전자상거래법</td></tr>
        <tr><td>세금계산서 등 거래 증빙</td><td>5년</td><td>국세기본법</td></tr>
        <tr><td>로그인 접속 기록</td><td>3개월</td><td>통신비밀보호법</td></tr>
        </tbody>
    </table>

    <div class="box warn">
        <b>삭제된 계정은 복구할 수 없습니다.</b><br>
        적립금·쿠폰은 환급되지 않고 소멸하며, 작성한 후기도 함께 삭제됩니다.
        진행 중인 주문(배송 전·배송 중·환불 처리 중)이 있는 경우에는 해당 거래가 완료된 뒤에 삭제가 진행됩니다.
    </div>

    <h2>요청 방법</h2>
    <p>
        아래 양식을 작성해 주시면 <b>영업일 기준 3일 이내</b>에 처리하고 결과를 이메일로 안내해 드립니다.
        본인 확인을 위해 <b>가입하신 이메일 주소</b>를 정확히 입력해 주세요.
        앱에서는 <b>마이페이지 &gt; 계정 삭제</b> 메뉴를 통해서도 요청할 수 있습니다.
    </p>

    <form method="POST" action="{{ route('legal.account-deletion.store') }}" class="form-card" style="padding:26px 24px;margin-top:16px">
        @csrf
        <div class="field">
            <label>이름 <span style="color:#e0322d">*</span></label>
            <input type="text" name="name" class="input" value="{{ old('name', auth()->user()->name ?? '') }}" required>
            @error('name')<div class="err" style="color:#e0322d;font-size:13px;margin-top:5px">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label>가입 이메일 <span style="color:#e0322d">*</span></label>
            <input type="email" name="email" class="input" value="{{ old('email', auth()->user()->email ?? '') }}" required>
            @error('email')<div class="err" style="color:#e0322d;font-size:13px;margin-top:5px">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label>연락처 <span class="muted" style="font-weight:400">(선택 — 본인 확인이 필요할 때 사용합니다)</span></label>
            <input type="text" name="phone" class="input" value="{{ old('phone', auth()->user()->phone ?? '') }}">
            @error('phone')<div class="err" style="color:#e0322d;font-size:13px;margin-top:5px">{{ $message }}</div>@enderror
        </div>
        <div class="field">
            <label>삭제 사유 <span class="muted" style="font-weight:400">(선택)</span></label>
            <input type="text" name="reason" class="input" value="{{ old('reason') }}" maxlength="200" placeholder="서비스 개선에 참고하겠습니다.">
            @error('reason')<div class="err" style="color:#e0322d;font-size:13px;margin-top:5px">{{ $message }}</div>@enderror
        </div>
        <label class="inline" style="font-size:13.5px;margin:4px 0 16px;line-height:1.6">
            <input type="checkbox" name="confirm" value="1" {{ old('confirm') ? 'checked' : '' }}>
            계정과 적립금·쿠폰·후기가 삭제되며 복구할 수 없다는 점에 동의합니다.
        </label>
        @error('confirm')<div class="err" style="color:#e0322d;font-size:13px;margin:-10px 0 14px">{{ $message }}</div>@enderror
        <button class="btn btn-primary btn-lg btn-block">계정 삭제 요청하기</button>
    </form>

    <h2>문의</h2>
    <table>
        <tbody>
        <tr><th style="width:150px">운영사</th><td>{{ $site['company'] ?? '메디셀' }}</td></tr>
        <tr><th>이메일</th><td>{{ $site['email'] ?? '-' }}</td></tr>
        <tr><th>고객센터</th><td>{{ $site['cs_tel'] ?? '-' }}<br><span class="muted" style="font-size:12.5px">{{ $site['cs_hours'] ?? '' }}</span></td></tr>
        </tbody>
    </table>

    <p class="muted" style="font-size:12.5px;margin-top:14px">
        개인정보 처리에 관한 자세한 내용은 <a href="{{ route('legal.privacy') }}">개인정보처리방침</a>을 확인해 주세요.
    </p>
</div>
@endsection
