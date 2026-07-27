<?php

namespace App\Services\Cafe24;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Cafe24 입점 의료소모품몰 검색 — medical 채널 수집원.
 *
 * Cafe24 는 타사가 쓸 수 있는 통합 상품검색 API가 없어, 몰별 표준 검색 페이지
 * (/product/search.html?keyword=…)를 조회해 상품목록에서 판매가를 뽑는다.
 * 목록 마크업은 Cafe24 공통 구조(li#anchorBoxId_… + 판매가 span)를 기준으로 한다.
 *
 * ⚠️ B2B 의료몰은 비로그인 상태에서 판매가를 숨기는 곳이 많다. 그런 몰은 0건으로 건너뛴다.
 */
class Cafe24MallSearchService
{
    private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36';

    /** 몰 하나당 최대 후보 수 — 최저가만 쓰므로 넉넉하지 않아도 된다 */
    private const MAX_PER_MALL = 8;

    public function isReady(): bool
    {
        return (bool) config('market.cafe24_enabled', true) && ! empty(config('market.cafe24_malls', []));
    }

    public function engine(): string
    {
        return 'cafe24';
    }

    /**
     * 등록된 Cafe24 의료몰을 순회하며 상품·가격 수집. 채널은 항상 medical.
     * @return array<int,array{seller:string,channel:string,title:string,price:int,delivery:string,rating:float,review:int,rocket:bool,url:string}>
     */
    public function search(string $keyword, ?int $refPrice = null): array
    {
        $keyword = trim($keyword);
        if ($keyword === '' || ! $this->isReady()) {
            return [];
        }

        $rows = [];
        foreach (config('market.cafe24_malls', []) as $mall) {
            $name = $mall['name'] ?? '의료몰';
            $url = str_replace('%s', urlencode($keyword), (string) ($mall['url'] ?? ''));
            if ($url === '') {
                continue;
            }

            foreach ($this->searchMall($name, $url, $keyword) as $r) {
                $rows[] = $r;
            }
        }

        usort($rows, fn ($a, $b) => $a['price'] <=> $b['price']);

        return $rows;
    }

    /** 몰 한 곳 조회 */
    private function searchMall(string $mallName, string $url, string $keyword): array
    {
        try {
            $res = Http::withHeaders(['User-Agent' => self::UA, 'Accept-Language' => 'ko-KR,ko;q=0.9'])
                ->timeout(12)->get($url);

            if (! $res->successful()) {
                return [];
            }
            $html = $this->toUtf8($res->body());
        } catch (\Throwable $e) {
            Log::warning('cafe24.search error', ['mall' => $mallName, 'msg' => $e->getMessage()]);

            return [];
        }

        $base = preg_replace('#^(https?://[^/]+).*$#', '$1', $url);
        $rows = [];

        foreach ($this->productBlocks($html) as $block) {
            $price = $this->price($block);
            if ($price <= 0) {
                continue;                       // 가격 비공개(로그인 필요) 몰
            }

            $link = $this->link($block);
            $rows[] = [
                'seller'   => $mallName,
                'channel'  => 'medical',
                'title'    => $this->title($block) ?: $keyword,
                'price'    => $price,
                'delivery' => '-',
                'rating'   => 0.0,
                'review'   => 0,
                'rocket'   => false,
                'url'      => $link ? (str_starts_with($link, 'http') ? $link : $base.$link) : $url,
            ];

            if (count($rows) >= self::MAX_PER_MALL) {
                break;
            }
        }

        return $rows;
    }

    /** Cafe24 상품목록 항목 분리 */
    private function productBlocks(string $html): array
    {
        preg_match_all('#<li id="anchorBoxId_\d+".*?(?=<li id="anchorBoxId_|</ul>)#s', $html, $m);

        return $m[0] ?? [];
    }

    /**
     * 판매가 추출. Cafe24 스킨별로 두 형태가 흔하다.
     *  ① <span data-sale="32,750">           (할인율 표시용 데이터 속성)
     *  ② …판매가</span> : <span>32,750원</span>
     * 배송비·적립금 같은 다른 금액을 잡지 않도록 위 두 경로만 본다.
     */
    private function price(string $block): int
    {
        if (preg_match('#data-sale="([0-9,]+)"#', $block, $m)) {
            return (int) str_replace(',', '', $m[1]);
        }
        if (preg_match('#판매가.{0,200}?([0-9]{1,3}(?:,[0-9]{3})+)\s*원#su', $block, $m)) {
            return (int) str_replace(',', '', $m[1]);
        }

        return 0;
    }

    private function title(string $block): string
    {
        if (preg_match('#<img[^>]*\salt="([^"]{4,200})"#', $block, $m)) {
            return trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }
        if (preg_match('#<div class="name">.*?<span[^>]*>([^<]{4,200})#s', $block, $m)) {
            return trim(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        }

        return '';
    }

    private function link(string $block): ?string
    {
        if (preg_match('#href="(/product/[^"]+)"#', $block, $m)) {
            return html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return null;
    }

    /** Cafe24 구형 스킨은 EUC-KR 로 응답하는 곳이 있다 */
    private function toUtf8(string $body): string
    {
        if (preg_match('#charset=["\']?(euc-kr|ks_c_5601-1987|cp949)#i', mb_substr($body, 0, 2000))) {
            return (string) mb_convert_encoding($body, 'UTF-8', 'CP949');
        }

        return $body;
    }
}
