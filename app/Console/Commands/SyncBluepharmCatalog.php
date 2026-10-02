<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 판매 구성을 "블루팜 전체 + 콜로플라스트 전체"로 맞춘다.
 * - 사전 준비: database/data/bluepharm_items.json  (품목/블루팜 판매현황 26년도.xlsx 에서 생성)
 *     코드 => {name, spec, unit, price(블루팜 판매단가, 최근 90일 최빈가), maker, last_sold}
 * - 블루팜 품목: 이름·규격·단위를 블루팜 기준으로 교체, 없으면 신규 등록
 *     정가(price) = 블루팜 단가, 병·의원 회원가(member_price) = 블루팜 단가 × 0.9 (원 단위 반올림)
 * - 콜로플라스트(maker=콜로플라스트) 는 그대로 둔다
 * - 그 외 상품은 삭제 (찜·최저가 등은 FK cascade, 주문 품목은 product_id 만 null)
 * - 전 상품 과세(taxable)
 */
class SyncBluepharmCatalog extends Command
{
    protected $signature = 'bluepharm:sync {--dry : 미리보기(변경 안 함)} {--map=database/data/bluepharm_items.json : 블루팜 품목 파일}';
    protected $description = '블루팜 + 콜로플라스트만 남기고 블루팜 품목을 블루팜 기준(이름·규격·단위·단가)으로 교체';

    private const COLOPLAST = '콜로플라스트';
    private const MEMBER_RATE = 0.9;

    public function handle(): int
    {
        $path = base_path($this->option('map'));
        if (! is_file($path)) {
            $this->error("맵 파일 없음: {$path}");
            return 1;
        }
        $map = json_decode(file_get_contents($path), true) ?: [];
        $dry = (bool) $this->option('dry');
        $buy = $this->loadJson('database/data/sames_buy.json');   // 코드 => [단위, 매입가, 일자]
        $this->info('블루팜 품목 '.count($map).'건'.($dry ? ' [미리보기]' : ''));

        $catId = Category::whereNull('parent_id')->pluck('id', 'slug');
        $classifier = app(ImportMulpumProducts::class);

        $stat = ['created' => 0, 'updated' => 0, 'unit' => 0, 'noprice' => [], 'deleted' => 0];
        $unitSamples = [];
        $contractWarn = [];

        DB::beginTransaction();
        try {
            // 1) 블루팜·콜로플라스트가 아닌 상품 삭제
            $drop = Product::where(fn ($q) => $q->whereNull('maker')->orWhere('maker', '!=', self::COLOPLAST))
                ->where(fn ($q) => $q->whereNull('code')->orWhereNotIn('code', array_keys($map)))
                ->pluck('id');
            $stat['deleted'] = $drop->count();
            $this->line('삭제 대상 '.$drop->count().'건 · 리뷰 '.DB::table('reviews')->whereIn('product_id', $drop)->count()
                .' · 찜 '.DB::table('wishlists')->whereIn('product_id', $drop)->count()
                .' · 병원전용가 '.DB::table('hospital_prices')->whereIn('product_id', $drop)->count()
                .' · 거래처가 '.DB::table('account_prices')->whereIn('product_id', $drop)->count()
                .' · 주문품목(연결만 해제) '.DB::table('order_items')->whereIn('product_id', $drop)->count());
            $drop->chunk(500)->each(fn ($ids) => Product::whereIn('id', $ids)->delete());

            // 2) 블루팜 품목 교체·등록
            foreach ($map as $code => $r) {
                $code = (string) $code;
                $unit = $r['unit'] ?: 'EA';
                $p = Product::where('code', $code)->first();

                $attrs = [
                    'name'      => mb_substr($r['name'], 0, 250),
                    'spec'      => $r['spec'] !== '' ? $r['spec'] : null,
                    'unit'      => $unit,
                    'tax_type'  => 'taxable',
                    // 블루팜에서 0원(사은품)으로만 나간 품목은 팔 가격이 없으니 숨긴다
                    'is_active' => $r['price'] !== null,
                ];
                if ($r['price'] !== null) {
                    $attrs['price'] = (int) round($r['price']);
                    $attrs['member_price'] = (int) round($r['price'] * self::MEMBER_RATE);
                } else {
                    $stat['noprice'][] = $code;
                }

                // 매입가: 삼에스 매입단가의 단위가 블루팜 단위와 같을 때만 쓴다
                $buyCost = (isset($buy[$code]) && strtoupper((string) $buy[$code][0]) === $unit && (int) $buy[$code][1] > 0)
                    ? (int) $buy[$code][1] : null;

                if ($p) {
                    $unitChanged = strtoupper((string) $p->unit) !== $unit;
                    if ($unitChanged) {
                        $stat['unit']++;
                        if (count($unitSamples) < 12) {
                            $unitSamples[] = "{$code} {$p->unit} ".number_format($p->price)."원 → {$unit} ".number_format($attrs['price'] ?? $p->price).'원 · '.mb_substr($r['name'], 0, 24);
                        }
                        // 이전 단위 기준 값은 쓸 수 없다
                        $attrs['cost'] = $buyCost;
                        if (! $dry) {
                            DB::table('market_prices')->where('product_id', $p->id)->delete();
                        }
                        $n = DB::table('hospital_prices')->where('product_id', $p->id)->count()
                            + DB::table('account_prices')->where('product_id', $p->id)->count();
                        if ($n) {
                            $contractWarn[] = "{$code} ({$n}건)";
                        }
                    } elseif ($buyCost && ! $p->cost) {
                        $attrs['cost'] = $buyCost;
                    }
                    $p->fill($attrs);
                    if ($p->isDirty()) {
                        $p->save();
                        $stat['updated']++;
                    }
                } else {
                    Product::create($attrs + [
                        'code'        => $code,
                        'category_id' => $catId[$classifier->classify($r['name'])] ?? $catId['etc-supplies'],
                        'maker'       => $r['maker'] !== '' ? $r['maker'] : null,
                        'summary'     => $r['maker'] ?? '',
                        'price'       => $attrs['price'] ?? 0,
                        'cost'        => $buyCost,
                        'stock'       => 100,
                    ]);
                    $stat['created']++;
                }
            }

            // 3) 전 상품 과세
            $taxFixed = Product::where('tax_type', '!=', 'taxable')->update(['tax_type' => 'taxable']);

            $dry ? DB::rollBack() : DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        $this->info("삭제 {$stat['deleted']} · 신규 {$stat['created']} · 교체 {$stat['updated']} · 단위 변경 {$stat['unit']} · 과세 전환 {$taxFixed}");
        foreach ($unitSamples as $s) {
            $this->line("  {$s}");
        }
        if ($stat['noprice']) {
            $this->warn('블루팜 단가 없음(숨김): '.implode(', ', $stat['noprice']));
        }
        if ($contractWarn) {
            $this->warn('단위가 바뀐 상품의 병원/거래처 전용가 — 확인 필요: '.implode(', ', $contractWarn));
        }
        $this->line('최종 상품 수: '.Product::count().' (콜로플라스트 '.Product::where('maker', self::COLOPLAST)->count().')');

        return 0;
    }

    private function loadJson(string $rel): array
    {
        $f = base_path($rel);

        return is_file($f) ? (json_decode(file_get_contents($f), true) ?: []) : [];
    }
}
