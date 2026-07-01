<?php

namespace App\Services\Popbill;

use Linkhub\Popbill\PopbillEasyFinBank;
use Linkhub\Popbill\PopbillException;

/**
 * 팝빌 계좌조회(EasyFinBank) 얇은 래퍼 — SDK 지연 생성.
 */
class PopbillEasyFinBankService
{
    private ?PopbillEasyFinBank $api = null;

    private function api(): PopbillEasyFinBank
    {
        if ($this->api === null) {
            if (! defined('LINKHUB_COMM_MODE')) {
                define('LINKHUB_COMM_MODE', env('POPBILL_LINKHUB_COMM_MODE', 'CURL'));
            }
            $api = new PopbillEasyFinBank(config('popbill.LinkID'), config('popbill.SecretKey'));
            $api->IsTest((bool) config('popbill.IsTest', true));
            $api->IPRestrictOnOff((bool) config('popbill.IPRestrictOnOff', true));
            $api->UseStaticIP((bool) config('popbill.UseStaticIP', false));
            $api->UseLocalTimeYN((bool) config('popbill.UseLocalTimeYN', true));
            $this->api = $api;
        }

        return $this->api;
    }

    public function requestJob(string $corpNum, string $bankCode, string $account, string $sDate, string $eDate): string
    {
        try {
            return $this->api()->RequestJob($corpNum, $bankCode, $account, $sDate, $eDate);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    public function getJobState(string $corpNum, string $jobId): object
    {
        try {
            return $this->api()->GetJobState($corpNum, $jobId);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    public function search(string $corpNum, string $jobId, array $tradeType = ['I'], ?string $searchString = null, int $page = 1, int $perPage = 500, string $order = 'D'): object
    {
        try {
            return $this->api()->Search($corpNum, $jobId, $tradeType, $searchString, $page, $perPage, $order);
        } catch (PopbillException $e) {
            $this->fail($e);
        }
    }

    private function fail(PopbillException $e): never
    {
        throw new \RuntimeException('[팝빌 '.$e->getCode().'] '.$e->getMessage(), (int) $e->getCode(), $e);
    }
}
