<?php

namespace App\Services;

use App\Models\MarketPrice;
use App\Models\Product;
use App\Services\Coupang\CoupangSearchService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 인터넷 최저가 비교 — 쿠팡 · 네이버 · 일반 의료소모품몰 채널별 최저가 1건씩(Top 3).
 *
 * 수집은 CoupangSearchService(모의 / SERP 구글쇼핑 / 쿠팡 파트너스)를 재사용하고,
 * 결과는 market_prices 에 채널당 1건만 남겨 페이지 조회 때 외부 호출이 없도록 한다.
 */
class MarketPriceService
{
    public function __construct(private CoupangSearchService $search) {}

    /**
     * 상세 페이지용 비교 데이터.
     * @return array{rows:array<int,array>,is_lowest:bool,saving:int,fetched_at:?\Illuminate\Support\Carbon}
     */
    public function compare(Product $product, ?int $sell = null): array
    {
        $empty = [
            'rows' => [], 'is_lowest' => false, 'saving' => 0,
            'fetched_at' => null, 'is_sample' => $this->isSample(),
        ];

        if (! config('market.enabled', true)) {
            return $empty;
        }

        $rows = $this->rows($product);
        if ($rows->isEmpty()) {
            return $empty;
        }

        $sell = $sell !== null ? $sell : $product->priceFor(null);
        $order = array_keys(config('market.channels', []));

        $out = $rows
            ->sortBy('price')
            ->map(fn (MarketPrice $m) => [
                'channel'       => $m->channel,
                'channel_label' => $m->channelLabel(),
                'seller'        => $m->seller,
                'title'         => $m->title,
                'price'         => (int) $m->price,
                'delivery'      => $m->delivery,
                'url'           => $m->url,
                // 우리 판매가와의 차액 — 양수면 우리가 더 저렴
                'diff'          => $sell > 0 ? (int) $m->price - $sell : 0,
                'diff_rate'     => $sell > 0 && $m->price > 0
                    ? (int) round(((int) $m->price - $sell) / (int) $m->price * 100)
                    : 0,
                'fetched_at'    => $m->fetched_at?->format('Y-m-d'),
            ])
            ->values()->all();

        // 설정된 채널 순서를 벗어난 값이 섞이지 않도록 화이트리스트 필터
        $out = array_values(array_filter($out, fn ($r) => in_array($r['channel'], $order, true)));

        $lowest = $out ? min(array_column($out, 'price')) : 0;

        return [
            'rows'       => $out,
            'is_lowest'  => $sell > 0 && $lowest > 0 && $sell <= $lowest,
            'saving'     => $sell > 0 && $lowest > $sell ? $lowest - $sell : 0,
            'fetched_at' => $rows->max('fetched_at'),
            // 모의(simulate) 모드 = 실제 시세가 아닌 생성값. 일반 고객에게 노출하면 안 된다.
            'is_sample'  => $this->isSample(),
        ];
    }

    /** 실연동 키가 없어 모의 데이터로 채워졌는지 */
    public function isSample(): bool
    {
        return ! $this->search->isReady();
    }

    /** 저장된 최저가 — 만료·미수집이면 정책에 따라 재수집 */
    private function rows(Product $product)
    {
        $rows = MarketPrice::where('product_id', $product->id)->get();

        $needsFetch = $rows->isEmpty() || $rows->contains(fn (MarketPrice $m) => $m->isStale());
        if ($needsFetch && $this->refreshOnView()) {
            // 같은 상품에 요청이 몰려도 수집은 throttle 간격당 1회만
            $gate = 'market:refresh:'.$product->id;
            if (Cache::add($gate, 1, (int) config('market.refresh_throttle', 300))) {
                $fresh = $this->refresh($product);
                if ($fresh->isNotEmpty()) {
                    return $fresh;
                }
            }
        }

        return $rows;
    }

    /** 조회 시 자동 수집 여부 — 미설정이면 모의 모드에서만 수집 */
    private function refreshOnView(): bool
    {
        $cfg = config('market.refresh_on_view');

        return $cfg === null ? (bool) config('coupang.simulate', true) : (bool) $cfg;
    }

    /**
     * 외부 검색 → 채널별 최저가 1건만 저장. 실패 시 기존 데이터를 유지한다.
     * @return \Illuminate\Support\Collection<int,MarketPrice>
     */
    public function refresh(Product $product)
    {
        $keyword = $this->keyword($product);
        if ($keyword === '') {
            return collect();
        }

        try {
            $found = $this->search->search($keyword, $product->price > 0 ? (int) $product->price : null);
        } catch (\Throwable $e) {
            Log::warning('market.refresh fail', ['product' => $product->id, 'msg' => $e->getMessage()]);

            return collect();
        }

        $allowed = array_keys(config('market.channels', []));
        $best = [];
        foreach ($found as $r) {
            $channel = $r['channel'] ?? 'medical';
            $price = (int) ($r['price'] ?? 0);
            if ($price <= 0 || ! in_array($channel, $allowed, true)) {
                continue;
            }
            if (! isset($best[$channel]) || $price < $best[$channel]['price']) {
                $best[$channel] = $r + ['price' => $price];
            }
        }

        if (! $best) {
            return collect();
        }

        foreach ($best as $channel => $r) {
            MarketPrice::updateOrCreate(
                ['product_id' => $product->id, 'channel' => $channel],
                [
                    'seller'     => mb_substr((string) ($r['seller'] ?? ''), 0, 100),
                    'title'      => mb_substr((string) ($r['title'] ?? $keyword), 0, 200),
                    'price'      => (int) $r['price'],
                    'delivery'   => mb_substr((string) ($r['delivery'] ?? ''), 0, 40) ?: null,
                    'url'        => mb_substr((string) ($r['url'] ?? ''), 0, 500) ?: null,
                    'fetched_at' => now(),
                ],
            );
        }

        // 이번 수집에서 사라진 채널의 낡은 데이터는 정리
        MarketPrice::where('product_id', $product->id)
            ->whereNotIn('channel', array_keys($best))
            ->delete();

        return MarketPrice::where('product_id', $product->id)->get();
    }

    /** 검색 키워드 — 상품명에서 선행 [규격] 태그를 떼고 사용 */
    private function keyword(Product $product): string
    {
        $name = trim(preg_replace('/^\s*\[[^\]]*\]\s*/u', '', (string) $product->name));

        return $name !== '' ? $name : trim((string) $product->name);
    }
}
