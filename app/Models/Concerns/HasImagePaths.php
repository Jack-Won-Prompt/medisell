<?php

namespace App\Models\Concerns;

/**
 * 이미지 경로를 환경 독립적으로 저장/출력한다.
 *
 *  저장(DB)  : 호스트 없는 상대경로  — 예) product/picked/ABC-123.jpg
 *  출력(화면) : 현재 APP_URL 기준 절대 URL — asset() 이 조립
 *
 * 이렇게 해두면 같은 DB를 로컬(하위폴더 서비스)과 운영(도메인 루트)에서 그대로 쓸 수 있고,
 * 특정 호스트가 데이터에 굳어버리지 않는다. 외부 URL(제휴 배너 등)과 data URI 는 그대로 둔다.
 */
trait HasImagePaths
{
    /** DB 저장용 — 절대 URL·서브폴더 접두사를 떼고 상대경로로 */
    public static function toRelativeImagePath(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }
        $url = trim($url);
        if ($url === '' || str_starts_with($url, 'data:')) {
            return $url === '' ? null : $url;
        }

        // 우리 사이트가 아닌 외부 이미지는 손대지 않는다
        if (preg_match('#^https?://#i', $url) && ! self::isOwnHost($url)) {
            return $url;
        }

        $path = ltrim((string) preg_replace('#^https?://[^/]+#i', '', $url), '/');

        // APP_URL 에 서브폴더가 있으면(예: http://호스트/medisell) 그 접두사 제거
        $base = trim((string) parse_url((string) config('app.url'), PHP_URL_PATH), '/');
        if ($base !== '' && str_starts_with($path, $base.'/')) {
            $path = substr($path, strlen($base) + 1);
        }

        return $path !== '' ? $path : null;
    }

    /** 화면 출력용 — 상대경로를 현재 환경의 절대 URL로 */
    public static function toImageUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }
        if (str_starts_with($path, 'data:') || preg_match('#^https?://#i', $path)) {
            return $path;                       // 외부 URL·data URI 는 그대로
        }

        return asset($path);
    }

    /** 저장된 값이 우리 사이트 호스트인지 (APP_URL 호스트 또는 현재 요청 호스트) */
    protected static function isOwnHost(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return true;
        }
        $own = array_filter([
            strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST)),
            strtolower((string) request()?->getHost()),
        ]);

        foreach ($own as $h) {
            // www 유무 차이는 같은 사이트로 본다
            if ($host === $h || ltrim($host, 'www.') === ltrim($h, 'www.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * 본문 HTML(상세설명) 안의 이미지 주소도 현재 환경으로 맞춘다.
     * 에디터가 업로드 시 절대 URL을 심어두면 다른 환경에서 깨지므로 출력 시 재조립한다.
     */
    public static function rewriteHtmlImageUrls(?string $html): ?string
    {
        if (! $html) {
            return $html;
        }

        return preg_replace_callback(
            '#(?:https?://[^"\'\s)]+)?/?((?:storage|product|images|img)/[^"\'\s)]+\.(?:png|jpe?g|gif|webp|svg))#i',
            fn ($m) => (string) self::toImageUrl(self::toRelativeImagePath($m[0])),
            $html,
        );
    }
}
