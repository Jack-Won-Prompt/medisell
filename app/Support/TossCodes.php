<?php

namespace App\Support;

/** 토스페이먼츠 카드사·은행 코드 → 이름 (모르는 코드는 코드 그대로) */
class TossCodes
{
    public const CARDS = [
        '11' => 'KB국민카드', '21' => '하나카드', '31' => 'BC카드', '41' => '신한카드', '51' => '삼성카드',
        '61' => '현대카드', '71' => '롯데카드', '91' => 'NH농협카드', '33' => '우리카드(BC)', 'W1' => '우리카드',
        '3K' => '기업BC카드', '36' => '씨티카드', '15' => '카카오뱅크', '24' => '토스뱅크', '3A' => '케이뱅크',
        '30' => 'KDB산업은행', '34' => 'Sh수협은행', '35' => '전북은행', '37' => '우체국', '38' => '새마을금고',
        '39' => '저축은행', '42' => '제주은행', '46' => '광주은행', '62' => '신협',
    ];

    public const BANKS = [
        // 숫자 코드
        '02' => 'KDB산업은행', '03' => 'IBK기업은행', '04' => 'KB국민은행', '07' => 'Sh수협은행', '11' => 'NH농협은행',
        '12' => '단위농협', '20' => '우리은행', '23' => 'SC제일은행', '27' => '씨티은행', '31' => 'iM뱅크(대구)',
        '32' => '부산은행', '34' => '광주은행', '35' => '제주은행', '37' => '전북은행', '39' => '경남은행',
        '45' => '새마을금고', '48' => '신협', '50' => '저축은행', '54' => 'HSBC', '64' => '산림조합',
        '71' => '우체국', '81' => '하나은행', '88' => '신한은행', '89' => '케이뱅크', '90' => '카카오뱅크', '92' => '토스뱅크',
        // 영문 코드 (가상계좌 발급 요청 등)
        'KDBBANK' => 'KDB산업은행', 'IBK' => 'IBK기업은행', 'KOOKMIN' => 'KB국민은행', 'SUHYEOP' => 'Sh수협은행',
        'NONGHYEOP' => 'NH농협은행', 'LOCALNONGHYEOP' => '단위농협', 'WOORI' => '우리은행', 'SC' => 'SC제일은행',
        'CITI' => '씨티은행', 'DAEGUBANK' => 'iM뱅크(대구)', 'BUSANBANK' => '부산은행', 'GWANGJUBANK' => '광주은행',
        'JEJUBANK' => '제주은행', 'JEONBUKBANK' => '전북은행', 'KYONGNAMBANK' => '경남은행', 'SAEMAUL' => '새마을금고',
        'SHINHYEOP' => '신협', 'SAVINGBANK' => '저축은행', 'POST' => '우체국', 'HANA' => '하나은행', 'SHINHAN' => '신한은행',
        'KBANK' => '케이뱅크', 'KAKAOBANK' => '카카오뱅크', 'TOSSBANK' => '토스뱅크',
    ];

    public static function card(?string $code): string
    {
        return self::CARDS[(string) $code] ?? (string) $code;
    }

    public static function bank(?string $code): string
    {
        return self::BANKS[strtoupper((string) $code)] ?? (string) $code;
    }
}
