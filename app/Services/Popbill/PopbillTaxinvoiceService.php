<?php

namespace App\Services\Popbill;

use Linkhub\Popbill\PopbillException;
use Linkhub\Popbill\PopbillTaxinvoice;
use Linkhub\Popbill\Taxinvoice;
use Linkhub\Popbill\TaxinvoiceDetail;

/**
 * 팝빌 전자세금계산서 API 얇은 래퍼.
 */
class PopbillTaxinvoiceService
{
    private ?PopbillTaxinvoice $api = null;

    /** 실제 API 필요 시점에만 SDK 생성 (시뮬레이트 모드에서는 미생성) */
    private function api(): PopbillTaxinvoice
    {
        if ($this->api === null) {
            if (! defined('LINKHUB_COMM_MODE')) {
                define('LINKHUB_COMM_MODE', config('popbill.comm_mode', 'CURL'));
            }
            $this->api = new PopbillTaxinvoice(config('popbill.LinkID'), config('popbill.SecretKey'));
            $this->api()->IsTest((bool) config('popbill.IsTest', true));
            $this->api()->IPRestrictOnOff((bool) config('popbill.IPRestrictOnOff', true));
            $this->api()->UseStaticIP((bool) config('popbill.UseStaticIP', false));
            $this->api()->UseLocalTimeYN((bool) config('popbill.UseLocalTimeYN', true));
        }

        return $this->api;
    }

    /**
     * SDK 의 Taxinvoice·TaxinvoiceDetail 은 PopbillTaxinvoice.php 안에 함께 있어 PSR-4 로는 따로 못 찾는다.
     * 그 파일을 먼저 읽어 두지 않으면 실발행 때 'Class Taxinvoice not found' 가 난다.
     */
    private static function loadSdk(): void
    {
        class_exists(PopbillTaxinvoice::class);
    }

    public function newInvoice(): Taxinvoice
    {
        self::loadSdk();

        return new Taxinvoice();
    }

    public function newDetail(): TaxinvoiceDetail
    {
        self::loadSdk();

        return new TaxinvoiceDetail();
    }

    public function getBalance(string $corpNum): float
    {
        try {
            return $this->api()->GetBalance($corpNum);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    /** 즉시발행 (등록 + 발행) */
    public function registIssue(string $corpNum, Taxinvoice $invoice, ?string $userId = null, bool $forceIssue = false, ?string $memo = null): object
    {
        try {
            return $this->api()->RegistIssue($corpNum, $invoice, $userId, false, $forceIssue, $memo);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    public function getInfo(string $corpNum, string $mgtKey): object
    {
        try {
            return $this->api()->GetInfo($corpNum, 'SELL', $mgtKey);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    public function cancelIssue(string $corpNum, string $mgtKey, ?string $memo = null, ?string $userId = null): object
    {
        try {
            return $this->api()->CancelIssue($corpNum, 'SELL', $mgtKey, $memo, $userId);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    /** 공급받는자(고객) 보기 URL — 팝빌이 고객에게 메일로 보내는 것과 같은 링크 */
    public function getMailUrl(string $corpNum, string $mgtKey, ?string $userId = null): string
    {
        try {
            return $this->api()->GetMailURL($corpNum, 'SELL', $mgtKey, $userId);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    public function getPopUpUrl(string $corpNum, string $mgtKey, ?string $userId = null): string
    {
        try {
            return $this->api()->GetPopUpURL($corpNum, 'SELL', $mgtKey, $userId);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    private function fail(PopbillException $e): never
    {
        throw new \RuntimeException('[팝빌 '.$e->getCode().'] '.$e->getMessage(), (int) $e->getCode(), $e);
    }
}
