<?php

namespace App\Console\Commands;

use App\Models\MarketPrice;
use App\Models\Product;
use App\Services\MarketPriceService;
use Illuminate\Console\Command;

/**
 * 인터넷 최저가 비교 데이터 갱신.
 * 실연동(SERP 등)에서는 조회 시 자동수집을 끄고 이 명령을 스케줄로 돌린다.
 *
 *   php artisan market:refresh                  # 만료·미수집 상품만 (기본 200건)
 *   php artisan market:refresh --force          # TTL 무시하고 전부
 *   php artisan market:refresh --product=12     # 특정 상품만
 */
class RefreshMarketPrices extends Command
{
    protected $signature = 'market:refresh
                            {--product= : 특정 상품 ID만 갱신}
                            {--limit=200 : 최대 처리 건수}
                            {--force : TTL 남은 상품도 강제 갱신}
                            {--sleep=0 : 건당 대기(밀리초) — 외부 API 레이트리밋 회피}';

    protected $description = '쿠팡·네이버·의료소모품몰 최저가 비교 데이터 갱신';

    public function handle(MarketPriceService $service): int
    {
        $q = Product::active();

        if ($id = $this->option('product')) {
            $q->where('id', (int) $id);
        } elseif (! $this->option('force')) {
            // 미수집 또는 TTL 만료 상품만
            $ttl = now()->subDays((int) config('market.ttl_days', 7));
            $fresh = MarketPrice::where('fetched_at', '>=', $ttl)->pluck('product_id')->unique();
            $q->whereNotIn('id', $fresh);
        }

        $products = $q->orderBy('id')->limit((int) $this->option('limit'))->get();
        if ($products->isEmpty()) {
            $this->info('갱신 대상이 없습니다.');

            return self::SUCCESS;
        }

        $sleep = (int) $this->option('sleep');
        $ok = $fail = 0;
        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        foreach ($products as $p) {
            $rows = $service->refresh($p);
            $rows->isNotEmpty() ? $ok++ : $fail++;
            if ($sleep > 0) {
                usleep($sleep * 1000);
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("갱신 완료: 성공 {$ok}건 / 실패·결과없음 {$fail}건");

        return self::SUCCESS;
    }
}
