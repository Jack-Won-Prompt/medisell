<?php

return [
    /*
    | 쿠팡 경쟁가(타사 판매정보) 조회.
    | simulate=true : 실제 쿠팡 호출 없이 모의 검색결과 생성(화면·흐름 검증용).
    | 실연동은 쿠팡 파트너스(제휴) 검색 API 또는 검색 크롤링이 필요하며 별도 승인/제약이 있음.
    */
    'simulate' => env('COUPANG_SIMULATE', true),

    /*
    | 실연동 엔진 자동 선택 우선순위: serp(구글쇼핑) → partners(쿠팡 파트너스).
    | simulate=false 이고 아래 키가 설정된 엔진이 사용됨.
    */

    // ① SERP(구글 쇼핑) API — 파트너스 키 없이 구글 색인 경유로 경쟁가 조회 (SerpAPI 등)
    'serp' => [
        'api_key'    => env('COUPANG_SERP_API_KEY'),
        'endpoint'   => env('COUPANG_SERP_ENDPOINT', 'https://serpapi.com/search.json'),
        'engine'     => env('COUPANG_SERP_ENGINE', 'google_shopping'),
        'gl'         => 'kr',
        'hl'         => 'ko',
        // true면 결과를 쿠팡 판매건만 필터, false면 전 마켓 경쟁가
        'coupang_only' => env('COUPANG_SERP_COUPANG_ONLY', false),
    ],

    // ② 쿠팡 파트너스(제휴) API
    'partners' => [
        'access_key' => env('COUPANG_PARTNERS_ACCESS_KEY'),
        'secret_key' => env('COUPANG_PARTNERS_SECRET_KEY'),
    ],
];
