<?php

return [
    /*
    | 쿠팡 경쟁가(타사 판매정보) 조회.
    | simulate=true : 실제 쿠팡 호출 없이 모의 검색결과 생성(화면·흐름 검증용).
    | 실연동은 쿠팡 파트너스(제휴) 검색 API 또는 검색 크롤링이 필요하며 별도 승인/제약이 있음.
    */
    'simulate' => env('COUPANG_SIMULATE', true),

    // 쿠팡 파트너스(제휴) API — 실연동 시 사용
    'partners' => [
        'access_key' => env('COUPANG_PARTNERS_ACCESS_KEY'),
        'secret_key' => env('COUPANG_PARTNERS_SECRET_KEY'),
    ],
];
