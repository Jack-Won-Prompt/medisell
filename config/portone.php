<?php

return [
    /*
    | 포트원(아임포트) 결제.
    | simulate=true : 실제 결제창/검증 없이 발행이력만(안전). 실연동은 false + 키/식별코드 설정.
    */
    'simulate'   => env('PORTONE_SIMULATE', true),

    'imp_code'   => env('PORTONE_IMP_CODE'),        // 가맹점 식별코드 (IMP.init, imp########)
    'imp_key'    => env('PORTONE_IMP_KEY'),         // REST API Key
    'imp_secret' => env('PORTONE_IMP_SECRET'),      // REST API Secret

    'pg'         => env('PORTONE_PG', 'html5_inicis'), // PG 채널 (예: html5_inicis, kcp, kakaopay, tosspayments)
    'pay_method' => env('PORTONE_PAY_METHOD', 'card'),
    'api_base'   => 'https://api.iamport.kr',
];
