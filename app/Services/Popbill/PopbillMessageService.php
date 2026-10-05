<?php

namespace App\Services\Popbill;

use Linkhub\Popbill\PopbillException;
use Linkhub\Popbill\PopbillMessaging;

/**
 * 팝빌 문자 API 얇은 래퍼.
 * config('popbill.sms.mode'): simulate(호출 안 함) / redirect(테스트 번호로) / live(실발송)
 */
class PopbillMessageService
{
    /** SMS 한 건 최대 90바이트(EUC-KR) — 넘으면 LMS */
    public const SMS_MAX_BYTES = 90;

    private ?PopbillMessaging $api = null;

    private function api(): PopbillMessaging
    {
        if ($this->api === null) {
            if (! defined('LINKHUB_COMM_MODE')) {
                define('LINKHUB_COMM_MODE', config('popbill.comm_mode', 'CURL'));
            }
            $api = new PopbillMessaging(config('popbill.LinkID'), config('popbill.SecretKey'));
            $api->IsTest((bool) config('popbill.IsTest', true));
            $api->IPRestrictOnOff((bool) config('popbill.IPRestrictOnOff', true));
            $api->UseStaticIP((bool) config('popbill.UseStaticIP', false));
            $api->UseLocalTimeYN((bool) config('popbill.UseLocalTimeYN', true));
            $this->api = $api;
        }

        return $this->api;
    }

    /** 승인된 발신번호 목록 ['번호' => 상태] (상태 1 = 승인) */
    public function senderNumbers(): array
    {
        $corp = preg_replace('/\D/', '', (string) config('popbill.corp_num'));
        $out = [];
        foreach ($this->api()->GetSenderNumberList($corp, config('popbill.user_id') ?: null) as $s) {
            $out[$s->number] = (int) $s->state;
        }

        return $out;
    }

    /** [파트너 포인트, 회원 포인트, SMS 단가, LMS 단가] */
    public function balances(): array
    {
        $corp = preg_replace('/\D/', '', (string) config('popbill.corp_num'));

        return [
            $this->api()->GetPartnerBalance($corp),
            $this->api()->GetBalance($corp),
            $this->api()->GetUnitCost($corp, \Linkhub\Popbill\ENumMessageType::SMS),
            $this->api()->GetUnitCost($corp, \Linkhub\Popbill\ENumMessageType::LMS),
        ];
    }

    public static function msgType(string $content): string
    {
        return strlen((string) @iconv('UTF-8', 'EUC-KR//IGNORE', $content)) > self::SMS_MAX_BYTES ? 'LMS' : 'SMS';
    }

    /**
     * 한 건 발송.
     * 반환: ['status' => sent|simulated|redirected, 'receiver' => 실제 받는 번호, 'msg_type' => SMS|LMS, 'receipt_num' => ?string]
     */
    public function send(string $to, string $content, string $subject = '[메디셀] 안내', ?string $forceMode = null): array
    {
        // $forceMode: 관리자 팝빌 테스트 화면처럼 사이트 설정과 무관하게 실발송할 때 'live'
        $mode = $forceMode ?? config('popbill.sms.mode', 'simulate');
        $type = self::msgType($content);
        $receiver = $to;
        $status = 'sent';

        if ($mode === 'simulate') {
            return ['status' => 'simulated', 'receiver' => $to, 'msg_type' => $type, 'receipt_num' => null];
        }
        if ($mode === 'redirect') {
            $receiver = preg_replace('/\D/', '', (string) config('popbill.sms.test_receiver'));
            if ($receiver === '') {
                throw new \RuntimeException('POPBILL_TEST_RECEIVER_HP 가 비어 있어 테스트 발송을 할 수 없습니다.');
            }
            $status = 'redirected';
        }

        $corpNum = preg_replace('/\D/', '', (string) config('popbill.corp_num'));
        $sender = preg_replace('/\D/', '', (string) config('popbill.sms.sender'));
        $userId = config('popbill.user_id') ?: null;
        $senderName = config('popbill.sms.sender_name') ?: null;
        $messages = [['rcv' => $receiver, 'msg' => $content]];

        try {
            $receipt = $type === 'SMS'
                ? $this->api()->SendSMS($corpNum, $sender, null, $messages, null, false, $userId, $senderName)
                : $this->api()->SendLMS($corpNum, $sender, $subject, null, $messages, null, false, $userId, $senderName);
        } catch (PopbillException $e) {
            throw new \RuntimeException('[팝빌 '.$e->getCode().'] '.$e->getMessage(), (int) $e->getCode(), $e);
        }

        return ['status' => $status, 'receiver' => $receiver, 'msg_type' => $type, 'receipt_num' => (string) $receipt];
    }
}
