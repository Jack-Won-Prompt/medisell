<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * 운영에서 난 예외를 SupportWorks 로 보낸다.
 *
 * 보고가 사이트를 망가뜨리면 안 된다. 그래서 예외를 절대 밖으로 내보내지 않고,
 * 응답을 돌려준 뒤에 보내고, 시간 제한을 짧게 둔다.
 */
class SupportWorksReporter
{
    /** 가려야 할 이름. 값이 그대로 남으면 오류 기록이 곧 자격증명 창고가 된다. */
    private const SECRET_KEYS = [
        'token', 'access_token', 'refresh_token', 'api_key', 'apikey',
        'secret', 'password', 'passwd', 'pw', 'signature', 'auth', 'key',
        'authorization', 'credential', 'credentials', 'bearer', 'otp', 'pin', 'session',
    ];

    private const MASK = '[숨김]';

    /** message 낱말 앞뒤에서 떼어 낼 문장부호. 가운데의 _ - 는 토큰의 일부라 여기 넣지 않는다. */
    private const WORD_PUNCTUATION = "'\"`()[]{}<>,;:.!?";

    public static function report(Throwable $e, ?Request $request = null): void
    {
        try {
            $url = config('services.supportworks.error_url');
            $token = config('services.supportworks.error_token');

            if (! $url || ! $token) {
                return;
            }

            // 404 는 보내지 않는다. 고칠 것이 없고, 봇이 훑고 갈 때마다 쌓인다.
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return;
            }

            $payload = [
                'level'     => 'error',
                'exception' => get_class($e),
                'message'   => mb_substr(self::maskMessage($e->getMessage()), 0, 2000),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'url'       => $request ? self::maskUrl($request) : null,
                'trace'     => mb_substr(self::trace($e), 0, 8000),
            ];

            // 응답을 돌려준 뒤에 보낸다. 콘솔에는 그 시점이 없으므로 바로 보낸다.
            $send = function () use ($url, $token, $payload) {
                try {
                    Http::withToken($token)->timeout(2)->connectTimeout(2)->post($url, $payload);
                } catch (Throwable) {
                    // 보고가 실패해도 사이트는 계속 돈다.
                }
            };

            app()->runningInConsole() ? $send() : app()->terminating($send);
        } catch (Throwable) {
            // 여기서 예외가 새어 나가면 예외 처리기 자체가 깨진다.
        }
    }

    /**
     * 요청 주소. 쿼리스트링은 이름으로, 경로는 생김새로 가린다.
     * /orders/123 같은 조각은 남긴다 — 어느 화면에서 난 오류인지는 알아야 한다.
     */
    private static function maskUrl(Request $request): string
    {
        $segments = array_map(
            fn (string $segment) => self::looksLikeSecret(rawurldecode($segment)) ? self::MASK : $segment,
            explode('/', $request->getPathInfo())
        );

        $url = $request->getSchemeAndHttpHost().$request->getBaseUrl().implode('/', $segments);

        $query = self::maskQuery($request->query->all());

        if ($query !== []) {
            // 사람이 읽을 기록이므로 인코딩을 풀어 둔다.
            $url .= '?'.rawurldecode(http_build_query($query, '', '&', PHP_QUERY_RFC3986));
        }

        return $url;
    }

    /** 이름이 목록에 걸리면 값만 가린다. 배열은 안으로 들어간다. */
    private static function maskQuery(array $query): array
    {
        foreach ($query as $name => $value) {
            if (self::isSecretName((string) $name)) {
                $query[$name] = self::MASK;
            } elseif (is_array($value)) {
                $query[$name] = self::maskQuery($value);
            }
        }

        return $query;
    }

    /**
     * 이름 전체가 목록과 같거나, 영숫자가 아닌 글자로 쪼갠 조각 하나가 목록과 정확히 같을 때.
     * keyword·author·monkey 처럼 목록의 이름을 품기만 한 것은 걸리지 않는다.
     */
    private static function isSecretName(string $name): bool
    {
        $name = strtolower($name);

        if (in_array($name, self::SECRET_KEYS, true)) {
            return true;
        }

        foreach (preg_split('/[^a-z0-9]+/', $name, -1, PREG_SPLIT_NO_EMPTY) as $part) {
            if (in_array($part, self::SECRET_KEYS, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 오류 메시지. 공백으로 끊고 앞뒤 문장부호를 뗀 가운데가 토큰처럼 생겼으면 가린다.
     * 역슬래시·슬래시·점이 섞인 클래스 이름과 파일 경로는 한 낱말이 아니라서 남는다.
     */
    private static function maskMessage(string $message): string
    {
        $parts = preg_split('/(\s+)/u', $message, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            return $message;
        }

        foreach ($parts as $i => $word) {
            $lead = strspn($word, self::WORD_PUNCTUATION);
            $trail = strspn(strrev($word), self::WORD_PUNCTUATION);

            if ($lead + $trail >= strlen($word)) {
                continue;
            }

            $core = substr($word, $lead, strlen($word) - $lead - $trail);

            if (self::looksLikeSecret($core)) {
                $parts[$i] = substr($word, 0, $lead).self::MASK.substr($word, strlen($word) - $trail);
            }
        }

        return implode('', $parts);
    }

    /**
     * 스택. getTraceAsString() 은 인자를 앞 15자까지 적으므로 쓰지 않는다.
     * 파일·줄·클래스·함수만 모으고 인자는 아예 넣지 않는다.
     */
    private static function trace(Throwable $e): string
    {
        $lines = [];

        foreach ($e->getTrace() as $i => $frame) {
            $where = isset($frame['file'])
                ? $frame['file'].'('.($frame['line'] ?? '?').')'
                : '[internal function]';

            $lines[] = '#'.$i.' '.$where.': '
                .($frame['class'] ?? '').($frame['type'] ?? '').($frame['function'] ?? '').'()';
        }

        $lines[] = '#'.count($lines).' {main}';

        return implode("\n", $lines);
    }

    /** 토큰처럼 생긴 낱말인가. 경로 조각과 message 가 이 규칙 하나를 함께 쓴다. */
    private static function looksLikeSecret(string $word): bool
    {
        return strlen($word) >= 20 && preg_match('/^[A-Za-z0-9_-]+$/', $word) === 1;
    }
}
