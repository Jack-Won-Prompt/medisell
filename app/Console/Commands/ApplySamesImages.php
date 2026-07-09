<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

/**
 * 스크래퍼가 내려받은 매칭 이미지(public/product/sames/)를
 * database/data/sames_images.json({code:파일명}) 매핑에 따라 상품 thumbnail 에 적용.
 */
class ApplySamesImages extends Command
{
    protected $signature = 'sames:images {--map=database/data/sames_images.json}';
    protected $description = '다운로드된 매칭 이미지를 상품 썸네일로 적용';

    public function handle(): int
    {
        $path = base_path($this->option('map'));
        if (! is_file($path)) {
            $this->error("매핑 파일 없음: {$path}");
            return 1;
        }
        $map = json_decode(file_get_contents($path), true) ?: [];
        $this->info('이미지 매핑 '.count($map).'건 적용 시작');

        $applied = 0; $missingFile = 0; $noProduct = 0;
        foreach ($map as $code => $file) {
            if (! is_file(public_path('product/sames/'.$file))) { $missingFile++; continue; }
            $url = asset('product/sames/'.$file);
            // 로컬 MariaDB table_definition_cache 간헐 오류 내성: 실패 시 FLUSH 후 1회 재시도
            $n = 0;
            for ($try = 0; $try < 3; $try++) {
                try {
                    $n = Product::where('code', $code)->update(['thumbnail' => $url]);
                    break;
                } catch (\Throwable $e) {
                    try { \DB::statement('FLUSH TABLES'); } catch (\Throwable $e2) {}
                    usleep(200000);
                }
            }
            if ($n) { $applied += $n; } else { $noProduct++; }
        }
        $this->info("완료: 적용 {$applied}건 / 파일없음 {$missingFile} / 대상상품없음 {$noProduct}");
        return 0;
    }
}
