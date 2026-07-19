<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

/**
 * 낱개(EA) 단가로 표시된 제품을 박스 단가로 변환.
 * - 사전 준비: database/data/mulpum_boxqty.json  (제품코드 => 박스입수량)
 * - 판매가·매입가에 입수량을 곱하고 단위를 BOX로 변경 (마진 유지)
 * - 현재 단위가 낱개(EA/PCS/개/빈값)인 경우만 대상. 이미 BOX/PK면 건너뜀.
 */
class ApplyBoxPrice extends Command
{
    protected $signature = 'mulpum:box-price {--dry : 미리보기(변경 안 함)} {--max=1000 : 이 낱개가 이하만 변환(0=무제한). 고가=이미 박스가일 수 있어 제외} {--min=10 : 이 낱개가 미만은 건너뜀(1원 등 미입력 placeholder 방지)}';
    protected $description = '낱개 단가 제품을 박스 단가(단가×입수, 단위 BOX)로 변환';

    private array $pieceUnits = ['EA', 'PCS', '개', 'T', ''];

    /** 낱개가로 보이더라도 이미 박스/팩 가격이거나 단품 고가인 품목(변환 제외) */
    private array $excludeKeywords = ['장갑', 'glove', '스왑', 'swab', 'dermabond', '탈지면', '코튼', 'cotton'];

    public function handle(): int
    {
        $path = base_path('database/data/mulpum_boxqty.json');
        if (! is_file($path)) {
            $this->error("맵 파일 없음: {$path}");
            return 1;
        }
        $qtyMap = json_decode(file_get_contents($path), true) ?: [];
        $dry = (bool) $this->option('dry');
        $max = (int) $this->option('max');
        $this->info('박스입수 맵 '.count($qtyMap).'건'.($max > 0 ? " · 낱개가 ≤ {$max}원만" : '').($dry ? ' [미리보기]' : ''));

        $done = 0; $skipUnit = 0; $skipNoprice = 0; $skipHigh = 0; $samples = [];
        foreach (Product::whereIn('code', array_keys($qtyMap))->cursor() as $p) {
            $qty = (int) $qtyMap[$p->code];
            if ($qty < 2) { continue; }
            $min = (int) $this->option('min');
            if ((int) $p->price < max(1, $min)) { $skipNoprice++; continue; }   // 1원 등 placeholder 방지
            $curUnit = strtoupper(trim((string) $p->unit));
            if (! in_array($curUnit, $this->pieceUnits, true)) { $skipUnit++; continue; }
            if ($max > 0 && (int) $p->price > $max) { $skipHigh++; continue; }
            if ((int) $p->price >= 100000) { $skipHigh++; continue; }   // 단품 고가(예: DERMABOND) 제외
            $lname = mb_strtolower($p->name, 'UTF-8');
            foreach ($this->excludeKeywords as $kw) {
                if (mb_strpos($lname, mb_strtolower($kw, 'UTF-8')) !== false) { $skipHigh++; continue 2; }
            }

            $oldP = (int) $p->price; $oldC = (int) $p->cost;
            $newP = $oldP * $qty;
            $newC = $oldC > 0 ? $oldC * $qty : $p->cost;
            if (count($samples) < 12) {
                $samples[] = number_format($oldP)." → ".number_format($newP)."원 (×{$qty}) · ".mb_substr($p->name, 0, 26);
            }
            if (! $dry) {
                $p->price = $newP;
                if ($oldC > 0) { $p->cost = $newC; }
                $p->unit = 'BOX';
                $p->save();
            }
            $done++;
        }

        $this->info("박스단가 변환 {$done}건 · 고가제외 {$skipHigh} · 이미 박스/기타단위 {$skipUnit} · 가격없음 {$skipNoprice}");
        foreach ($samples as $s) { $this->line("  {$s}"); }
        return 0;
    }
}
