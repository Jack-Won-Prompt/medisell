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

    // 채널 정의 — 표시 순서 겸 화이트리스트
    'channels' => [
        'coupang' => '쿠팡',
        'naver'   => '네이버',
        'medical' => '의료소모품몰',
    ],
];
