<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * YK병원용품단가.xlsx(한국안전 유한킴벌리 병원용품)에서 미등록 제품을 등록.
 * - 입력: E:/tmp/yk_hosp.json (xlsx에서 추출)
 * - 이미지: YK병원용품단가/*.png (계열별 1장) → public/product/yk/{code}.png
 * - 판매가 = 구매가 × 1.15 (15% 마진), cost = 구매가
 * - dedup: code==모델 또는 name LIKE %모델% → 기존이면 활성화+단가갱신, 없으면 신규
 */
class ImportYkHospital extends Command
{
    protected $signature = 'products:import-yk-hosp {--dry : 미리보기} {--margin=15 : 마진율(%)}';
    protected $description = 'YK 병원용품 단가표에서 미등록 제품 등록(이미지·규격변형 포함)';

    private string $imgDir = 'YK병원용품단가';

    /** 계열 키워드 => [이미지파일, 카테고리ID, group_key(변형묶음, null=단독)] */
    private array $families = [
        '수술용 가운'      => ['YK 수술용 가운.png', 70, 'yk-surgical-gown'],
        '덴탈마스크'       => ['YK 덴탈마스크(일회용마스크).png', 70, null],
        '수술용마스크'     => ['YK 수술용마스크.png', 70, null],
        '간호사용모자'     => ['YK 간호사용모자.png', 70, null],
        '의사용모자'       => ['YK 의사용모자.png', 70, null],
        '깔개매트'         => ['YK 디펜드 에이플러스 깔개매트 .png', 72, null],
        '스마트 수술용글러브' => ['YK 스마트 수술용글러브 라텍스.png', 70, 'yk-smart-surgical-glove'],
        '핸즈키퍼'         => ['YK 핸즈키퍼 파우더프리 라텍스 진료용글러브.png', 70, 'yk-handskeeper-glove'],
        '멸균포'           => ['YK400 국산 멸균포.png', 70, 'yk400-sterile-drape'],
        '힐더스'           => ['YK 힐더스병원용와이퍼.png', 72, null],
    ];

    public function handle(): int
    {
        $json = 'E:/tmp/yk_hosp.json';
        if (! is_file($json)) {
            $this->error("JSON 없음: {$json}");
            return 1;
        }
        $rows = json_decode(file_get_contents($json), true) ?: [];
        $dry = (bool) $this->option('dry');
        $margin = (float) $this->option('margin');

        $destDir = public_path('product/yk');
        if (! $dry && ! is_dir($destDir)) {
            mkdir($destDir, 0775, true);
        }

        $created = 0; $updated = 0; $imgOk = 0; $imgMiss = 0; $rowsOut = [];
        foreach ($rows as $r) {
            $model = (string) $r['model'];
            $buy = (int) $r['buy'];
            $price = (int) round($buy * (1 + $margin / 100));
            [$imgFile, $catId, $groupKey] = $this->familyFor($r['name']);
            $unit = $this->unitFor($r['unit']);
            $code = 'YK'.$model;   // 코드 접두로 기존 mulpum/ZC 코드와 충돌 방지

            // 이미지 복사 (계열 공용 → code별 파일)
            $thumb = null;
            if ($imgFile) {
                $src = base_path($this->imgDir.'/'.$imgFile);
                if (is_file($src)) {
                    $destRel = "product/yk/{$code}.png";
                    if (! $dry) { copy($src, public_path($destRel)); }
                    $thumb = "http://localhost/medisell/{$destRel}";
                    $imgOk++;
                } else {
                    $imgMiss++;
                }
            }

            // 기존 매칭: 코드(YK모델/모델) 또는 이름에 모델번호 포함
            $existing = Product::where('code', $code)->orWhere('code', $model)
                ->orWhere('name', 'like', "%{$model}%")->first();

            $rowsOut[] = sprintf('%-9s [cat%d/%s] %s → %s | 구매 %s → 판매 %s',
                $code, $catId, $unit, mb_substr($r['name'], 0, 30),
                $existing ? ('갱신#'.$existing->id) : '신규',
                number_format($buy), number_format($price));

            if ($dry) { continue; }

            $data = [
                'category_id' => $catId,
                'name'        => $r['name'],
                'code'        => $code,
                'group_key'   => $groupKey,
                'unit'        => $unit,
                'maker'       => '유한킴벌리',
                'spec'        => $r['spec'] ?: null,
                'summary'     => $r['note'] ?: null,
                'price'       => $price,
                'cost'        => $buy,
                'member_price' => 0,
                'tax_type'    => 'taxable',
                'stock'       => 100,
                'is_active'   => true,
            ];
            if ($thumb) {
                $data['thumbnail'] = $thumb;
                $data['images'] = json_encode([$thumb], JSON_UNESCAPED_UNICODE);
            }

            if ($existing) {
                $existing->update($data);
                $updated++;
            } else {
                $data['slug'] = $this->uniqueSlug($r['name'], $code);
                Product::create($data);
                $created++;
            }
        }

        $this->info(($dry ? '[미리보기] ' : '')."완료 · 신규 {$created} · 갱신 {$updated} · 이미지 {$imgOk}(누락 {$imgMiss}) · 마진 {$margin}%");
        foreach ($rowsOut as $s) { $this->line('  '.$s); }
        return 0;
    }

    private function familyFor(string $name): array
    {
        foreach ($this->families as $kw => $meta) {
            if (str_contains($name, $kw)) { return $meta; }
        }
        return [null, 72, null];  // 미매칭 → 기타소모품, 이미지 없음
    }

    private function unitFor(string $u): string
    {
        $u = strtoupper(trim($u));
        return match ($u) {
            'BX'   => 'BOX',
            '카톤', 'CARTON' => '카톤',
            'EA'   => 'EA',
            default => $u ?: 'EA',
        };
    }

    private function uniqueSlug(string $name, string $code): string
    {
        $base = Str::slug($name) ?: Str::lower($code);
        $slug = $base; $i = 1;
        while (Product::where('slug', $slug)->exists()) { $slug = $base.'-'.(++$i); }
        return $slug;
    }
}
