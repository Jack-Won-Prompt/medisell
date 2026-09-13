<?php

namespace Tests\Feature;

use App\Support\SupportWorksReporter;
use Illuminate\Database\QueryException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class SupportWorksReporterTest extends TestCase
{
    private const MASK = '[숨김]';

    private const ENDPOINT = 'https://supportworks.test/api/errors';

    /** 도우미는 private 이라 반사로 부른다. */
    private function helper(string $method, mixed ...$args): mixed
    {
        return (new ReflectionMethod(SupportWorksReporter::class, $method))->invoke(null, ...$args);
    }

    private function maskUrl(string $uri): string
    {
        return $this->helper('maskUrl', Request::create('https://shop.test'.$uri));
    }

    private function configure(): string
    {
        $token = Str::random(40);

        config([
            'services.supportworks.error_url'   => self::ENDPOINT,
            'services.supportworks.error_token' => $token,
        ]);

        return $token;
    }

    /** 긴 문자열을 인자로 받아 던지는 함수. trace 에 인자가 새는지 보려고 쓴다. */
    private function failWith(string $secret): never
    {
        throw new RuntimeException('실패');
    }

    // ── looksLikeSecret ────────────────────────────────────────────────

    public function test_looks_like_secret_is_twenty_plus_word_characters(): void
    {
        $this->assertTrue($this->helper('looksLikeSecret', str_repeat('a', 20)));
        $this->assertTrue($this->helper('looksLikeSecret', 'abc_DEF-123_abc_DEF-123'));
        $this->assertFalse($this->helper('looksLikeSecret', str_repeat('a', 19)));
        $this->assertFalse($this->helper('looksLikeSecret', '123'));
        $this->assertFalse($this->helper('looksLikeSecret', 'App\\Http\\Controllers\\OrderController'));
        $this->assertFalse($this->helper('looksLikeSecret', 'app/Http/Controllers/OrderController.php'));
        $this->assertFalse($this->helper('looksLikeSecret', 'Illuminate.Database.QueryException'));
    }

    // ── maskUrl: 가림 ──────────────────────────────────────────────────

    public function test_mask_url_masks_secret_query_value_only(): void
    {
        $secret = Str::random(12);
        $url = $this->maskUrl('/search?token='.$secret.'&q=mask');

        $this->assertStringNotContainsString($secret, $url);
        $this->assertStringContainsString('token='.self::MASK, $url);
        $this->assertStringContainsString('q=mask', $url);
    }

    public function test_mask_url_masks_split_names(): void
    {
        $a = Str::random(12);
        $b = Str::random(12);
        $url = $this->maskUrl('/x?api_key='.$a.'&access-token='.$b);

        $this->assertStringNotContainsString($a, $url);
        $this->assertStringNotContainsString($b, $url);
        $this->assertStringContainsString('api_key='.self::MASK, $url);
        $this->assertStringContainsString('access-token='.self::MASK, $url);
    }

    public function test_mask_url_masks_whole_name_match(): void
    {
        $secret = Str::random(12);
        $url = $this->maskUrl('/x?apiKey='.$secret);

        $this->assertStringNotContainsString($secret, $url);
        $this->assertStringContainsString('apiKey='.self::MASK, $url);
    }

    public function test_mask_url_recurses_into_array_parameters(): void
    {
        $secret = Str::random(12);
        $url = $this->maskUrl('/x?user[password]='.$secret.'&user[name]=kim');

        $this->assertStringNotContainsString($secret, $url);
        $this->assertStringContainsString('user[password]='.self::MASK, $url);
        $this->assertStringContainsString('user[name]=kim', $url);
    }

    public function test_mask_url_masks_token_in_path(): void
    {
        $secret = Str::random(64);
        $url = $this->maskUrl('/reset-password/'.$secret);

        $this->assertSame('https://shop.test/reset-password/'.self::MASK, $url);
    }

    // ── maskUrl: 안 가림 ───────────────────────────────────────────────

    public function test_mask_url_keeps_names_that_only_contain_a_secret_word(): void
    {
        $url = $this->maskUrl('/x?keyword=shoes&author=kim&monkey=1&keynote=1');

        $this->assertStringNotContainsString(self::MASK, $url);
        foreach (['keyword=shoes', 'author=kim', 'monkey=1', 'keynote=1'] as $pair) {
            $this->assertStringContainsString($pair, $url);
        }
    }

    public function test_mask_url_keeps_ordinary_path(): void
    {
        $this->assertSame('https://shop.test/orders/123', $this->maskUrl('/orders/123'));
    }

    // ── maskMessage ────────────────────────────────────────────────────

    public function test_mask_message_masks_sql_binding(): void
    {
        $secret = Str::random(32);
        $message = 'SQLSTATE[42S22]: Column not found (Connection: mysql, SQL: select * from users where token = '.$secret.' and id = 123)';

        $masked = $this->helper('maskMessage', $message);

        $this->assertStringNotContainsString($secret, $masked);
        $this->assertStringContainsString('where token = '.self::MASK.' and id = 123', $masked);
    }

    public function test_mask_message_strips_surrounding_punctuation_before_checking(): void
    {
        $secret = Str::random(32);
        $masked = $this->helper('maskMessage', "Duplicate entry '{$secret}' for key, ({$secret}), \"{$secret}\".");

        $this->assertStringNotContainsString($secret, $masked);
        $this->assertSame("Duplicate entry '[숨김]' for key, ([숨김]), \"[숨김]\".", $masked);
    }

    public function test_mask_message_keeps_class_names_and_paths(): void
    {
        $message = 'Call to undefined method App\\Http\\Controllers\\OrderController::show() in /var/www/app/Http/Controllers/OrderController.php';

        $this->assertSame($message, $this->helper('maskMessage', $message));
    }

    public function test_real_query_exception_message_has_no_binding(): void
    {
        config(['database.connections.sw_probe' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        $secret = Str::random(40);

        try {
            DB::connection('sw_probe')->select('select * from users where token = ? and id = ?', [$secret, 123]);
            $this->fail('QueryException 이 나야 한다.');
        } catch (QueryException $e) {
            // 원래 메시지에는 바인딩 값이 그대로 들어 있다.
            $this->assertStringContainsString($secret, $e->getMessage());

            $masked = $this->helper('maskMessage', $e->getMessage());
            $this->assertStringNotContainsString($secret, $masked);
            $this->assertStringContainsString('where token = '.self::MASK.' and id = 123', $masked);
        }
    }

    // ── trace ──────────────────────────────────────────────────────────

    public function test_trace_has_no_arguments(): void
    {
        $secret = Str::random(64);

        try {
            $this->failWith($secret);
        } catch (RuntimeException $e) {
            // getTraceAsString() 은 앞 15자를 흘린다 — 이 시험이 헛돌지 않는다는 확인.
            $this->assertStringContainsString(substr($secret, 0, 15), $e->getTraceAsString());

            $trace = $this->helper('trace', $e);

            $this->assertStringNotContainsString(substr($secret, 0, 8), $trace);
            $this->assertStringContainsString(static::class.'->failWith()', $trace);
            $this->assertMatchesRegularExpression('/^#0 .+SupportWorksReporterTest\.php\(\d+\): /', $trace);
        }
    }

    // ── report ─────────────────────────────────────────────────────────

    public function test_report_sends_masked_payload_with_token(): void
    {
        $token = $this->configure();
        Http::fake();
        $secret = Str::random(40);

        SupportWorksReporter::report(
            new RuntimeException('bad token '.$secret),
            Request::create('https://shop.test/orders/123?token='.$secret)
        );

        Http::assertSent(function (ClientRequest $r) use ($token, $secret) {
            return $r->url() === self::ENDPOINT
                && $r->hasHeader('Authorization', 'Bearer '.$token)
                && $r['level'] === 'error'
                && $r['exception'] === RuntimeException::class
                && $r['message'] === 'bad token '.self::MASK
                && $r['url'] === 'https://shop.test/orders/123?token='.self::MASK
                && is_int($r['line'])
                && ! str_contains(json_encode($r->data()), $secret);
        });
    }

    public function test_report_skips_silently_without_config(): void
    {
        config(['services.supportworks.error_url' => null, 'services.supportworks.error_token' => null]);
        Http::fake();

        SupportWorksReporter::report(new RuntimeException('x'));

        Http::assertNothingSent();
    }

    public function test_report_skips_not_found(): void
    {
        $this->configure();
        Http::fake();

        SupportWorksReporter::report(new NotFoundHttpException());

        Http::assertNothingSent();
    }

    public function test_report_never_throws_when_sending_fails(): void
    {
        $this->configure();
        Http::fake(fn () => throw new ConnectionException('연결 실패'));

        SupportWorksReporter::report(new RuntimeException('x'), Request::create('https://shop.test/'));

        $this->assertTrue(true);
    }

    public function test_site_keeps_running_when_an_exception_is_reported(): void
    {
        $this->configure();

        // 받는 쪽이 죽어 있는 상황. 던지는 가짜 요청은 Http 가 기록하지 않으므로 직접 모은다.
        $sent = [];
        Http::fake(function (ClientRequest $r) use (&$sent) {
            $sent[] = $r;
            throw new ConnectionException('연결 실패');
        });

        Route::get('/_sw_probe/boom', fn () => throw new RuntimeException('일부러 낸 오류'));
        Route::get('/_sw_probe/ok', fn () => 'ok');

        // APP_URL 에 하위 경로(/medisell)가 붙어 있어 상대 주소는 라우트에 닿지 않는다. 절대 주소로 부른다.
        $this->get('https://shop.test/_sw_probe/boom')->assertStatus(500);
        $this->get('https://shop.test/_sw_probe/ok')->assertOk()->assertSee('ok');

        $this->assertCount(1, $sent);
        $this->assertSame('일부러 낸 오류', $sent[0]['message']);
        $this->assertSame('https://shop.test/_sw_probe/boom', $sent[0]['url']);
    }
}
