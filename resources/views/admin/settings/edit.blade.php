@extends('layouts.admin')
@section('title', '사이트 설정')
@section('heading', '사이트 설정')

@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" style="max-width:880px">
    @csrf @method('PUT')

    {{-- 기본 정보 --}}
    <div class="adm-card">
        <div class="h">기본 정보</div>
        <div style="padding:20px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="afield"><label>쇼핑몰명 <span style="color:#e0322d">*</span></label><input type="text" name="name" class="ainput" value="{{ old('name', $site['name'] ?? '') }}" required></div>
                <div class="afield"><label>영문명</label><input type="text" name="name_en" class="ainput" value="{{ old('name_en', $site['name_en'] ?? '') }}"></div>
            </div>
            <div class="afield"><label>슬로건</label><input type="text" name="tagline" class="ainput" value="{{ old('tagline', $site['tagline'] ?? '') }}"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="afield"><label>상호(법인명)</label><input type="text" name="company" class="ainput" value="{{ old('company', $site['company'] ?? '') }}"></div>
                <div class="afield"><label>대표자</label><input type="text" name="ceo" class="ainput" value="{{ old('ceo', $site['ceo'] ?? '') }}"></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="afield"><label>사업자등록번호</label><input type="text" name="biz_no" class="ainput" value="{{ old('biz_no', $site['biz_no'] ?? '') }}"></div>
                <div class="afield"><label>통신판매업신고</label><input type="text" name="mailorder" class="ainput" value="{{ old('mailorder', $site['mailorder'] ?? '') }}"></div>
            </div>
            <div class="afield"><label>주소</label><input type="text" name="address" class="ainput" value="{{ old('address', $site['address'] ?? '') }}"></div>
        </div>
    </div>

    {{-- 고객센터 --}}
    <div class="adm-card">
        <div class="h">고객센터</div>
        <div style="padding:20px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="afield"><label>대표전화</label><input type="text" name="cs_tel" class="ainput" value="{{ old('cs_tel', $site['cs_tel'] ?? '') }}"></div>
                <div class="afield"><label>이메일</label><input type="text" name="email" class="ainput" value="{{ old('email', $site['email'] ?? '') }}"></div>
            </div>
            <div class="afield"><label>운영시간 안내</label><input type="text" name="cs_hours" class="ainput" value="{{ old('cs_hours', $site['cs_hours'] ?? '') }}"></div>
        </div>
    </div>

    {{-- 무통장 입금계좌 --}}
    <div class="adm-card">
        <div class="h">무통장 입금계좌</div>
        <div style="padding:20px">
            <table class="atable" id="bankTable">
                <thead><tr><th style="width:160px">은행</th><th>계좌번호</th><th style="width:160px">예금주</th><th style="width:50px"></th></tr></thead>
                <tbody>
                    @php($banks = old('banks', $site['banks'] ?? []))
                    @forelse($banks as $i => $b)
                        <tr>
                            <td><input type="text" name="banks[{{ $i }}][bank]" class="ainput" value="{{ $b['bank'] ?? '' }}" placeholder="국민은행"></td>
                            <td><input type="text" name="banks[{{ $i }}][account]" class="ainput" value="{{ $b['account'] ?? '' }}" placeholder="000-00-000000"></td>
                            <td><input type="text" name="banks[{{ $i }}][holder]" class="ainput" value="{{ $b['holder'] ?? '' }}"></td>
                            <td><button type="button" class="abtn abtn-red abtn-sm" onclick="this.closest('tr').remove()">×</button></td>
                        </tr>
                    @empty
                    @endforelse
                </tbody>
            </table>
            <button type="button" class="abtn abtn-ghost abtn-sm" id="addBank" style="margin-top:10px">＋ 계좌 추가</button>
        </div>
    </div>

    {{-- 배송 / 적립 정책 --}}
    <div class="adm-card">
        <div class="h">배송 / 적립 정책</div>
        <div style="padding:20px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="afield"><label>무료배송 기준금액(원)</label><input type="number" name="free_ship_over" class="ainput" value="{{ old('free_ship_over', $site['free_ship_over'] ?? 0) }}" required></div>
                <div class="afield"><label>기본 배송비(원)</label><input type="number" name="shipping_fee" class="ainput" value="{{ old('shipping_fee', $site['shipping_fee'] ?? 0) }}" required></div>
                <div class="afield"><label>신규가입 적립금(원)</label><input type="number" name="signup_point" class="ainput" value="{{ old('signup_point', $site['signup_point'] ?? 0) }}" required></div>
                <div class="afield"><label>구매 적립률(%)</label><input type="number" name="point_rate" class="ainput" value="{{ old('point_rate', $site['point_rate'] ?? 0) }}" required></div>
            </div>
        </div>
    </div>

    {{-- 인기검색어 --}}
    <div class="adm-card">
        <div class="h">인기 검색어</div>
        <div style="padding:20px">
            <div class="afield">
                <label>인기검색어 (쉼표로 구분)</label>
                <input type="text" name="popular_keywords" class="ainput" value="{{ old('popular_keywords', implode(', ', $site['popular_keywords'] ?? [])) }}" placeholder="주사기, 멸균거즈, 수액세트">
            </div>
        </div>
    </div>

    <div style="display:flex;justify-content:flex-end;gap:10px">
        <button class="abtn abtn-pri" style="padding:11px 28px">설정 저장</button>
    </div>
</form>

<script>
(function () {
    var add = document.getElementById('addBank');
    var tbody = document.querySelector('#bankTable tbody');
    add.addEventListener('click', function () {
        var i = tbody.children.length;
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><input type="text" name="banks['+i+'][bank]" class="ainput" placeholder="은행"></td>' +
            '<td><input type="text" name="banks['+i+'][account]" class="ainput" placeholder="계좌번호"></td>' +
            '<td><input type="text" name="banks['+i+'][holder]" class="ainput" placeholder="예금주"></td>' +
            '<td><button type="button" class="abtn abtn-red abtn-sm">×</button></td>';
        tr.querySelector('button').addEventListener('click', function(){ tr.remove(); });
        tbody.appendChild(tr);
    });
})();
</script>
@endsection
