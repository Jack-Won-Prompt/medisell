<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * 삼에스 제품 엑셀(→ database/data/sames_products.json)을 medisell 상품으로 임포트.
 * - mediversal식 6개 카테고리로 키워드 자동 분류
 * - 이미지는 포함하지 않음(요청사항: 데이터만 먼저)
 */
class ImportSamesProducts extends Command
{
    protected $signature = 'sames:import {--file=database/data/sames_products.json} {--limit=0}';
    protected $description = '삼에스 제품 데이터를 6개 카테고리로 분류하여 임포트 (이미지 제외)';

    /** 대분류 정의: slug => [이름, 아이콘, 정렬] */
    private array $cats = [
        'inj-catheter-tube' => ['주사/카테터/튜브', 'syringe', 1],
        'dressing'          => ['드레싱/소독', 'bandage', 2],
        'surgical'          => ['수술용품', 'safety', 3],
        'instrument'        => ['기구/용기류', 'tools', 4],
        'etc-supplies'      => ['기타소모품', 'package', 5],
        'medicine'          => ['의약품', 'drop', 6],
    ];

    /** 분류 키워드 (우선순위 순서대로 첫 매칭) */
    private array $rules = [
        'medicine' => ['saline', 'contrast', '조영제', '주사액', '수액', '의약품', '정제', '시럽', 'ophthalmic solution'],
        'inj-catheter-tube' => ['syringe', 'needle', 'catheter', 'cannula', 'tubing', 'tube', 'guidewire', 'guide wire', 'sheath', 'dilator', 'introducer', 'infusion', 'balloon', 'angio', 'stent graft', 'stent', 'port', '주사기', '주사침', '카테터', '튜브', '니들', '벌룬', '와이어', '시스'],
        'dressing' => ['dressing', 'gauze', 'bandage', 'wound', 'antiseptic', 'swab', 'povidone', 'sponge', ' pad', 'adhesive', 'film', '드레싱', '거즈', '소독', '밴드', '반창고', '패드', '스폰지', '필름'],
        'instrument' => ['instrument', 'forceps', 'scissors', 'retractor', 'container', 'tray', 'basin', 'bowl', 'bottle', 'canister', 'holder', 'clamp', 'probe', 'blade', 'scalpel', '기구', '용기', '트레이', '포셉', '클램프', '보울'],
        'surgical' => ['pack', 'drape', 'gown', 'suture', 'mesh', 'clip', 'stapler', 'graft', 'implant', 'prosthesis', 'screw', 'plate', 'trocar', 'surgical', 'incise', 'anchor', 'fixation', 'kit', 'set', '수술', '팩', '드레이프', '봉합', '메쉬', '임플란트', '스크류', '플레이트'],
    ];

    public function handle(): int
    {
        $path = base_path($this->option('file'));
        if (! is_file($path)) {
            $this->error("파일 없음: {$path}");
            return 1;
        }
        $rows = json_decode(file_get_contents($path), true);
        if ($this->option('limit')) {
            $rows = array_slice($rows, 0, (int) $this->option('limit'));
        }
        $this->info('제품 '.count($rows).'건 임포트 시작');

        // 1) 카테고리 생성 + 기존 대분류는 뒤로
        Category::whereNull('parent_id')
            ->whereNotIn('slug', array_keys($this->cats))
            ->update(['sort_order' => 30]);
        $catId = [];
        foreach ($this->cats as $slug => [$name, $icon, $ord]) {
            $catId[$slug] = Category::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'icon' => $icon, 'sort_order' => $ord, 'is_active' => true],
            )->id;
        }

        // 2) 기존 코드(중복 방지)
        $existing = Product::whereNotNull('code')->pluck('code')->flip();

        $now = now();
        $batch = [];
        $inserted = 0; $skipped = 0; $dist = [];
        foreach ($rows as $i => $r) {
            $code = trim($r['barcode'] ?: ($r['model'] ?: ''));
            if ($code === '') { $code = 'SMS'.str_pad((string) $i, 6, '0', STR_PAD_LEFT); }
            if (isset($existing[$code])) { $skipped++; continue; }
            $existing[$code] = true; // 이번 배치 내 중복도 방지

            $catSlug = $this->classify($r['name']);
            $dist[$catSlug] = ($dist[$catSlug] ?? 0) + 1;

            $maker = $r['maker'] ?: ($r['maker_type'] ?: $r['supplier']);
            $sell = is_numeric($r['sell']) ? (int) $r['sell'] : 0;
            $base = Str::slug($r['name']);
            $slug = ($base !== '' ? $base : 'item').'-'.Str::lower($code);

            $batch[] = [
                'category_id' => $catId[$catSlug],
                'name'    => mb_substr($r['name'], 0, 250),
                'slug'    => mb_substr($slug, 0, 250),
                'code'    => $code,
                'unit'    => $r['unit'] ?: 'EA',
                'maker'   => mb_substr($maker, 0, 250),
                'spec'    => $r['spec'],
                'summary' => trim(($r['grade'] ? $r['grade'].' · ' : '').($r['spec'] ? '규격 '.$r['spec'].' · ' : '').($r['supplier'] ?: '')),
                'price'   => $sell,
                'member_price' => null,
                'tax_type' => 'taxable',
                'stock'   => 100,
                'is_active' => 1,
                'created_at' => $now, 'updated_at' => $now,
            ];
            if (count($batch) >= 300) { Product::insert($batch); $inserted += count($batch); $batch = []; $this->output->write('.'); }
        }
        if ($batch) { Product::insert($batch); $inserted += count($batch); }

        $this->newLine();
        $this->info("완료: 신규 {$inserted}건, 스킵(중복) {$skipped}건");
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
