<?php

namespace App\Services;

use App\Models\MarketPrice;
use App\Models\Product;
use App\Services\Cafe24\Cafe24MallSearchService;
use App\Services\Coupang\CoupangSearchService;
use App\Services\Naver\NaverShopSearchService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * 인터넷 최저가 비교 — 쿠팡 · 네이버 · 일반 의료소모품몰 채널별 최저가 1건씩(Top 3).
 *
 * 수집원은 설정된 것만 사용하고 결과를 합쳐 채널별 최저가를 고른다.
 *  - NaverShopSearchService : 네이버 검색 API — 네이버·일반몰(+쿠팡몰) 커버
 *  - CoupangSearchService   : SERP(구글쇼핑) 또는 쿠팡 파트너스 — 쿠팡 커버
 * 실연동 수집원이 하나도 없을 때만 모의(simulate) 결과를 쓴다(실데이터와 절대 섞지 않는다).
 *
 * 결과는 market_prices 에 채널당 1건만 남겨 페이지 조회 때 외부 호출이 없도록 한다.
 */
class MarketPriceService
{
    /**
     * medical 채널로 인정할 판매처 키워드.
     * 11번가·G마켓 같은 종합몰이 '의료소모품몰'로 잘못 표기되지 않게, 후보를 이 목록으로 걸러
     * 그 안에서 최저가를 고른다. 해당하는 곳이 없으면 medical 행 없이 2건만 노출된다.
     */
    private const MEDICAL_HINTS = [
        '메디', '메드', 'medi', 'med', '의료', '닥터', 'doctor', '헬스', 'health',
        '케어', 'care', '병원', '약국', '팜', 'pharm', '바이오', 'bio', '위생', '덴탈', 'dental',
    ];

    public function __construct(
        private CoupangSearchService $search,
        private NaverShopSearchService $naver,
        private Cafe24MallSearchService $cafe24,
    ) {}

    /**
     * 상세 페이지용 비교 데이터.
     * @return array{rows:array<int,array>,is_lowest:bool,saving:int,fetched_at:?\Illuminate\Support\Carbon}
     */
    public function compare(Product $product, ?int $sell = null): array
    {
        $empty = [
            'rows' => [], 'is_lowest' => false, 'saving' => 0,
            'fetched_at' => null, 'is_sample' => ! $this->hasLiveProvider(),
        ];

        if (! config('market.enabled', true)) {
            return $empty;
        }

        $sell = $sell !== null ? $sell : $product->priceFor(null);
        $order = array_keys(config('market.channels', []));

        /*
         * 설정에서 빠진 채널(예: 폐기한 쿠팡)의 잔존 행은 판정 전에 걸러낸다.
         * is_sample 을 그 행들까지 보고 계산하면, 안 보여줄 데이터 때문에 영역 전체가 숨는다.
         */
        $rows = $this->rows($product)->filter(fn (MarketPrice $m) => in_array($m->channel, $order, true));
        if ($rows->isEmpty()) {
            return $empty;
        }

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

        $lowest = $out ? min(array_column($out, 'price')) : 0;

        // 마케팅 정책: 우리가 최저가가 아닐 때는 아예 노출하지 않도록 설정할 수 있다
        if (! config('market.show_when_not_lowest', true) && ! ($sell > 0 && $lowest > 0 && $sell <= $lowest)) {
            return $empty;
        }

        return [
            'rows'       => $out,
            'is_lowest'  => $sell > 0 && $lowest > 0 && $sell <= $lowest,
            'saving'     => $sell > 0 && $lowest > $sell ? $lowest - $sell : 0,
            'fetched_at' => $rows->max('fetched_at'),
            /*
             * 실제 시세가 아닌 생성값이 한 건이라도 섞였는지 — 일반 고객에게 노출하면 안 된다.
             * 현재 설정이 아니라 "저장된 행이 어떤 엔진으로 수집됐는지"로 판단해야,
             * 키를 새로 넣은 직후 남아있는 옛 모의데이터가 실데이터로 오인되지 않는다.
             */
            'is_sample'  => $rows->contains(fn (MarketPrice $m) => $m->isSample()),
        ];
    }

    /** 저장된 최저가 — 만료·미수집이면 정책에 따라 재수집 */
    private function rows(Product $product)
    {
        $rows = MarketPrice::where('product_id', $product->id)->get();

        // 만료·미수집이거나, 실연동으로 전환됐는데 저장분이 아직 모의데이터인 경우
        $needsFetch = $rows->isEmpty()
            || $rows->contains(fn (MarketPrice $m) => $m->isStale())
            || ($this->hasLiveProvider() && $rows->contains(fn (MarketPrice $m) => $m->isSample()));
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

    /** 조회 시 자동 수집 여부 — 미설정이면 모의 모드에서만 수집(실연동은 크론) */
    private function refreshOnView(): bool
    {
        $cfg = config('market.refresh_on_view');

        return $cfg === null ? ! $this->hasLiveProvider() : (bool) $cfg;
    }

    /** 실연동 수집원이 하나라도 준비됐는지 (네이버 검색 API / Cafe24 의료몰 / SERP / 쿠팡 파트너스) */
    public function hasLiveProvider(): bool
    {
        return $this->naver->isReady() || $this->cafe24->isReady() || $this->search->isReady();
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

        $ref = $product->price > 0 ? (int) $product->price : null;
        $allowed = array_keys(config('market.channels', []));

        /*
         * 수집원별 결과를 모아 채널 후보를 만든다 (행마다 어느 엔진에서 왔는지 기록).
         * 상품명 전체로는 타사 몰 검색이 잘 안 걸리므로(긴 문자열 AND 매칭) 축약 키워드까지
         * 순차 시도하고, 대신 제목 유사도 가드로 엉뚱한 규격이 섞이는 것을 막는다.
         */
        $candidates = [];
        $rawSeen = 0;
        foreach ($this->providers() as $engine => $provider) {
            foreach ($this->keywords($keyword) as $kw) {
                try {
                    $found = $provider($kw, $ref);
                } catch (\Throwable $e) {
                    Log::warning('market.refresh fail', [
                        'product' => $product->id, 'engine' => $engine, 'msg' => $e->getMessage(),
                    ]);
                    break;
                }

                $picked = 0;
                foreach ($found as $r) {
                    $rawSeen++;                 // 검색 자체는 응답했는지(정리 판단용)
                    $channel = $r['channel'] ?? 'medical';
                    $price = (int) ($r['price'] ?? 0);
                    if ($price <= 0 || ! in_array($channel, $allowed, true)) {
                        continue;
                    }
                    if (! $this->comparable($keyword, (string) ($r['title'] ?? ''))
                        || ! $this->priceSane($price, $ref)) {
                        continue;               // 같은 물건으로 보기 어려운 결과는 버린다
                    }
                    $candidates[$channel][] = $r + ['price' => $price, 'engine' => $engine];
                    $picked++;
                }

                if ($picked > 0) {
                    break;                      // 이 수집원에서 건졌으면 더 축약하지 않는다
                }
            }
        }

        // 채널별 최저가 1건 — medical 은 의료소모품몰만 후보로 인정
        $best = [];
        foreach ($candidates as $channel => $rows) {
            if ($channel === 'medical') {
                $rows = array_values(array_filter($rows, fn ($r) => ($r['engine'] ?? '') === 'cafe24'
                    || $this->looksMedical((string) ($r['seller'] ?? ''))));
                if (! $rows) {
                    continue;
                }
            }
            usort($rows, fn ($a, $b) => $a['price'] <=> $b['price']);
            $best[$channel] = $rows[0];
        }

        // 설정에서 빠진 채널(예: 폐기한 쿠팡)의 행은 표시되지 않으니 항상 정리
        MarketPrice::where('product_id', $product->id)->whereNotIn('channel', $allowed)->delete();

        // 실연동으로 전환된 뒤에는 옛 모의(simulate) 데이터를 남겨두지 않는다
        if ($this->hasLiveProvider()) {
            MarketPrice::where('product_id', $product->id)->where('engine', 'simulate')->delete();
        }

        /*
         * 검색은 응답했는데(rawSeen>0) 통과한 후보가 없는 채널은 낡은 행을 지운다.
         * 가드 도입 전에 저장된 오매칭이 계속 남는 것을 막는다.
         * 반대로 응답이 아예 없었다면(몰 장애·차단) 기존 데이터를 보존하고 TTL 만료를 기다린다.
         */
        if ($rawSeen > 0) {
            MarketPrice::where('product_id', $product->id)
                ->whereNotIn('channel', array_keys($best))
                ->delete();
        }

        if (! $best) {
            return MarketPrice::where('product_id', $product->id)->get();
        }

        foreach ($best as $channel => $r) {
            MarketPrice::updateOrCreate(
                ['product_id' => $product->id, 'channel' => $channel],
                [
                    'engine'     => $r['engine'],
                    'seller'     => mb_substr((string) ($r['seller'] ?? ''), 0, 100),
                    'title'      => mb_substr((string) ($r['title'] ?? $keyword), 0, 200),
                    'price'      => (int) $r['price'],
                    'delivery'   => mb_substr((string) ($r['delivery'] ?? ''), 0, 40) ?: null,
                    'url'        => mb_substr((string) ($r['url'] ?? ''), 0, 500) ?: null,
                    'fetched_at' => now(),
                ],
            );
        }

        return MarketPrice::where('product_id', $product->id)->get();
    }

    /**
     * 사용할 수집원 — [engine => callable(keyword, refPrice): rows].
     * 실연동이 하나라도 있으면 모의 결과는 쓰지 않는다(실데이터와 섞이면 안 됨).
     * @return array<string,callable>
     */
    private function providers(): array
    {
        $providers = [];

        if ($this->naver->isReady()) {
            $providers[$this->naver->engine()] = fn ($kw, $ref) => $this->naver->search($kw, $ref);
        }
        if ($this->cafe24->isReady()) {
            $providers[$this->cafe24->engine()] = fn ($kw, $ref) => $this->cafe24->search($kw, $ref);
        }
        if ($this->search->isReady()) {
            $providers[$this->search->engine()] = fn ($kw, $ref) => $this->search->search($kw, $ref);
        }
        if (! $providers) {
            $providers['simulate'] = fn ($kw, $ref) => $this->search->search($kw, $ref);
        }

        return $providers;
    }

    /**
     * 의료소모품몰로 보이는 판매처명인지.
     * Cafe24 몰 직접 수집분은 이미 의료몰만 등록돼 있으므로 항상 통과시킨다.
     */
    private function looksMedical(string $seller): bool
    {
        $s = mb_strtolower($seller);
        foreach (self::MEDICAL_HINTS as $hint) {
            if (str_contains($s, mb_strtolower($hint))) {
                return true;
            }
        }

        return false;
    }

    /** 검색 키워드 — 상품명에서 선행 [규격] 태그를 떼고 사용 */
    private function keyword(Product $product): string
    {
        $name = trim(preg_replace('/^\s*\[[^\]]*\]\s*/u', '', (string) $product->name));

        return $name !== '' ? $name : trim((string) $product->name);
    }

    /**
     * 시도할 검색어 목록 — 전체 상품명 → 뒤 토큰을 떼어낸 축약형.
     * 규격·수량 토큰(10x10, 1박스, 50매, 3인치 …)이 붙으면 타사 검색에서 0건이 되기 쉽다.
     * @return array<int,string>
     */
    private function keywords(string $name): array
    {
        $tokens = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $list = [$name];

        // 규격·수량으로 보이는 토큰을 제거한 형태
        $core = array_values(array_filter($tokens, fn ($t) => ! preg_match('/^[\d.]+(cc|g|kg|ml|l|mm|cm|m|매|조|개|본|인치|호|박스|bx|ea)?$/iu', $t)
            && ! preg_match('/^\d+\s*[x×]\s*\d+/iu', $t)
            && ! preg_match('/^[\[(].*[\])]$/u', $t)));
        if ($core && count($core) < count($tokens)) {
            $list[] = implode(' ', $core);
        }
        // 앞 2토큰(브랜드+제품군)
        if (count($tokens) > 2) {
            $list[] = implode(' ', array_slice($tokens, 0, 2));
        }

        return array_values(array_unique(array_filter(array_map('trim', $list))));
    }

    /**
     * 우리 상품과 후보가 같은 물건으로 볼 수 있는지 — 의미 있는 토큰이 겹치는지로 판단.
     * "일회용주사기 1CC 26g" 검색이 축약돼 아무 "주사기"나 잡히는 것을 막는다.
     */
    private function comparable(string $name, string $title): bool
    {
        if ($title === '') {
            return false;
        }

        $norm = fn (string $s) => preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($s), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $mine = array_values(array_filter($norm($name), fn ($t) => mb_strlen($t) >= 2 && ! preg_match('/^\d+$/', $t)));
        if (! $mine) {
            return true;                        // 판단 근거가 없으면 통과
        }

        $theirs = $norm($title);
        $hit = 0;
        foreach ($mine as $t) {
            foreach ($theirs as $u) {
                if ($t === $u || (mb_strlen($t) >= 3 && str_contains($u, $t)) || (mb_strlen($u) >= 3 && str_contains($t, $u))) {
                    $hit++;
                    break;
                }
            }
        }

        /*
         * 토큰이 2개 이상이면 최소 2개는 겹쳐야 한다.
         * (절반 규칙만 쓰면 '소독용 에탄올' ↔ '…에탄올 83% 알콜솜' 처럼 한 단어만 겹쳐도 통과한다)
         */
        $need = count($mine) === 1 ? 1 : max(2, (int) ceil(count($mine) * 0.6));

        return $hit >= $need;
    }

    /**
     * 가격 정합성 — 우리 판매가와 배수 차이가 지나치면 다른 규격·수량(박스↔낱개)으로 본다.
     * 예: 1L 에탄올 10,124원에 알콜솜 1,870원이 붙는 것을 막는다.
     */
    private function priceSane(int $price, ?int $ref): bool
    {
        if (! $ref || $ref <= 0) {
            return true;
        }
        $min = (float) config('market.price_band.min', 0.3);
        $max = (float) config('market.price_band.max', 4.0);

        return $price >= $ref * $min && $price <= $ref * $max;
    }
}
