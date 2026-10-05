<?php

namespace App\Services\Popbill;

use Linkhub\Popbill\Cashbill;
use Linkhub\Popbill\PopbillCashbill;
use Linkhub\Popbill\PopbillException;

/** 팝빌 현금영수증 API 얇은 래퍼 */
class PopbillCashbillService
{
    private ?PopbillCashbill $api = null;

    private function api(): PopbillCashbill
    {
        if ($this->api === null) {
            if (! defined('LINKHUB_COMM_MODE')) {
                define('LINKHUB_COMM_MODE', config('popbill.comm_mode', 'CURL'));
            }
            $api = new PopbillCashbill(config('popbill.LinkID'), config('popbill.SecretKey'));
            $api->IsTest((bool) config('popbill.IsTest', true));
            $api->IPRestrictOnOff((bool) config('popbill.IPRestrictOnOff', true));
            $api->UseStaticIP((bool) config('popbill.UseStaticIP', false));
            $api->UseLocalTimeYN((bool) config('popbill.UseLocalTimeYN', true));
            $this->api = $api;
        }

        return $this->api;
    }

    /** SDK 의 Cashbill 은 PopbillCashbill.php 안에 함께 있어 PSR-4 로는 따로 못 찾는다 — 파일을 먼저 읽는다 */
    public function newCashbill(): Cashbill
    {
        class_exists(PopbillCashbill::class);

        return new Cashbill();
    }

    /** 즉시발행 — SDK 순서: (사업자번호, 현금영수증, 메모, 회원아이디, 메일제목) */
    public function registIssue(string $corpNum, Cashbill $cashbill, ?string $userId = null, ?string $memo = null): object
    {
        return $this->call(fn () => $this->api()->RegistIssue($corpNum, $cashbill, $memo, $userId));
    }

    /** 취소 현금영수증 즉시발행(전체 취소) — 새 문서번호로 원본 승인번호·거래일자를 지정 */
    public function revokeRegistIssue(string $corpNum, string $mgtKey, string $orgConfirmNum, string $orgTradeDate, ?string $userId = null, ?string $memo = null): object
    {
        return $this->call(fn () => $this->api()->RevokeRegistIssue($corpNum, $mgtKey, $orgConfirmNum, $orgTradeDate, false, $memo, $userId));
    }

    public function getInfo(string $corpNum, string $mgtKey): object
    {
        return $this->call(fn () => $this->api()->GetInfo($corpNum, $mgtKey));
    }

    public function getPopUpUrl(string $corpNum, string $mgtKey, ?string $userId = null): string
    {
        return $this->call(fn () => $this->api()->GetPopUpURL($corpNum, $mgtKey, $userId));
    }

    /** 고객 보기 URL — 팝빌이 고객에게 메일로 보내는 것과 같은 링크 */
    public function getMailUrl(string $corpNum, string $mgtKey, ?string $userId = null): string
    {
        return $this->call(fn () => $this->api()->GetMailURL($corpNum, $mgtKey, $userId));
    }

    private function call(\Closure $fn)
    {
        try {
            return $fn();
        } catch (PopbillException $e) {
            throw new \RuntimeException('[팝빌 '.$e->getCode().'] '.$e->getMessage(), (int) $e->getCode(), $e);
        }
    }
}
