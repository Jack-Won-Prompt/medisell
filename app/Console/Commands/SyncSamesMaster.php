<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

/**
 * 삼에스 제품.xlsx(제품 마스터)의 제품코드 기준으로 기본단위·기본판매가를 정확 동기화.
 * - 사전 준비: database/data/sames_by_code.json  (제품코드 => [기본단위, 기본판매단가])
 * - 코드 완전일치만 적용(이름매칭 아님). 마스터 판매가=0이면 0(가격문의)으로 정정.
 */
class SyncSamesMaster extends Command
{
    protected $signature = 'mulpum:sync-master {--dry : 미리보기(변경 안 함)} {--map=database/data/sames_by_code.json : 코드=>[단위,판매가] 맵 파일}';
    protected $description = '삼에스 마스터(제품코드) 기준으로 기본단위·기본판매가 동기화';

    public function handle(): int
    {
        $path = base_path($this->option('map'));
        if (! is_file($path)) {
            $this->error("맵 파일 없음: {$path}");
            return 1;
        }
        $map = json_decode(file_get_contents($path), true) ?: [];
        $dry = (bool) $this->option('dry');
        $this->info('마스터 코드맵 '.count($map).'건'.($dry ? ' [미리보기]' : ''));

        $priceSet = 0; $priceClear = 0; $unitSet = 0; $touched = 0;
        foreach (Product::whereIn('code', array_keys($map))->cursor() as $p) {
            [$unit, $sell] = $map[$p->code];
            $sell = (int) $sell;
            $changed = false;

            if ((int) $p->price !== $sell) {
                if (! $dry) { $p->price = $sell; }
                $sell > 0 ? $priceSet++ : $priceClear++;
                $changed = true;
            }
            $unit = mb_substr((string) $unit, 0, 20) ?: 'EA';
            if ($p->unit !== $unit) {
                if (! $dry) { $p->unit = $unit; }
                $unitSet++;
                $changed = true;
            }
            if ($changed) {
                $touched++;
                if (! $dry) { $p->save(); }
            }
        }

        $this->info("변경 {$touched}건 · 판매가 설정 {$priceSet} · 판매가 0(가격문의) 정정 {$priceClear} · 단위 갱신 {$unitSet}");
        return 0;
    }
}
