<?php

/*
|--------------------------------------------------------------------------
| 모바일 앱 버전 관리
|--------------------------------------------------------------------------
| 앱은 /api/v1/settings 응답의 mobile 블록을 받아 자신의 빌드번호와 비교한다.
| - 빌드번호 < min_build   → 강제 업데이트 (닫기 불가)
| - 빌드번호 < latest_build → 선택 업데이트 (나중에 가능)
|
| 새 버전 출시 시 .env 의 값을 올린 뒤 `php artisan config:clear`.
*/

return [
    'android' => [
        'latest_build'   => (int) env('MOBILE_ANDROID_LATEST_BUILD', 9),
        'min_build'      => (int) env('MOBILE_ANDROID_MIN_BUILD', 1),
        'latest_version' => env('MOBILE_ANDROID_LATEST_VERSION', '1.0.0'),
        'store_url'      => env('MOBILE_ANDROID_STORE_URL', 'https://play.google.com/store/apps/details?id=co.kr.medisell.medisell_app'),
        'notes'          => env('MOBILE_ANDROID_NOTES', ''),
    ],
    'ios' => [
        'latest_build'   => (int) env('MOBILE_IOS_LATEST_BUILD', 1),
        'min_build'      => (int) env('MOBILE_IOS_MIN_BUILD', 1),
        'latest_version' => env('MOBILE_IOS_LATEST_VERSION', '1.0.0'),
        'store_url'      => env('MOBILE_IOS_STORE_URL', ''),
        'notes'          => env('MOBILE_IOS_NOTES', ''),
    ],
];
