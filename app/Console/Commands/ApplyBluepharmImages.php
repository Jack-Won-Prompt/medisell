<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

/**
 * scratchpad_bluepharm_img.py 가 받은 이미지(public/product/bluepharm/)를 대표 이미지로 연결.
 * - 사전 준비: database/data/bluepharm_images.json  (코드 => 파일명, 코드_alt => 매칭 상품명, 코드_x => 교차확인 사이트 수)
 * - 대표 이미지가 비어 있는 상품만 채운다 (기존 이미지는 덮어쓰지 않음)
 */
class ApplyBluepharmImages extends Command
{
    protected $signature = 'bluepharm:images {--dry : 미리보기(변경 안 함)} {--map=database/data/bluepharm_images.json : 이미지 매핑 파일}';
    protected $description = '블루팜 품목 이미지(public/product/bluepharm)를 비어 있는 대표 이미지에 연결';

    public function handle(): int
    {
        $path = base_path($this->option('map'));
        if (! is_file($path)) {
            $this->error("맵 파일 없음: {$path}");
            return 1;
        }
        $map = json_decode(file_get_contents($path), true) ?: [];
        $dry = (bool) $this->option('dry');

        $set = 0; $missingFile = 0; $lowX = [];
        foreach ($map as $code => $file) {
            if (str_starts_with((string) $code, '_') || preg_match('/_(alt|src|x)$/', (string) $code) || ! is_string($file)) {
                continue;
            }
            if (! is_file(public_path('product/bluepharm/'.$file))) {
                $missingFile++;
                continue;
            }
            $p = Product::where('code', $code)->first();
            if (! $p || filled($p->getRawOriginal('thumbnail'))) {
                continue;
            }
            if ((int) ($map[$code.'_x'] ?? 0) <= 1) {
                $lowX[] = "{$code} {$p->name} ← ".($map[$code.'_alt'] ?? '');
            }
            if (! $dry) {
                $p->thumbnail = 'product/bluepharm/'.$file;   // 접근자가 출력 시 asset() 으로 조립
                $p->save();
            }
            $set++;
        }

        $this->info(($dry ? '[미리보기] ' : '')."대표 이미지 연결 {$set}건 · 파일 없음 {$missingFile}");
        if ($lowX) {
            $this->warn('한 사이트에서만 찾은 이미지(눈으로 확인 권장) '.count($lowX).'건:');
            foreach ($lowX as $s) {
                $this->line('  '.mb_substr($s, 0, 90));
            }
        }

        return 0;
    }
}
