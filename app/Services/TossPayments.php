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
