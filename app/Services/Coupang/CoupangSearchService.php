<?php

namespace App\Services\Coupang;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * 쿠팡 경쟁가(타사 판매정보) 조회.
 *  - simulate=true : 키워드 기반 모의 검색결과 생성(결정적)
 *  - 실연동 : 쿠팡 파트너스(제휴) 검색 API — 파트너스 키 발급 시 활성화
 */
class CoupangSearchService
{
    private const HOST = 'https://api-gateway.coupang.com';

    private const SEARCH_PATH = '/v2/providers/affiliate_open_api/apis/openapi/v1/products/search';

    /** 가상 판매자(스토어) 풀 */
    private array $sellers = [
        '메디마트', '헬스케어몰', '위드메디', '닥터스토어', '메디큐브', '케어플러스',
        '바이오샵', '한국의료몰', '이지메디', '프로메드', '굿닥터', '메디원',
    ];

    /**
     * 키워드로 경쟁 판매정보 조회.
     * @return array<int,array{seller:string,title:string,price:int,delivery:string,rating:float,review:int,rocket:bool,url:string}>
     */
    public function search(string $keyword, ?int $refPrice = null): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return [];
        }

        if (config('coupang.simulate', true)) {
            return $this->simulate($keyword, $refPrice);
        }

        // 파트너스(제휴) 검색 API 실연동
        return $this->partnersSearch($keyword);
    }

    /** 파트너스 키 설정 여부 */
    public function isReady(): bool
    {
        return ! config('coupang.simulate', true)
            && config('coupang.partners.access_key')
            && config('coupang.partners.secret_key');
    }

    /**
     * 쿠팡 파트너스 상품검색 API 호출 (HMAC CEA 서명).
     * 응답 data.productData[] → 표준 결과 배열로 매핑.
     */
    private function partnersSearch(string $keyword, int $limit = 20): array
    {
        $access = config('coupang.partners.access_key');
        $secret = config('coupang.partners.secret_key');
        if (! $access || ! $secret) {
            return [];
        }

        $query = 'keyword='.rawurlencode($keyword).'&limit='.$limit;
        $datetime = gmdate('ymd\THis\Z');                       // 예: 260701T120000Z
        $message = $datetime.'GET'.self::SEARCH_PATH.$query;
        $signature = hash_hmac('sha256', $message, $secret);
        $authorization = "CEA algorithm=HmacSHA256, access-key={$access}, signed-date={$datetime}, signature={$signature}";

        try {
            $res = Http::withHeaders([
                'Authorization' => $authorization,
                'Content-Type'  => 'application/json;charset=UTF-8',
            ])->timeout(10)->get(self::HOST.self::SEARCH_PATH.'?'.$query);

            if (! $res->successful()) {
                Log::warning('coupang.partners.search fail', ['status' => $res->status(), 'body' => $res->body()]);

                return [];
            }

            $rows = [];
            foreach (($res->json('data.productData') ?? []) as $r) {
                $rows[] = [
                    'seller'   => $r['categoryName'] ?? '쿠팡',
                    'title'    => $r['productName'] ?? $keyword,
                    'price'    => (int) ($r['productPrice'] ?? 0),
                    'delivery' => ! empty($r['isRocket']) ? '로켓배송' : (! empty($r['isFreeShipping']) ? '무료배송' : '-'),
                    'rating'   => 0,
                    'review'   => 0,
                    'rocket'   => (bool) ($r['isRocket'] ?? false),
                    'url'      => $r['productUrl'] ?? 'https://www.coupang.com/np/search?q='.urlencode($keyword),
                ];
            }
            usort($rows, fn ($a, $b) => $a['price'] <=> $b['price']);

            return $rows;
        } catch (\Throwable $e) {
            Log::warning('coupang.partners.search error', ['msg' => $e->getMessage()]);

            return [];
        }
    }

    /** 결정적 모의 결과 생성 (같은 키워드 → 같은 결과) */
    private function simulate(string $keyword, ?int $refPrice): array
    {
        $base = $refPrice && $refPrice > 0 ? $refPrice : (5000 + (crc32($keyword) % 90000));
        mt_srand(crc32($keyword));               // 결정적
        $count = 4 + (crc32($keyword) % 5);      // 4~8건

        $sellers = $this->sellers;
        shuffle($sellers);
        $suffix = ['', ' 대용량', ' 1박스', ' 의료용', ' 낱개', ' 세트', ' 정품', ' 특가'];

        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            // 기준가의 82% ~ 128% 분포
            $rate = mt_rand(82, 128) / 100;
            $price = (int) (round($base * $rate / 10) * 10);
            $rocket = mt_rand(0, 100) < 45;
            $rows[] = [
                'seller'   => $sellers[$i % count($sellers)],
                'title'    => $keyword.$suffix[mt_rand(0, count($suffix) - 1)],
                'price'    => $price,
                'delivery' => $rocket ? '로켓배송' : (mt_rand(0, 1) ? '무료배송' : '2,500원'),
                'rating'   => round(mt_rand(38, 50) / 10, 1),
                'review'   => mt_rand(0, 3200),
                'rocket'   => $rocket,
                'url'      => 'https://www.coupang.com/np/search?q='.urlencode($keyword),
            ];
        }
        mt_srand();

        // 가격 오름차순
        usort($rows, fn ($a, $b) => $a['price'] <=> $b['price']);

        return $rows;
    }
}
