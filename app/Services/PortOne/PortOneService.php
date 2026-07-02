<?php

namespace App\Services\PortOne;

use Illuminate\Support\Facades\Http;

/**
 * 포트원(아임포트) REST 결제검증 래퍼.
 * getToken → getPayment(imp_uid) 로 결제내역 조회 후 금액/상태 대조.
 */
class PortOneService
{
    private function base(): string
    {
        return rtrim(config('portone.api_base'), '/');
    }

    /** REST 액세스 토큰 발급 */
    public function token(): ?string
    {
        $res = Http::asJson()->post($this->base().'/users/getToken', [
            'imp_key'    => config('portone.imp_key'),
            'imp_secret' => config('portone.imp_secret'),
        ]);

        return $res->json('response.access_token');
    }

    /** imp_uid 로 결제 조회 */
    public function getPayment(string $impUid): ?array
    {
        $token = $this->token();
        if (! $token) {
            return null;
        }
        $res = Http::withToken($token)->get($this->base().'/payments/'.$impUid);

        return $res->successful() ? ($res->json('response') ?? null) : null;
    }

    /**
     * 결제 검증: imp_uid 조회 → 주문금액과 일치 & 상태 확인.
     * 반환: ['ok'=>bool, 'status'=>?, 'method'=>?, 'message'=>?, 'vbank'=>?]
     */
    public function verify(string $impUid, int $expectedAmount): array
    {
        $p = $this->getPayment($impUid);
        if (! $p) {
            return ['ok' => false, 'message' => '결제 정보를 조회할 수 없습니다.'];
        }
        if ((int) ($p['amount'] ?? -1) !== $expectedAmount) {
            return ['ok' => false, 'message' => '결제 금액이 일치하지 않습니다.'];
        }

        $status = $p['status'] ?? '';   // paid / ready(가상계좌 발급) / failed / cancelled
        $vbank = null;
        if ($status === 'ready' && ! empty($p['vbank_num'])) {
            $vbank = [
                'bank'    => $p['vbank_name'] ?? null,
                'account' => $p['vbank_num'] ?? null,
                'holder'  => $p['vbank_holder'] ?? null,
                'due'     => isset($p['vbank_date']) ? date('Y-m-d H:i:s', (int) $p['vbank_date']) : null,
            ];
        }

        return [
            'ok'      => in_array($status, ['paid', 'ready']),
            'status'  => $status,
            'method'  => $p['pay_method'] ?? null,
            'vbank'   => $vbank,
            'message' => $status === 'failed' ? ($p['fail_reason'] ?? '결제 실패') : null,
        ];
    }
}
