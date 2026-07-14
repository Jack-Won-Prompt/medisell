<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FCM HTTP v1 발송 서비스 (추가 패키지 없이 서비스계정 JWT → OAuth 토큰 → 발송).
 *
 * 설정: config/fcm.php (FCM_PROJECT_ID, FCM_CREDENTIALS)
 * 키 미설정 시 조용히 skip (로컬/미구성 환경 안전).
 */
class FcmService
{
    private const SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';
    private const OAUTH = 'https://oauth2.googleapis.com/token';

    private ?array $sa = null;   // 서비스 계정
    private ?string $projectId = null;

    public function __construct()
    {
        if (config('fcm.disabled')) {
            return;
        }
        $path = config('fcm.credentials');
        if ($path && is_file($path)) {
            $json = json_decode((string) file_get_contents($path), true);
            if (is_array($json) && ! empty($json['client_email']) && ! empty($json['private_key'])) {
                $this->sa = $json;
                $this->projectId = config('fcm.project_id') ?: ($json['project_id'] ?? null);
            }
        }
    }

    public function enabled(): bool
    {
        return $this->sa !== null && $this->projectId !== null;
    }

    /** 특정 회원의 모든 기기로 발송 */
    public function sendToUser(User|int $user, string $title, string $body, array $data = []): void
    {
        $userId = $user instanceof User ? $user->id : $user;
        $tokens = DeviceToken::where('user_id', $userId)->pluck('token');
        $this->sendToTokens($tokens, $title, $body, $data);
    }

    /** 여러 회원에게 발송 (마케팅/공지) */
    public function sendToUsers(iterable $userIds, string $title, string $body, array $data = []): void
    {
        $tokens = DeviceToken::whereIn('user_id', collect($userIds)->all())->pluck('token');
        $this->sendToTokens($tokens, $title, $body, $data);
    }

    /** 전체 발송 */
    public function sendToAll(string $title, string $body, array $data = []): void
    {
        $this->sendToTokens(DeviceToken::pluck('token'), $title, $body, $data);
    }

    /** 토큰 목록으로 발송 (개별 요청, 실패 토큰 정리) */
    public function sendToTokens(Collection|array $tokens, string $title, string $body, array $data = []): int
    {
        if (! $this->enabled()) {
            return 0;
        }
        $tokens = collect($tokens)->filter()->unique()->values();
        if ($tokens->isEmpty()) {
            return 0;
        }

        $access = $this->accessToken();
        if (! $access) {
            return 0;
        }

        // data 값은 문자열이어야 함
        $data = collect($data)->map(fn ($v) => (string) $v)->all();

        $sent = 0;
        foreach ($tokens as $token) {
            $ok = $this->postMessage($access, $token, $title, $body, $data);
            if ($ok) {
                $sent++;
            }
        }

        return $sent;
    }

    private function postMessage(string $access, string $token, string $title, string $body, array $data): bool
    {
        try {
            $res = Http::withToken($access)
                ->acceptJson()
                ->post("https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => ['title' => $title, 'body' => $body],
                        'data' => $data,
                        'android' => [
                            'priority' => 'high',
                            'notification' => ['sound' => 'default', 'channel_id' => 'medisell_default'],
                        ],
                        'apns' => [
                            'payload' => ['aps' => ['sound' => 'default']],
                        ],
                    ],
                ]);

            if ($res->successful()) {
                return true;
            }

            // 만료/미등록 토큰 정리
            $status = $res->json('error.status');
            if (in_array($status, ['UNREGISTERED', 'NOT_FOUND', 'INVALID_ARGUMENT'], true) || $res->status() === 404) {
                DeviceToken::where('token', $token)->delete();
            }
            Log::warning('fcm.send.fail', ['status' => $res->status(), 'body' => $res->json('error.status')]);

            return false;
        } catch (\Throwable $e) {
            Log::warning('fcm.send.error', ['msg' => $e->getMessage()]);

            return false;
        }
    }

    /** OAuth2 액세스 토큰 (약 55분 캐시) */
    private function accessToken(): ?string
    {
        return Cache::remember('fcm_access_token_'.md5($this->sa['client_email']), 3300, function () {
            $jwt = $this->buildJwt();
            if (! $jwt) {
                return null;
            }
            try {
                $res = Http::asForm()->post(self::OAUTH, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);
                if ($res->successful()) {
                    return $res->json('access_token');
                }
                Log::warning('fcm.oauth.fail', ['status' => $res->status(), 'body' => $res->body()]);
            } catch (\Throwable $e) {
                Log::warning('fcm.oauth.error', ['msg' => $e->getMessage()]);
            }

            return null;
        });
    }

    /** 서비스계정 → RS256 JWT (구글 OAuth assertion) */
    private function buildJwt(): ?string
    {
        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss'   => $this->sa['client_email'],
            'scope' => self::SCOPE,
            'aud'   => self::OAUTH,
            'iat'   => $now,
            'exp'   => $now + 3600,
        ];

        $segments = [
            $this->b64(json_encode($header)),
            $this->b64(json_encode($claims)),
        ];
        $signingInput = implode('.', $segments);

        $signature = '';
        $pk = openssl_pkey_get_private($this->sa['private_key']);
        if (! $pk || ! openssl_sign($signingInput, $signature, $pk, 'sha256WithRSAEncryption')) {
            return null;
        }
        $segments[] = $this->b64($signature);

        return implode('.', $segments);
    }

    private function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
