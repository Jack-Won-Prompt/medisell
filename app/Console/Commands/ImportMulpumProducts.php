<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * 물품명.xlsx(→ database/data/mulpum.json) 508개를 medisell 상품으로 임포트.
 * - mediversal식 6개 카테고리로 키워드 자동 분류 (삼에스 임포트와 동일 규칙)
 * - database/data/mulpum_images.json 매칭 이미지(public/product/mulpum/)를 썸네일로 연결
 * - 가격 정보는 원본에 없음 → price=0 (관리자가 추후 입력)
 */
class ImportMulpumProducts extends Command
{
    protected $signature = 'mulpum:import {--file=database/data/mulpum.json}';
    protected $description = '물품명 데이터를 6개 카테고리로 분류·임포트하고 매칭 이미지를 연결';

    private array $cats = [
        'inj-catheter-tube' => ['주사/카테터/튜브', 'syringe', 1],
        'dressing'          => ['드레싱/소독', 'bandage', 2],
        'surgical'          => ['수술용품', 'safety', 3],
        'instrument'        => ['기구/용기류', 'tools', 4],
        'etc-supplies'      => ['기타소모품', 'package', 5],
        'medicine'          => ['의약품', 'drop', 6],
    ];

    private array $rules = [
        'medicine' => ['saline', 'contrast', '조영제', '주사액', '수액', '의약품', '정제', '시럽', 'ophthalmic solution'],
        'inj-catheter-tube' => ['syringe', 'needle', 'catheter', 'cannula', 'tubing', 'tube', 'guidewire', 'guide wire', 'sheath', 'dilator', 'introducer', 'infusion', 'balloon', 'angio', 'stent graft', 'stent', 'port', 'i.v', 'iv set', '주사기', '주사침', '카테터', '튜브', '니들', '벌룬', '와이어', '시스', '수액'],
        'dressing' => ['dressing', 'gauze', 'bandage', 'wound', 'antiseptic', 'swab', 'povidone', 'sponge', ' pad', 'adhesive', 'film', 'foam', 'duoderm', 'aquacel', 'steri', '드레싱', '거즈', '소독', '밴드', '반창고', '패드', '스폰지', '필름', '폼'],
        'instrument' => ['instrument', 'forceps', 'scissors', 'retractor', 'container', 'tray', 'basin', 'bowl', 'bottle', 'canister', 'holder', 'clamp', 'probe', 'blade', 'scalpel', 'depressor', '기구', '용기', '트레이', '포셉', '클램프', '보울', '설압자'],
        'surgical' => ['pack', 'drape', 'gown', 'suture', 'mesh', 'clip', 'stapler', 'graft', 'implant', 'prosthesis', 'screw', 'plate', 'trocar', 'surgical', 'incise', 'anchor', 'fixation', 'glove', 'airway', 'air way', '수술', '팩', '드레이프', '봉합', '메쉬', '임플란트', '스크류', '플레이트', '장갑', '가운', '에어웨이'],
    ];

    public function handle(): int
    {
        $path = base_path($this->option('file'));
        if (! is_file($path)) {
            $this->error("파일 없음: {$path}");
            return 1;
        }
        $rows = json_decode(file_get_contents($path), true);
        $imgMap = [];
        $imgPath = base_path('database/data/mulpum_images.json');
        if (is_file($imgPath)) {
            $imgMap = json_decode(file_get_contents($imgPath), true) ?: [];
        }
        $base = rtrim(config('app.url'), '/');
        $this->info('제품 '.count($rows).'건 임포트 시작 (이미지 매핑 '.count(array_filter($imgMap, fn ($k) => ! str_ends_with($k, '_alt'), ARRAY_FILTER_USE_KEY)).'건)');

        // 카테고리 확보
        $catId = [];
        foreach ($this->cats as $slug => [$name, $icon, $ord]) {
            $catId[$slug] = Category::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'icon' => $icon, 'sort_order' => $ord, 'is_active' => true],
            )->id;
        }

        $created = 0; $updated = 0; $withImg = 0; $dist = [];
        foreach ($rows as $r) {
            $code = trim((string) ($r['code'] ?? ''));
            $name = trim((string) ($r['name'] ?? ''));
            if ($code === '' || $name === '') { continue; }

            $slug = $this->classify($name);
            $dist[$slug] = ($dist[$slug] ?? 0) + 1;
            $maker = trim((string) ($r['groupname'] ?? ''));

            $thumb = null;
            if (! empty($imgMap[$code]) && is_string($imgMap[$code])) {
                $thumb = $base.'/product/mulpum/'.$imgMap[$code];
                $withImg++;
            }

            $attrs = [
                'category_id' => $catId[$slug],
                'name'    => mb_substr($name, 0, 250),
                'slug'    => mb_substr(Str::slug($name) ?: 'item', 0, 235).'-'.Str::lower($code),
                'unit'    => 'EA',
                'maker'   => mb_substr($maker, 0, 250),
                'summary' => $maker !== '' ? $maker : '',
                'price'   => 0,
                'tax_type' => 'taxable',
                'stock'   => 100,
                'is_active' => 1,
            ];
            if ($thumb) { $attrs['thumbnail'] = $thumb; }

            $p = Product::where('code', $code)->first();
            if ($p) {
                // 이미지가 새로 생겼을 때만 갱신 (기존 데이터 보존)
                $u = [];
                if ($thumb && $p->thumbnail !== $thumb) { $u['thumbnail'] = $thumb; }
                if ($u) { $p->update($u); $updated++; }
            } else {
                $attrs['code'] = $code;
                Product::create($attrs);
                $created++;
            }
        }

        $this->info("완료: 신규 {$created}건 · 갱신 {$updated}건 · 이미지 연결 {$withImg}건");
        $this->line('분류 분포:');
        foreach ($dist as $slug => $n) { $this->line("  {$this->cats[$slug][0]}: {$n}"); }
        return 0;
    }

    private function classify(string $name): string
    {
        $n = Str::lower($name);
        foreach ($this->rules as $slug => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($n, $kw)) { return $slug; }
            }
        }
        return 'etc-supplies';
    }
}
