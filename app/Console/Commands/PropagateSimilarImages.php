<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

/**
 * 유사(같은 기본상품, 규격/사이즈만 다른) 상품에 동일 이미지를 전파.
 * - 그룹키 = 제조사 + 정규화된 상품명(브랜드/사이즈/규격/숫자 제거)
 * - 그룹 내 이미지 보유 상품의 썸네일을 이미지 없는 형제에 복사
 */
class PropagateSimilarImages extends Command
{
    protected $signature = 'sames:propagate {--dry : 미리보기(적용 안 함)} {--min=2 : 그룹 최소 핵심단어수}';
    protected $description = '유사 판단(같은 기본상품) 상품에 동일 이미지 전파';

    public function handle(): int
    {
        $products = Product::select('id', 'name', 'maker', 'thumbnail', 'code')->get();
        $groups = [];
        foreach ($products as $p) {
            $key = $this->baseKey($p->name, $p->maker);
            if ($key === '' || count(explode(' ', $key)) < (int) $this->option('min')) {
                continue; // 핵심단어 너무 적으면(과전파 위험) 제외
            }
            $groups[$key][] = $p;
        }

        $dry = $this->option('dry');
        $applied = 0; $groupsUsed = 0; $samples = [];
        foreach ($groups as $key => $items) {
            $withImg = array_values(array_filter($items, fn ($p) => $p->thumbnail));
            $without = array_values(array_filter($items, fn ($p) => ! $p->thumbnail));
            if (! $withImg || ! $without) continue;
            $src = $withImg[0]->thumbnail;
            $groupsUsed++;
            if (count($samples) < 12) {
                $samples[] = "[{$key}] +".count($without)."개  ⟵ ".mb_substr($withImg[0]->name, 0, 30);
            }
            foreach ($without as $p) {
                if (! $dry) {
                    for ($t = 0; $t < 3; $t++) {
                        try { Product::where('id', $p->id)->update(['thumbnail' => $src]); break; }
                        catch (\Throwable $e) { try { \DB::statement('FLUSH TABLES'); } catch (\Throwable $e2) {} usleep(150000); }
                    }
                }
                $applied++;
            }
        }

        $this->info(($dry ? '[DRY] ' : '')."전파 그룹 {$groupsUsed}개 · 대상 상품 {$applied}개");
        foreach ($samples as $s) $this->line('  '.$s);
        return 0;
    }

    /** 브랜드/사이즈/규격/숫자 제거 후 핵심 단어들(정렬)로 그룹키 */
    private function baseKey(string $name, ?string $maker): string
    {
        $s = preg_replace('/\[[^\]]*\]|\([^)]*\)/u', ' ', $name);
        $s = mb_strtolower($s, 'UTF-8');
        // 사이즈/규격 토큰 제거
        $s = preg_replace('/\b\d+(\.\d+)?\s*(fr|f|g|cc|ml|mm|cm|inch|way|매|개입|호|인치)\b/u', ' ', $s);
        $s = preg_replace('#\b\d+\s*[/x]\s*\d+\b#u', ' ', $s);     // 1/0, 16x24
        $s = preg_replace('/\b(xxxl|xxl|xl|[smlx])\b/u', ' ', $s); // 사이즈 문자
        $s = preg_replace('/[0-9]+/u', ' ', $s);                    // 남은 숫자
        $s = preg_replace('/[^a-z가-힣]+/u', ' ', $s);              // 문자만
        // 일반어 제거
        $stop = ['no', 'size', 'type', 'set', 'kit', 'the', 'for', 'and', 'with', 'plus', 'new'];
        $words = [];
        foreach (explode(' ', $s) as $w) {
            if (mb_strlen($w) >= 2 && ! in_array($w, $stop, true)) $words[$w] = true;
        }
        $words = array_keys($words);
        sort($words);
        $mk = $maker ? preg_replace('/[^a-z가-힣]+/u', '', mb_strtolower(mb_substr($maker, 0, 10))) : '';
        return trim($mk.'|'.implode(' ', $words));
    }
}
