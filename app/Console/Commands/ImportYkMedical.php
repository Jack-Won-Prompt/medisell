<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * korsafety(한국안전)에서 추출한 유한킴벌리 메디컬용 제품을 medisell로 임포트.
 * - 입력: E:/tmp/yk_medical.json  (korsafety에서 내보낸 30개)
 * - 이미지: korsafety public/shop/img/{ks_id}/photo.jpg → medisell public/product/yk/{code}.jpg
 * - code 기준 dedup (재실행 안전)
 */
class ImportYkMedical extends Command
{
    protected $signature = 'products:import-yk {--dry : 미리보기} {--json=E:/tmp/yk_medical.json}';
    protected $description = 'korsafety 유한킴벌리 메디컬 제품 임포트(이미지 포함)';

    private string $ksImgDir = 'E:/xampp/htdocs/korsafety/public';

    public function handle(): int
    {
        $json = $this->option('json');
        if (! is_file($json)) {
            $this->error("JSON 없음: {$json}");
            return 1;
        }
        $rows = json_decode(file_get_contents($json), true) ?: [];
        $dry = (bool) $this->option('dry');

        $destDir = public_path('product/yk');
        if (! $dry && ! is_dir($destDir)) {
            mkdir($destDir, 0775, true);
        }

        $created = 0; $updated = 0; $imgOk = 0; $imgMiss = 0; $samples = [];
        foreach ($rows as $r) {
            $code = 'YK'.($r['external_no'] ?: $r['ks_id']);
            $name = '[유한킴벌리] '.$this->cleanName($r['name']);
            $catId = $this->categoryFor($r['name']);
            $unit = $this->unitFor($r['name']);

            // 이미지 복사
            $thumb = null;
            $src = $this->ksImgDir.$r['img'];               // /shop/img/{id}/photo.jpg
            $ext = strtolower(pathinfo($r['img'], PATHINFO_EXTENSION) ?: 'jpg');
            $destRel = "product/yk/{$code}.{$ext}";
            if (is_file($src)) {
                if (! $dry) {
                    copy($src, public_path($destRel));
                }
                $thumb = "http://localhost/medisell/{$destRel}";  // 로컬 하위폴더 → 절대경로 유지
                $imgOk++;
            } else {
                $imgMiss++;
            }

            if (count($samples) < 12) {
                $samples[] = "[{$catId}/{$unit}] {$code} · ".mb_substr($name, 0, 40).' · '.number_format($r['price']).'원';
            }

            if ($dry) { continue; }

            $existing = Product::where('code', $code)->first();
            $data = [
                'category_id' => $catId,
                'name'        => $name,
                'slug'        => $existing->slug ?? $this->uniqueSlug($name, $code),
                'code'        => $code,
                'unit'        => $unit,
                'maker'       => '유한킴벌리',
                'price'       => (int) $r['price'],
                'cost'        => 0,
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
                Product::create($data);
                $created++;
            }
        }

        $this->info(($dry ? '[미리보기] ' : '')."임포트 완료 · 신규 {$created} · 갱신 {$updated} · 이미지 {$imgOk} (누락 {$imgMiss})");
        foreach ($samples as $s) { $this->line('  '.$s); }
        return 0;
    }

    /** SKU 코드(4자리+ 숫자)·대괄호 제거해 제품명 정리 */
    private function cleanName(string $raw): string
    {
        $n = str_replace(['[', ']'], ' ', $raw);
        $n = preg_replace('/\b\d{4,}\b/u', ' ', $n);   // 44137, 56764 등 SKU 제거 (500ml·G10·70매 등은 유지)
        $n = preg_replace('/\s+/u', ' ', $n);
        return trim($n, " ·/-");
    }

    private function categoryFor(string $name): int
    {
        $n = mb_strtolower($name);
        $has = fn (array $kw) => (bool) array_filter($kw, fn ($k) => str_contains($n, mb_strtolower($k)));
        if ($has(['손소독', '솝', '비누', '핸드워시', '물티슈', '손세정', '청결제'])) { return 69; }  // 드레싱/소독
        if ($has(['글러브', '장갑', '나이트릴', '라텍스'])) { return 70; }                              // 수술용품(장갑)
        if ($has(['가글'])) { return 72; }                                                              // 기타소모품
        return 70;  // 가운·보호복·캡·커버·앞치마·마스크·방역 → 수술용품
    }

    private function unitFor(string $name): string
    {
        $n = mb_strtolower($name);
        if (str_contains($n, '낱개')) { return 'EA'; }
        if (str_contains($n, '세트')) { return 'SET'; }
        if (str_contains($n, '켤레')) { return '켤레'; }
        if (str_contains($n, '벌')) { return '벌'; }
        if (str_contains($n, '박스') || str_contains($n, '카톤') || str_contains($n, '케이스')) { return 'BOX'; }
        return 'EA';
    }

    private function uniqueSlug(string $name, string $code): string
    {
        $base = Str::slug($name) ?: Str::lower($code);
        $slug = $base;
        $i = 1;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $base.'-'.(++$i);
        }
        return $slug;
    }
}
