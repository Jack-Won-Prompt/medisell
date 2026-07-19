<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

/**
 * 제품명·제조사를 정규화해 "규격/사이즈만 다른 변형"을 같은 group_key로 묶는다.
 * 상세페이지 규격선택·목록 대표상품 표시에 사용.
 */
class BackfillGroupKey extends Command
{
    protected $signature = 'products:group {--dry : 미리보기}';
    protected $description = '규격/사이즈 변형을 group_key로 묶어 저장';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $byKey = [];  // key => [ids]
        foreach (Product::query()->select('id', 'name', 'maker')->cursor() as $p) {
            $key = $this->baseKey($p->name, $p->maker);
            $byKey[$key][] = $p->id;
        }
        $n = 0; $groups = [];
        foreach ($byKey as $key => $ids) {
            $groups[$key] = count($ids);
            if (! $dry) {
                Product::whereIn('id', $ids)->update(['group_key' => $key]);
            }
            $n += count($ids);
        }
        $multi = collect($groups)->filter(fn ($c) => $c > 1);
        $this->info(($dry ? '[미리보기] ' : '')."group_key 설정 {$n}건 · 전체 그룹 ".count($groups).' · 변형(2개+) 그룹 '.$multi->count());
        $this->line('변형 많은 그룹 예시:');
        foreach ($multi->sortDesc()->take(8) as $k => $c) {
            $this->line("  {$c}개  {$k}");
        }
        return 0;
    }

    private function baseKey(string $name, ?string $maker): string
    {
        // 브랜드: maker 우선, 없으면 이름 앞의 [브랜드] 추출 (다른 브랜드가 합쳐지는 것 방지)
        $brand = $maker;
        if (empty($brand) && preg_match('/^\s*\[([^\]]+)\]/u', $name, $m)) {
            $brand = $m[1];
        }

        $s = preg_replace('/\[[^\]]*\]|\([^)]*\)/u', ' ', $name);
        $s = mb_strtolower($s, 'UTF-8');
        $s = preg_replace('/\b\d+(\.\d+)?\s*(fr|f|g|cc|ml|mm|cm|inch|way|매|개입|호|인치)\b/u', ' ', $s);
        $s = preg_replace('#\b\d+\s*[/x]\s*\d+\b#u', ' ', $s);
        $s = preg_replace('/\b(xxxl|xxl|xl|[smlx])\b/u', ' ', $s);
        $s = preg_replace('/[0-9]+/u', ' ', $s);
        $s = preg_replace('/[^a-z가-힣]+/u', ' ', $s);
        $stop = ['no', 'size', 'type', 'set', 'kit', 'the', 'for', 'and', 'with', 'plus', 'new'];
        $words = [];
        foreach (explode(' ', $s) as $w) {
            if (mb_strlen($w) >= 2 && ! in_array($w, $stop, true)) {
                $words[$w] = true;
            }
        }
        $words = array_keys($words);
        sort($words);
        $mk = $brand ? preg_replace('/[^a-z0-9가-힣]+/u', '', mb_strtolower(mb_substr($brand, 0, 12))) : '';
        $key = trim($mk.'|'.implode(' ', $words));

        return $key === '|' || $key === '' ? mb_strtolower($name) : $key;
    }
}
