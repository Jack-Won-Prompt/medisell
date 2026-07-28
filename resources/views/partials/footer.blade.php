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

            @php($__android = $site['app_android_url'] ?? '')
            @php($__ios = $site['app_ios_url'] ?? '')
            @if(filled($__android) || filled($__ios))
                <div class="foot-app">
                    <h5>앱 다운로드</h5>
                    <p>주문·배송조회를 더 빠르게. 알림으로 소식도 받아보세요.</p>
                    @if(filled($__android))
                        <a href="{{ $__android }}" class="store-btn" target="_blank" rel="noopener" aria-label="Google Play 에서 다운로드">
                            <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true">
                                <path fill="#34a853" d="M3.6 20.5 14 12 3.6 3.5c-.4.3-.6.8-.6 1.4v14.2c0 .6.2 1.1.6 1.4z"/>
                                <path fill="#fbbc04" d="m17.5 15.5 3.1-1.8c.9-.5.9-1.9 0-2.4l-3.1-1.8L14 12z"/>
                                <path fill="#ea4335" d="M3.6 20.5c.5.4 1.2.4 1.9 0l12-6.9-3.5-3.5z"/>
                                <path fill="#4285f4" d="M3.6 3.5 14 13.9l3.5-3.5-12-6.9c-.7-.4-1.4-.4-1.9 0z"/>
                            </svg>
                            <span><small>GET IT ON</small>Google Play</span>
                        </a>
                    @endif
                    @if(filled($__ios))
                        <a href="{{ $__ios }}" class="store-btn" target="_blank" rel="noopener" aria-label="App Store 에서 다운로드">
                            <svg viewBox="0 0 24 24" width="20" height="20" fill="#fff" aria-hidden="true">
                                <path d="M16.4 12.6c0-2.2 1.8-3.3 1.9-3.3-1-1.5-2.6-1.7-3.2-1.7-1.4-.1-2.7.8-3.3.8-.7 0-1.7-.8-2.8-.8-1.5 0-2.8.8-3.5 2.1-1.5 2.6-.4 6.5 1.1 8.6.7 1 1.6 2.2 2.7 2.2 1.1 0 1.5-.7 2.8-.7s1.7.7 2.8.7c1.2 0 1.9-1 2.6-2.1.8-1.2 1.2-2.4 1.2-2.5-.1 0-2.3-.9-2.3-3.3zM14.3 5.9c.6-.7 1-1.7.9-2.7-.9 0-2 .6-2.6 1.3-.6.6-1.1 1.7-.9 2.6 1 .1 2-.5 2.6-1.2z"/>
                            </svg>
                            <span><small>Download on the</small>App Store</span>
                        </a>
                    @endif
                </div>
            @endif

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
                    <a href="{{ route('legal.terms') }}">이용약관</a>
                    <a href="{{ route('legal.privacy') }}"><b style="color:#fff">개인정보처리방침</b></a>
                    <a href="{{ route('legal.account-deletion') }}">계정 삭제</a>
                    <a href="#">이용안내</a>
                </div>
                <div class="copy">
                    {{ $site['company'] }} · 대표 {{ $site['ceo'] }} · 사업자등록번호 {{ $site['biz_no'] }}@if(!empty($site['mailorder'])) · 통신판매업 {{ $site['mailorder'] }}@endif @if(!empty($site['med_device'])) · 의료기기판매업 {{ $site['med_device'] }}@endif<br>
                    {{ $site['address'] }} · 고객센터 {{ $site['cs_tel'] }}<br>
                    Copyright © {{ date('Y') }} {{ $site['name_en'] }}. All rights reserved.
                </div>
            </div>
        </div>
    </div>
</footer>
