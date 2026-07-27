<?php

namespace App\Services\Naver;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 네이버 개발자센터 검색 API(쇼핑) 기반 경쟁가 조회.
 * 결과의 mallName 으로 채널을 나눈다 — 쿠팡 / 네이버(가격비교·스마트스토어) / 그 외 일반몰.
 * 클라이언트 ID·Secret 이 설정되면 자동 활성화된다.
 */
class NaverShopSearchService
{
    public function isReady(): bool
    {
        return (bool) (config('naver.shop.client_id') && config('naver.shop.client_secret'));
    }

    public function engine(): string
    {
        return 'naver_api';
    }

    /**
     * @return array<int,array{seller:string,channel:string,title:string,price:int,delivery:string,rating:float,review:int,rocket:bool,url:string}>
     */
    public function search(string $keyword, ?int $refPrice = null): array
    {
        $keyword = trim($keyword);
        if ($keyword === '' || ! $this->isReady()) {
            return [];
        }

        try {
            $res = Http::withHeaders([
                'X-Naver-Client-Id'     => config('naver.shop.client_id'),
                'X-Naver-Client-Secret' => config('naver.shop.client_secret'),
            ])->timeout(12)->get(config('naver.shop.endpoint'), [
                'query'   => $keyword,
                'display' => max(1, min(100, (int) config('naver.shop.display', 40))),
                'sort'    => 'asc',                 // 가격 오름차순
            ]);

            if (! $res->successful()) {
                Log::warning('naver.shop fail', [
                    'status' => $res->status(),
                    'body'   => mb_substr($res->body(), 0, 300),
                ]);

                return [];
            }

            $rows = [];
            foreach ($res->json('items') ?? [] as $it) {
                $price = (int) ($it['lprice'] ?? 0);
                if ($price <= 0) {
                    continue;
                }
                $mall = trim((string) ($it['mallName'] ?? ''));
                $link = (string) ($it['link'] ?? '');

                $rows[] = [
                    'seller'   => $mall !== '' ? $mall : '네이버쇼핑',
                    'channel'  => $this->channel($mall, $link),
                    // title 에는 <b> 태그와 HTML 엔티티가 섞여 온다
                    'title'    => html_entity_decode(strip_tags((string) ($it['title'] ?? $keyword)), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                    'price'    => $price,
                    'delivery' => '-',
                    'rating'   => 0.0,
                    'review'   => 0,
                    'rocket'   => false,
                    'url'      => $link !== '' ? $link : 'https://search.shopping.naver.com/search/all?query='.urlencode($keyword),
                ];
            }
            usort($rows, fn ($a, $b) => $a['price'] <=> $b['price']);

            return $rows;
        } catch (\Throwable $e) {
            Log::warning('naver.shop error', ['msg' => $e->getMessage()]);

            return [];
        }
    }

    /** 판매몰명·링크로 채널 판별 — coupang | naver | medical */
    private function channel(string $mall, string $link): string
    {
        $hay = mb_strtolower($mall.' '.$link);

        if (str_contains($hay, '쿠팡') || str_contains($hay, 'coupang')) {
            return 'coupang';
        }
        // mallName='네이버' 는 네이버쇼핑 가격비교 상품
        if ($mall === '' || str_contains($hay, '네이버') || str_contains($hay, 'naver')
            || str_contains($hay, '스마트스토어') || str_contains($hay, 'smartstore')) {
            return 'naver';
        }

        return 'medical';
    }
}
