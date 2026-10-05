<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * 토스페이먼츠 서버 API 래퍼.
 * 시크릿 키 Basic 인증: base64(secretKey + ":")
 */
class TossPayments
{
    private string $base;

    private string $secret;

    public function __construct()
    {
        $cfg = config('services.toss');
        $this->base = rtrim($cfg['api_base'], '/');
        $this->secret = (string) $cfg['secret_key'];
    }

    private function auth(): string
    {
        return 'Basic '.base64_encode($this->secret.':');
    }

    /**
     * 결제 승인 — POST /v1/payments/confirm
     * 성공 시 Payment 객체(array), 실패 시 ['error' => true, 'code', 'message'].
     */
    public function confirm(string $paymentKey, string $orderId, int $amount): array
    {
        $res = Http::withHeaders([
            'Authorization' => $this->auth(),
            'Content-Type'  => 'application/json',
        ])->post($this->base.'/v1/payments/confirm', [
            'paymentKey' => $paymentKey,
            'orderId'    => $orderId,
            'amount'     => $amount,
        ]);

        if ($res->successful()) {
            return $res->json();
        }

        return [
            'error'   => true,
            'code'    => $res->json('code', 'UNKNOWN'),
            'message' => $res->json('message', '결제 승인에 실패했습니다.'),
        ];
    }

    /** paymentKey로 결제 단건 조회 (웹훅 검증용) */
    public function get(string $paymentKey): array
    {
        $res = Http::withHeaders(['Authorization' => $this->auth()])
            ->get($this->base.'/v1/payments/'.$paymentKey);

        return $res->successful() ? $res->json() : ['error' => true];
    }

    /**
     * 거래 내역 조회 — GET /v1/transactions (관리자 결제 이력 화면)
     * $start/$end: 'Y-m-d\TH:i:s' (KST). $after: 다음 페이지용 마지막 transactionKey.
     * 성공 시 거래 배열, 실패 시 ['error'=>true, 'code', 'message'].
     */
    public function transactions(string $start, string $end, ?string $after = null, int $limit = 100): array
    {
        $res = Http::withHeaders(['Authorization' => $this->auth()])
            ->timeout(20)
            ->get($this->base.'/v1/transactions', array_filter([
                'startDate'      => $start,
                'endDate'        => $end,
                'startingAfter'  => $after,
                'limit'          => $limit,
            ]));

        if ($res->successful()) {
            return ['error' => false, 'items' => $res->json() ?? []];
        }

        return [
            'error'   => true,
            'code'    => $res->json('code', 'HTTP_'.$res->status()),
            'message' => $res->json('message', '토스 거래 내역을 불러오지 못했습니다.'),
        ];
    }

    /** paymentKey 결제 상세 — 실패 시 ['error'=>true, 'code', 'message'] */
    public function payment(string $paymentKey): array
    {
        $res = Http::withHeaders(['Authorization' => $this->auth()])
            ->timeout(20)
            ->get($this->base.'/v1/payments/'.rawurlencode($paymentKey));

        return $res->successful()
            ? $res->json()
            : ['error' => true, 'code' => $res->json('code', 'HTTP_'.$res->status()), 'message' => $res->json('message', '결제 정보를 불러오지 못했습니다.')];
    }

    /**
     * 결제 취소(환불) — POST /v1/payments/{paymentKey}/cancel
     * 성공 시 Payment 객체, 실패 시 ['error'=>true, 'code', 'message'].
     */
    public function cancel(string $paymentKey, string $reason): array
    {
        $res = Http::withHeaders([
            'Authorization' => $this->auth(),
            'Content-Type'  => 'application/json',
        ])->post($this->base.'/v1/payments/'.$paymentKey.'/cancel', [
            'cancelReason' => $reason,
        ]);

        if ($res->successful()) {
            return $res->json();
        }

        return [
            'error'   => true,
            'code'    => $res->json('code', 'UNKNOWN'),
            'message' => $res->json('message', '결제 취소에 실패했습니다.'),
        ];
    }
}
