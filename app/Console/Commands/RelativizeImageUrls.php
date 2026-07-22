<?php

namespace App\Console\Commands;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Product;
use Illuminate\Console\Command;

/**
 * DB에 저장된 이미지 절대경로(http://localhost/medisell/public/...)를
 * 상대경로(/product/...)로 일괄 변환한다. (도메인 이전/배포용)
 *
 * 예) php artisan images:relativize
 *     php artisan images:relativize --from="http://localhost/medisell/public" --to=""
 *     php artisan images:relativize --dry   (미리보기, 변경 안 함)
 */
class RelativizeImageUrls extends Command
{
    protected $signature = 'images:relativize
        {--from= : 제거할 절대경로 접두사 (기본: http://localhost/medisell)}
        {--to= : 대체 문자열 (기본: 빈 문자열 → /product/... 형태)}
        {--dry : 실제 변경 없이 대상 건수만 표시}';

    protected $description = '이미지 절대경로(localhost)를 상대경로로 일괄 변환';

    public function handle(): int
    {
        // 저장 형식이 http://localhost/medisell/product/... (하위폴더, /public 없음)
        $from = rtrim($this->option('from') ?: 'http://localhost/medisell', '/');
        $to = (string) ($this->option('to') ?? '');
        $dry = (bool) $this->option('dry');

        $rep = fn ($s) => is_string($s) ? str_replace($from, $to, $s) : $s;

        $this->info(($dry ? '[미리보기] ' : '').'변환: "'.$from.'" → "'.($to === '' ? '(제거)' : $to).'"');

        // 상품 (thumbnail, images[], description)
        $pCount = 0;
        foreach (Product::query()->cursor() as $p) {
            $changed = false;

            if (is_string($p->thumbnail) && str_contains($p->thumbnail, $from)) {
                $p->thumbnail = $rep($p->thumbnail);
                $changed = true;
            }
            if (is_string($p->description) && str_contains($p->description, $from)) {
                $p->description = $rep($p->description);
                $changed = true;
            }
            if (is_array($p->images)) {
                $imgs = array_map($rep, $p->images);
                if ($imgs !== $p->images) {
                    $p->images = $imgs;
                    $changed = true;
                }
            }

            if ($changed) {
                $pCount++;
                if (! $dry) {
                    $p->save();
                }
            }
        }

        // 배너, 브랜드 로고
        $bCount = $this->fixColumn(Banner::class, 'image', $from, $rep, $dry);
        $brCount = $this->fixColumn(Brand::class, 'logo', $from, $rep, $dry);

        $this->info("상품 {$pCount}건, 배너 {$bCount}건, 브랜드 {$brCount}건 ".($dry ? '변환 예정' : '변환 완료'));

        return self::SUCCESS;
    }

    private function fixColumn(string $model, string $col, string $from, callable $rep, bool $dry): int
    {
        $count = 0;
        foreach ($model::query()->where($col, 'like', '%'.$from.'%')->cursor() as $row) {
            if (! $dry) {
                $row->{$col} = $rep($row->{$col});
                $row->save();
            }
            $count++;
        }

        return $count;
    }
}
