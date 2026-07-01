@extends('layouts.app')
@section('title', '회원정보수정 — 메디셀')

@section('content')
<div class="page-head"><div class="container"><h1>회원정보수정</h1></div></div>
<div class="container" style="padding-top:26px">
    <div class="my-layout">
        @include('partials.mynav')
        <div>
            <form method="POST" action="{{ route('mypage.profile.update') }}" class="form-card">
                @csrf @method('PUT')
                <h3><x-icon name="user"/> 기본정보</h3>
                <div class="row2">
                    <div class="field"><label>이름 <span class="req">*</span></label><input type="text" name="name" class="input" value="{{ old('name', $user->name) }}" required></div>
                    <div class="field"><label>이메일</label><input type="email" class="input" value="{{ $user->email }}" disabled></div>
                </div>
                <div class="field"><label>연락처</label><input type="text" name="phone" class="input" value="{{ old('phone', $user->phone) }}"></div>

                @if($user->member_type==='business')
                    <div style="background:var(--navy-50);border-radius:10px;padding:14px;margin-bottom:16px;font-size:13.5px">
                        <b style="color:var(--navy-800)">병원정보</b> · {{ $user->company_name }} ({{ $user->biz_no }})
                        @if($user->biz_status==='approved')<span class="badge badge-new">승인완료</span>@else<span class="badge badge-hot">승인대기</span>@endif
                    </div>
                @endif

                <h3 style="margin-top:24px"><x-icon name="pin"/> 기본 배송지</h3>
                <div class="field" style="max-width:200px"><label>우편번호</label><input type="text" name="postcode" class="input" value="{{ old('postcode', $user->postcode) }}"></div>
                <div class="field"><label>주소</label><input type="text" name="address1" class="input" value="{{ old('address1', $user->address1) }}"></div>
                <div class="field"><label>상세주소</label><input type="text" name="address2" class="input" value="{{ old('address2', $user->address2) }}"></div>

                <h3 style="margin-top:24px"><x-icon name="shield"/> 비밀번호 변경 (선택)</h3>
                <div class="row2">
                    <div class="field"><label>새 비밀번호</label><input type="password" name="password" class="input" placeholder="변경 시에만 입력"></div>
                    <div class="field"><label>비밀번호 확인</label><input type="password" name="password_confirmation" class="input"></div>
                </div>

                <button class="btn btn-primary btn-lg">저장하기</button>
            </form>
        </div>
    </div>
</div>
@endsection
