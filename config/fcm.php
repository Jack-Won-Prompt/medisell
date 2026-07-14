<?php

return [
    // Firebase 프로젝트 ID (Firebase 콘솔 → 프로젝트 설정)
    'project_id' => env('FCM_PROJECT_ID'),

    // 서비스 계정 키(JSON) 절대경로.
    // Firebase 콘솔 → 프로젝트 설정 → 서비스 계정 → 새 비공개 키 생성 → 다운로드
    // 기본 위치: storage/app/fcm-service-account.json (.gitignore 권장)
    'credentials' => env('FCM_CREDENTIALS', storage_path('app/fcm-service-account.json')),

    // 발송 비활성화(키 미설정 시 자동 skip). true 로 강제 비활성 가능.
    'disabled' => env('FCM_DISABLED', false),
];
