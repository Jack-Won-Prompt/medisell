@extends('layouts.admin')
@section('title', '푸시 알림')
@section('heading', '푸시 알림 발송')

@section('content')
<div style="max-width:640px">
    @unless($enabled)
        <div class="flash" style="background:#fef3c7;border-color:#fde68a;color:#92400e">
            <x-icon name="close"/> FCM 설정이 완료되지 않았습니다.
            <code>config/fcm.php</code> 의 <b>FCM_PROJECT_ID</b> 와 서비스계정 키(JSON)를 확인하세요.
        </div>
    @endunless

    <div class="card" style="padding:18px;margin-bottom:16px">
        <div style="display:flex;gap:24px">
            <div><div class="muted" style="font-size:12px">등록 기기</div><b style="font-size:20px">{{ number_format($tokenCount) }}</b></div>
            <div><div class="muted" style="font-size:12px">알림 수신 회원</div><b style="font-size:20px">{{ number_format($userCount) }}</b></div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.push.send') }}" class="card" style="padding:20px">
        @csrf
        <div style="margin-bottom:14px">
            <label style="display:block;font-weight:600;margin-bottom:6px">제목</label>
            <input type="text" name="title" maxlength="60" required value="{{ old('title') }}"
                   style="width:100%;padding:11px 13px;border:1px solid #e5e7eb;border-radius:9px" placeholder="예) 신상품 입고 안내">
        </div>
        <div style="margin-bottom:14px">
            <label style="display:block;font-weight:600;margin-bottom:6px">내용</label>
            <textarea name="body" maxlength="200" required rows="3"
                      style="width:100%;padding:11px 13px;border:1px solid #e5e7eb;border-radius:9px" placeholder="알림 본문">{{ old('body') }}</textarea>
        </div>
        <div style="margin-bottom:14px">
            <label style="display:block;font-weight:600;margin-bottom:6px">이동 경로 (선택)</label>
            <input type="text" name="link" maxlength="200" value="{{ old('link') }}"
                   style="width:100%;padding:11px 13px;border:1px solid #e5e7eb;border-radius:9px" placeholder="예) /product/상품슬러그  또는  /community/notices">
            <small class="muted">앱에서 알림을 탭하면 이 경로로 이동합니다.</small>
        </div>
        <div style="margin-bottom:18px">
            <label style="display:block;font-weight:600;margin-bottom:6px">대상</label>
            <select name="target" id="target" style="width:100%;padding:11px 13px;border:1px solid #e5e7eb;border-radius:9px">
                <option value="all">전체 회원</option>
                <option value="business">병원(사업자) 회원</option>
                <option value="general">일반 회원</option>
                <option value="email">특정 회원 (이메일)</option>
            </select>
        </div>
        <div id="emailRow" style="margin-bottom:18px;display:none">
            <label style="display:block;font-weight:600;margin-bottom:6px">대상 이메일</label>
            <input type="email" name="email" value="{{ old('email') }}"
                   style="width:100%;padding:11px 13px;border:1px solid #e5e7eb;border-radius:9px" placeholder="user@example.com">
        </div>
        <button type="submit" class="btn btn-navy" style="padding:12px 22px" @unless($enabled) disabled @endunless>
            <x-icon name="headset"/> 발송하기
        </button>
    </form>
</div>

<script>
    document.getElementById('target').addEventListener('change', function () {
        document.getElementById('emailRow').style.display = this.value === 'email' ? 'block' : 'none';
    });
</script>
@endsection
