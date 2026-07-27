<?php

return [
    /*
    | 네이버 개발자센터 검색 API(쇼핑) — 인터넷 최저가 비교의 네이버·일반몰 채널 수집원.
    | https://developers.naver.com 에서 애플리케이션 등록 후 "검색" API 사용 추가.
    | 두 값이 모두 채워지면 자동으로 실연동된다(별도 스위치 없음).
    */
    'shop' => [
        'client_id'     => env('NAVER_CLIENT_ID'),
        'client_secret' => env('NAVER_CLIENT_SECRET'),
        'endpoint'      => env('NAVER_SHOP_ENDPOINT', 'https://openapi.naver.com/v1/search/shop.json'),
        // 한 번에 받아올 건수(최대 100) — 많을수록 채널별 최저가 정확도가 올라간다
        'display'       => (int) env('NAVER_SHOP_DISPLAY', 40),
    ],
];
