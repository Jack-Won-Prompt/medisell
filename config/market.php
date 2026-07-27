<?php

return [
    /*
    | 상품 상세 "인터넷 최저가 비교" 영역.
    | 쿠팡 · 네이버 · 일반 의료소모품몰 채널별 최저가 1건씩(=Top 3)을 보여준다.
    | 수집은 CoupangSearchService(모의 / SERP 구글쇼핑 / 쿠팡 파트너스)를 그대로 재사용한다.
    */
    'enabled' => env('MARKET_COMPARE_ENABLED', true),

    // 저장된 최저가를 며칠까지 유효하게 볼지 (지나면 재수집 대상)
    'ttl_days' => (int) env('MARKET_COMPARE_TTL_DAYS', 7),

    /*
    | 상세 페이지 조회 시 만료·미수집 상품을 즉시 재수집할지.
    | 모의(simulate) 모드는 외부 호출이 없어 안전하지만, 실연동(SERP 등)에서는
    | 페이지 지연·API 과금이 생긴다. null 이면 simulate 일 때만 자동 수집하고
    | 실연동에서는 market:refresh 스케줄로만 갱신한다.
    */
    'refresh_on_view' => env('MARKET_COMPARE_REFRESH_ON_VIEW', null),

    // 동일 상품 재수집 최소 간격(초) — 동시 요청 몰림 방지
    'refresh_throttle' => 300,

    /*
    | 가격 정합성 밴드 — 우리 정가 대비 이 배수를 벗어난 후보는 버린다.
    | 타사 검색은 같은 이름으로 낱개/박스가 섞여 나오므로(예: 1L 에탄올 vs 알콜솜 1매)
    | 배수가 크게 벗어나면 다른 규격으로 보고 비교에서 제외한다.
    */
    'price_band' => ['min' => 0.3, 'max' => 4.0],

    /*
    | 우리가 최저가가 아닐 때도 비교 영역을 보여줄지.
    | true  : 항상 노출(정직한 비교 — 우리가 더 비싸도 그대로 표시)
    | false : 우리 판매가가 전 채널 최저일 때만 노출(마케팅용)
    */
    'show_when_not_lowest' => env('MARKET_SHOW_WHEN_NOT_LOWEST', true),

    /*
    | 채널 정의 — 표시 순서 겸 화이트리스트.
    | 쿠팡은 파트너스 검색 API 미등록(401)으로 제외. 필요해지면 'coupang' => '쿠팡' 한 줄만 되살리면 된다.
    */
    'channels' => [
        'naver'   => '네이버',
        'medical' => '의료소모품몰',
    ],

    /*
    | Cafe24 입점 의료소모품몰 직접 검색 (medical 채널 수집원).
    | Cafe24 는 타사용 통합 상품검색 API가 없어 몰별 표준 검색 URL을 조회한다.
    |   %s = URL 인코딩된 검색어
    | ⚠️ 대부분의 B2B 의료몰은 비로그인 상태에서 판매가를 노출하지 않는다(로그인 후 공개).
    |    가격이 안 나오는 몰은 결과 0건으로 조용히 건너뛴다.
    */
    'cafe24_enabled' => env('MARKET_CAFE24_ENABLED', true),
    'cafe24_malls' => [
        ['name' => '어바웃메디', 'url' => 'https://aboutmedi.com/product/search.html?keyword=%s'],
        /*
        | 아래 몰들은 2026-07-27 확인 시 비로그인 상태에서 판매가를 렌더하지 않아 제외했다
        | (요청해도 0건이라 불필요한 트래픽만 발생). 가격이 공개되면 주석만 풀면 된다.
        |   ['name' => '메디버설',   'url' => 'https://mediversal.co.kr/product/search.html?keyword=%s'],
        |   ['name' => '메디세일',   'url' => 'https://medisale.co.kr/product/search.html?keyword=%s'],
        |   ['name' => '오픈메디컬', 'url' => 'https://openmedical.co.kr/product/search.html?keyword=%s'],
        */
    ],
];
