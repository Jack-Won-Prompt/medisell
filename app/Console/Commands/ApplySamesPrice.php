<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

/**
 * 삼에스 제품_정리.xlsx의 기본단위·기본판매가를, 이름이 일치하는 (판매가 미설정=0) 상품에 적용.
 * - 사전 준비: database/data/sames_price_by_name.json  (정규화이름 => [단위, 판매가])
 * - 정규화: 소문자 + [..]/(..) 제거 + 영문/숫자/한글만 (파이썬 생성 로직과 동일)
 */
class ApplySamesPrice extends Command
{
    protected $signature = 'mulpum:sames-price {--dry : 미리보기(변경 안 함)} {--min=1 : 이 값 미만 판매가는 건너뜀}';
    protected $description = '삼에스 xlsx의 기본단위·기본판매가를 이름 일치 상품에 적용';

    public function handle(): int
    {
        $path = base_path('database/data/sames_price_by_name.json');
        if (! is_file($path)) {
            $this->error("맵 파일 없음: {$path}");
            return 1;
        }
        $map = json_decode(file_get_contents($path), true) ?: [];
        $dry = (bool) $this->option('dry');
        $min = (int) $this->option('min');
        $this->info('삼에스 가격맵 '.count($map).'건 · 대상: 판매가 0원 상품'.($dry ? ' [미리보기]' : ''));

        $applied = 0; $low = 0; $noMatch = 0; $lowSamples = [];
        foreach (Product::where('price', '<=', 0)->cursor() as $p) {
            $key = $this->norm($p->name);
            if (! isset($map[$key])) { $noMatch++; continue; }
            [$unit, $sell] = $map[$key];
            $sell = (int) $sell;
            if ($sell < $min) {
                $low++;
                if (count($lowSamples) < 10) { $lowSamples[] = "{$sell}원 · ".mb_substr($p->name, 0, 34); }
                continue;
            }
            if (! $dry) {
                $p->price = $sell;
                if ($unit) { $p->unit = mb_substr($unit, 0, 20); }
                $p->save();
            }
            $applied++;
        }

        $this->info("적용 {$applied}건 · 저가(<{$min}원)건너뜀 {$low}건 · 이름불일치 {$noMatch}건");
        if ($lowSamples) {
            $this->line('저가로 건너뛴 예시:');
            foreach ($lowSamples as $s) { $this->line("  {$s}"); }
        }
        return 0;
    }

    private function norm(string $s): string
    {
        $s = preg_replace('/\[[^\]]*\]|\([^)]*\)/u', ' ', mb_strtolower($s, 'UTF-8'));
        return preg_replace('/[^a-z0-9가-힣]/u', '', $s) ?? '';
    }
}
