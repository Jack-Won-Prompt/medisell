<footer class="site-footer">
    <div class="foot-top">
        <div class="container">
            <div class="foot-cs">
                <div style="font-size:13px;font-weight:600;color:#cdd5e6">고객센터</div>
                <div class="tel">{{ $site['cs_tel'] }}</div>
                <div class="hours">{{ $site['cs_hours'] }}<br>이메일 {{ $site['email'] }}</div>
                <div class="btns">
                    <a href="{{ route('community.inquiry', ['type' => 'qna']) }}" class="btn btn-red btn-sm">1:1 문의</a>
                    <a href="{{ route('community.faq') }}" class="btn btn-ghost btn-sm" style="background:transparent;color:#cdd5e6;border-color:#3a4760">자주묻는질문</a>
                </div>
            </div>

            <div class="foot-cols">
                <div>
                    <h5>쇼핑가이드</h5>
                    <a href="{{ route('community.notices') }}">공지사항</a>
                    <a href="{{ route('community.faq') }}">FAQ</a>
                    <a href="{{ route('community.qna') }}">견적·1:1문의</a>
                    <a href="{{ route('community.reviews') }}">상품후기</a>
                </div>
                <div>
                    <h5>마이페이지</h5>
                    <a href="{{ route('mypage.orders') }}">주문조회</a>
                    <a href="{{ route('mypage.points') }}">적립금</a>
                    <a href="{{ route('mypage.profile') }}">회원정보수정</a>
                    <a href="{{ route('cart.index') }}">장바구니</a>
                </div>
            </div>

            <div class="foot-banks">
                <h5>무통장 입금계좌</h5>
                @foreach($site['banks'] as $b)
                    <div class="b"><b>{{ $b['bank'] }}</b> {{ $b['account'] }}</div>
                @endforeach
                <div class="b" style="margin-top:6px;color:#6b7794">예금주 : {{ $site['banks'][0]['holder'] }}</div>
            </div>
        </div>
    </div>

    <div class="foot-bottom">
        <div class="container">
            <div>
                <div class="legal">
                    <a href="#">회사소개</a>
                    <a href="#">이용약관</a>
                    <a href="#"><b style="color:#fff">개인정보처리방침</b></a>
                    <a href="#">이용안내</a>
                </div>
                <div class="copy">
                    {{ $site['company'] }} · 대표 {{ $site['ceo'] }} · 사업자등록번호 {{ $site['biz_no'] }} · 통신판매업 {{ $site['mailorder'] }}<br>
                    {{ $site['address'] }} · 고객센터 {{ $site['cs_tel'] }}<br>
                    Copyright © {{ date('Y') }} {{ $site['name_en'] }}. All rights reserved.
                </div>
            </div>
        </div>
    </div>
</footer>
