<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * 노출 제품의 실제 제조사(maker)로 추천 브랜드를 구성하고 제품을 brand_id로 연결.
 * 콜로 제품 라인만 나오던 문제 해결 — 3M·BD·ETHICON·성심·세운 등 실제 브랜드 노출.
 */
class SyncBrands extends Command
{
    protected $signature = 'brands:sync {--min=3 : 최소 노출 제품 수} {--dry : 미리보기}';
    protected $description = '제품 제조사로 추천 브랜드 구성 + 제품 brand_id 연결';

    /** 브랜드가 아닌 제조사/분류 값 제외 */
    private array $exclude = ['의약외품', '기타', '수입', '국산', '공용', '-'];

    public function handle(): int
    {
        $min = (int) $this->option('min');
        $dry = (bool) $this->option('dry');

        $makers = Product::active()->whereNotNull('maker')->where('maker', '!=', '')
            ->selectRaw('maker, count(*) as c')->groupBy('maker')
            ->having('c', '>=', $min)->orderByDesc('c')->get();

        $sort = 1; $created = 0; $linked = 0; $rows = [];
        foreach ($makers as $m) {
            $maker = trim($m->maker);
            if (in_array($maker, $this->exclude, true)) { continue; }

            if (! $dry) {
                $brand = Brand::firstOrCreate(
                    ['name' => $maker],
                    ['slug' => (Str::slug($maker) ?: 'brand-'.Str::lower(Str::random(6))), 'is_active' => true, 'sort_order' => $sort],
                );
                if ($brand->wasRecentlyCreated) { $created++; }
                $brand->update(['is_active' => true, 'sort_order' => $sort]);
                $linked += Product::where('maker', $maker)->update(['brand_id' => $brand->id]);
            }
            $rows[] = "{$sort}. {$maker} ({$m->c})";
            $sort++;
        }

        // 활성 제품 없는 브랜드는 비활성화 (콜로 제품 라인 등)
        if (! $dry) {
            $activeBrandIds = Product::active()->whereNotNull('brand_id')->distinct()->pluck('brand_id');
            Brand::whereNotIn('id', $activeBrandIds)->update(['is_active' => false]);
        }

        $this->info(($dry ? '[미리보기] ' : '')."브랜드 ".count($rows)."개 · 신규 {$created} · 제품연결 {$linked}");
        foreach (array_slice($rows, 0, 15) as $r) { $this->line('  '.$r); }
        return 0;
    }
}
