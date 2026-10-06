<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="pusher-key" content="{{ config('broadcasting.connections.pusher.key') }}">
    <meta name="pusher-cluster" content="{{ config('broadcasting.connections.pusher.options.cluster') }}">
    <title>@yield('title', '관리자') — 메디셀 관리자</title>
    <link rel="stylesheet" as="style" crossorigin href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard@v1.3.9/dist/web/static/pretendard.min.css">
    <link rel="icon" href="{{ asset('images/logo-mark.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/site.css') }}?v=21">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v=6">
</head>
<body>
    @include('partials.icons')
    @php($resources = config('admin'))
    @php($groups = collect($resources)->groupBy('group', preserveKeys: true))
    @php($cur = request()->route('resource'))
    <div class="adm">
        <aside class="adm-side">
            <a href="{{ route('admin.dashboard') }}" class="adm-brand">
                <img src="{{ asset('images/logo-mark.svg') }}" alt="" class="mark" style="width:32px;height:32px;background:#fff;border-radius:7px;padding:2px">
                <span><strong>메디셀</strong><span>ADMIN</span></span>
            </a>
            <nav class="adm-nav">
                <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'on' : '' }}"><x-icon name="chart"/> 대시보드</a>
                <a href="{{ route('admin.reports.sales') }}" class="{{ request()->routeIs('admin.reports.*') ? 'on' : '' }}"><x-icon name="chart"/> 매출 리포트</a>

                <div class="grp">주문/회원</div>
                <a href="{{ route('admin.orders.index') }}" class="{{ request()->routeIs('admin.orders.*') ? 'on' : '' }}"><x-icon name="cart"/> 주문관리</a>
                <a href="{{ route('admin.bank.index') }}" class="{{ request()->routeIs('admin.bank.*') ? 'on' : '' }}"><x-icon name="coin"/> 입금확인</a>
                <a href="{{ route('admin.toss.index') }}" class="{{ request()->routeIs('admin.toss.*') ? 'on' : '' }}"><x-icon name="coin"/> 토스 결제 이력</a>
                <a href="{{ route('admin.popbill.index') }}" class="{{ request()->routeIs('admin.popbill.*') ? 'on' : '' }}"><x-icon name="doc"/> 팝빌 테스트</a>
                <a href="{{ route('admin.cashbacks.index') }}" class="{{ request()->routeIs('admin.cashbacks.*') ? 'on' : '' }}"><x-icon name="coin"/> 대행 캐쉬백</a>
                <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'on' : '' }}"><x-icon name="user"/> 회원관리</a>
                <a href="{{ route('admin.accounts.index') }}" class="{{ request()->routeIs('admin.accounts.*') ? 'on' : '' }}"><x-icon name="building"/> 거래처 단가</a>
                <a href="{{ route('admin.login-logs.index') }}" class="{{ request()->routeIs('admin.login-logs.*') ? 'on' : '' }}"><x-icon name="shield"/> 로그인 이력</a>
                <a href="{{ route('admin.chat.index') }}" class="{{ request()->routeIs('admin.chat.*') ? 'on' : '' }}"><x-icon name="headset"/> 실시간 상담</a>
                <a href="{{ route('admin.inquiries.index') }}" class="{{ request()->routeIs('admin.inquiries.*') ? 'on' : '' }}"><x-icon name="question"/> 문의관리</a>
                <a href="{{ route('admin.reviews.index') }}" class="{{ request()->routeIs('admin.reviews.*') ? 'on' : '' }}"><x-icon name="star"/> 후기관리</a>
                <a href="{{ route('admin.coupang.index') }}" class="{{ request()->routeIs('admin.coupang.*') ? 'on' : '' }}"><x-icon name="tag"/> 쿠팡 경쟁가</a>
                <a href="{{ route('admin.push.index') }}" class="{{ request()->routeIs('admin.push.*') ? 'on' : '' }}"><x-icon name="headset"/> 푸시 알림</a>

                @foreach($groups as $gname => $items)
                    <div class="grp">{{ $gname }}</div>
                    @foreach($items as $key => $r)
                        <a href="{{ route('admin.index', $key) }}" class="{{ $cur === $key ? 'on' : '' }}"><x-icon :name="$r['icon']"/> {{ $r['label'] }}</a>
                    @endforeach
                @endforeach

                <div class="grp">환경설정</div>
                <a href="{{ route('admin.settings.edit') }}" class="{{ request()->routeIs('admin.settings.*') ? 'on' : '' }}"><x-icon name="tools"/> 사이트 설정</a>
            </nav>
            <div class="adm-foot">
                <a href="{{ route('home') }}" target="_blank"><x-icon name="arrow-right"/> 쇼핑몰 보기</a>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button type="submit"><x-icon name="logout"/> 로그아웃</button>
                </form>
            </div>
        </aside>

        <div class="adm-main">
            <header class="adm-top">
                <h1>@yield('heading', '관리자')</h1>
                <div class="who"><x-icon name="user" :size="16"/> {{ auth()->user()->name }} 님</div>
            </header>
            <div class="adm-body">
                @if(session('ok'))<div class="flash"><x-icon name="check"/> {{ session('ok') }}</div>@endif
                @if(session('error'))<div class="flash" style="background:#fee2e2;border-color:#fecaca;color:#991b1b"><x-icon name="close"/> {{ session('error') }}</div>@endif
                @if($errors->any())<div class="flash" style="background:#fee2e2;border-color:#fecaca;color:#991b1b"><x-icon name="close"/> {{ $errors->first() }}</div>@endif
                @yield('content')
            </div>
        </div>
    </div>
<script>
/* 가로 탭 공용 — 처음 여는 탭: 입력 오류가 난 칸의 탭 → 주소 #탭 → 저장 전 보던 탭(저장 후 돌아와도 유지) → data-default */
(function () {
    var errors = @json(array_keys($errors->getMessages()));
    document.querySelectorAll('.utabs[data-tabs]').forEach(function (bar) {
        var scope = bar.parentElement, key = 'admin-tab:' + location.pathname + ':' + bar.dataset.tabs;
        var tabs = bar.querySelectorAll('[data-tab]');
        var panels = Array.prototype.filter.call(scope.children, function (el) { return el.classList.contains('upanel'); });
        scope.classList.add('tabs-on');
        function show(name, remember) {
            var found = false;
            tabs.forEach(function (t) { var on = t.dataset.tab === name; t.classList.toggle('on', on); t.setAttribute('aria-selected', on); found = found || on; });
            if (!found) return false;
            panels.forEach(function (p) { p.classList.toggle('on', p.dataset.panel === name); });
            if (remember !== false) { try { sessionStorage.setItem(key, name); } catch (e) {} }
            if (location.hash !== '#' + name) history.replaceState(null, '', location.pathname + location.search + '#' + name);
            return true;
        }
        tabs.forEach(function (t) { t.type = 'button'; t.addEventListener('click', function () { show(t.dataset.tab); }); });
        // 숨은 탭의 필수 입력이 비어 있으면 브라우저가 아무 말 없이 제출을 막는다 — 그 칸의 탭을 열어 보여 준다
        scope.addEventListener('invalid', function (e) {
            var panel = e.target.closest && e.target.closest('.upanel');
            if (panel && panels.indexOf(panel) !== -1 && !panel.classList.contains('on')) show(panel.dataset.panel);
        }, true);
        var errorTab = null;
        errors.some(function (k) {   // banks.0.bank → banks[0][bank]
            var parts = k.split('.'), name = parts.shift() + parts.map(function (p) { return '[' + p + ']'; }).join('');
            var el = scope.querySelector('[name="' + name + '"],[name="' + name + '[]"]');
            var panel = el && el.closest('.upanel');
            if (panel) { errorTab = panel.dataset.panel; return true; }
            return false;
        });
        var stored = null; try { stored = sessionStorage.getItem(key); } catch (e) {}
        [errorTab, location.hash.replace('#', ''), stored, bar.dataset.default].concat(Array.prototype.map.call(tabs, function (t) { return t.dataset.tab; }))
            .some(function (n) { return n && show(n); });
    });
})();
</script>
</body>
</html>
