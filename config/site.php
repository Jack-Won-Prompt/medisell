<?php

/*
|--------------------------------------------------------------------------
| 사이트 전역 설정 (회사정보 / 고객센터 / 무통장 입금계좌)
|--------------------------------------------------------------------------
*/

return [
    'name'        => '메디셀',
    'name_en'     => 'MEDISELL',
    'tagline'     => '의료소모품 전문 쇼핑몰',
    'company'     => '메디셀',
    'ceo'         => '최연아',
    'biz_no'      => '833-27-01712',
    'mailorder'   => '제2024-서울강서-1841호',      // 통신판매업 신고번호
    'med_device'  => '제2024-3150037-00091호',      // 의료기기판매업 신고번호
    'address'     => '서울특별시 강서구 마곡중앙로 161-8, C동 5층 502호 (마곡동, 두산더랜드파크)',

    'cs_tel'      => '070-7537-3329',
    'cs_hours'    => '평일 09:00 ~ 18:00 (점심 12:00~13:00) / 주말·공휴일 휴무',
    'email'       => 'help@medisell.co.kr',

    // 결제가 확인된 모든 주문의 주문서(PDF) 수신처
    'order_mail_to' => ['3shub@naver.com', 'adm@linkthelab.co.kr'],

    // 관리자 알림 (회원가입·문의·실시간 상담·주문) — 이메일 + 문자
    'admin_notify_emails' => ['adm@linkthelab.co.kr'],
    'admin_notify_phones' => ['01042061393'],

    // 무통장 입금계좌
    'banks' => [
        ['bank' => '국민은행', 'account' => '000000-00-000000', 'holder' => '메디셀'],
        ['bank' => '신한은행', 'account' => '000-000-000000', 'holder' => '메디셀'],
        ['bank' => '농협은행', 'account' => '000-0000-0000-00', 'holder' => '메디셀'],
        ['bank' => '하나은행', 'account' => '000-000000-00000', 'holder' => '메디셀'],
    ],

    // 결제 PG (관리자 사이트설정에서 선택): toss | portone
    'payment_pg' => env('PAYMENT_PG', 'toss'),

    /*
     | 모바일 앱 스토어 주소
     |
     | 값이 비어 있으면 화면에 앱 다운로드 영역을 아예 그리지 않는다.
     | 관리자 > 사이트설정에서 입력하므로 심사 통과 후 배포 없이 켤 수 있다.
     */
    'app_android_url' => env('APP_ANDROID_URL', 'https://play.google.com/store/apps/details?id=co.kr.medisell.medisell_app'),
    'app_ios_url'     => env('APP_IOS_URL', ''),       // https://apps.apple.com/kr/app/...

    // 오늘의 특가 구성 (관리자 사이트설정): random(랜덤) | discount(할인율순) | best(베스트)
    'deal_mode' => 'random',

    // 배송비 정책
    'free_ship_over' => 50000,  // 5만원 이상 무료배송
    'shipping_fee'   => 3000,

    // 가입 적립금
    'signup_point'   => 3000,
    // 구매 적립률(%)
    'point_rate'     => 1,

    'popular_keywords' => ['주사기', '멸균거즈', '수액세트', '니트릴장갑', '인슐린주사기', '알코올솜', '반창고'],
];
