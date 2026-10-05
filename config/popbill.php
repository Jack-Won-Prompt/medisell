<?php

return [
    'LinkID'          => env('POPBILL_ID'),
    'SecretKey'       => env('POPBILL_SECRET_KEY'),
    'IsTest'          => env('POPBILL_IS_TEST', true),
    'IPRestrictOnOff' => env('POPBILL_IP_RESTRICT_ON_OFF', true),
    'UseStaticIP'     => env('POPBILL_USE_STATIC_IP', false),
    'UseLocalTimeYN'  => env('POPBILL_USE_LOCAL_TIME_YN', true),
    // 서비스 안에서 env() 를 직접 읽으면 config:cache 후 null 이 되므로 여기서 읽는다
    'comm_mode'       => env('POPBILL_LINKHUB_COMM_MODE', 'CURL'),

    /*
    | 발행·발송 사업자 (세금계산서·현금영수증·문자 공통) — LINKTHELAB 연동회원.
    | 팝빌에 이 사업자로 가입돼 있어야 하고, 세금계산서는 공동인증서, 문자는 발신번호 승인이 필요하다.
    */
    'corp_num'        => env('POPBILL_CORP_NUM', env('POPBILL_SMS_CORP_NUM', env('POPBILL_TEST_CORP_NUM', ''))),
    // 발행 사업자의 팝빌 회원 아이디(링크더랩: linkthelab). 옛 TEST_USER_ID(leefriends)로 대체하면 '회원의 아이디가 아닙니다' 오류
    'user_id'         => env('POPBILL_USER_ID', ''),

    /*
    | 고객 안내 문자
    | mode: simulate(발송 안 함, 이력만) / redirect(모두 테스트 번호로) / live(실제 고객에게)
    */
    'sms' => [
        'mode'          => env('POPBILL_SMS_MODE', 'simulate'),
        'sender'        => env('POPBILL_SENDER_NUM', ''),          // 팝빌에 등록·승인된 발신번호
        'sender_name'   => env('POPBILL_SENDER_NAME', '메디셀'),
        'test_receiver' => env('POPBILL_TEST_RECEIVER_HP', ''),
    ],

    // 현금영수증 — 기본 시뮬레이트(실발행 사고 방지). 실발행: POPBILL_CASHBILL_SIMULATE=false
    'cashbill' => [
        'simulate' => env('POPBILL_CASHBILL_SIMULATE', true),
    ],

    // 승인 병원 회원 주문(카드 결제 제외) 결제 확인 시 세금계산서 자동 발행
    'auto_taxinvoice' => env('POPBILL_AUTO_TAXINVOICE', true),

    /*
    | 시뮬레이트 모드: true 면 실제 팝빌 API를 호출하지 않고 발행이력만 생성.
    | 실발행 준비가 되면 .env 에 POPBILL_TAXINVOICE_SIMULATE=false 로 설정.
    | ※ 기본값 true (실계정/실발행 사고 방지)
    */
    'simulate'        => env('POPBILL_TAXINVOICE_SIMULATE', true),

    // 계좌조회(EasyFinBank) — 무통장 입금 자동확인
    'bank' => [
        'simulate'    => env('POPBILL_BANK_SIMULATE', true),          // 기본 시뮬레이트(실계좌 조회 차단)
        'corp_num'    => env('POPBILL_TEST_CORP_NUM', ''),            // 팝빌 회원 사업자번호
        'user_id'     => env('POPBILL_TEST_USER_ID', ''),
        'bank_code'   => env('POPBILL_BANK_CODE', '0020'),            // 은행코드(예: 우리 0020, 국민 0004, 신한 0088, 농협 0011, 기업 0003, 하나 0081)
        'account_num' => env('POPBILL_BANK_ACCOUNT', ''),            // 조회 계좌번호(하이픈 제외)
    ],

    // 공급자(발행자) — 위 corp_num 과 같은 사업자. 상호·대표·주소·업태·종목도 그 사업자의 사업자등록증과 같아야 한다.
    'supplier' => [
        'corp_num'  => env('POPBILL_CORP_NUM', env('POPBILL_SMS_CORP_NUM', env('POPBILL_TEST_CORP_NUM', ''))), // 숫자만
        'user_id'   => env('POPBILL_USER_ID', ''),                                                             // 팝빌 회원 아이디
        'corp_name' => env('COMPANY_CORP_NAME', '메디셀'),
        'ceo_name'  => env('COMPANY_CEO_NAME', '최연아'),
        'addr'      => env('COMPANY_ADDR', '서울특별시 강서구 마곡중앙로 161-8, C동 5층 502호'),
        'biz_type'  => env('COMPANY_BIZ_CLASS', '도매 및 소매업'),        // 업태
        'biz_class' => env('COMPANY_BIZ_TYPE', '전자상거래 소매업, 의료기기 도소매'),  // 종목
        'tel'       => env('COMPANY_TEL', ''),
        'email'     => env('COMPANY_EMAIL', ''),
    ],
];
