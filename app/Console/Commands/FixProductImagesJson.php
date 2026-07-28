<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * 이중 인코딩된 products.images 를 정상 JSON 배열로 되돌린다.
 *
 * yk 임포터가 json_encode() 한 문자열을 array 캐스팅 컬럼에 넣어,
 * DB 에는 "[\"http:\\/\\/...\"]" 처럼 한 번 더 감싸진 값이 저장됐다.
 * 이 경우 캐스팅 결과가 배열이 아닌 문자열이라 상세 갤러리에서
 * <img src="[&quot;http:...&quot;]"> 같은 깨진 이미지가 그려졌다.
 *
 * URL 자체는 건드리지 않는다 — 도메인 치환은 images:relativize 담당이라
 * 로컬에서 돌려도 안전하다.
 */
class FixProductImagesJson extends Command
{
    protected $signature = 'images:fix-json {--dry : 변경 없이 대상만 표시}';

    protected $description = '이중 인코딩된 products.images 를 정상 배열로 복구';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $fixed = 0;
        $skipped = 0;

        // 접근자를 우회해 원본 값을 본다
        foreach (DB::table('products')->select('id', 'images')->whereNotNull('images')->cursor() as $row) {
            $decoded = json_decode((string) $row->images, true);

            if (! is_string($decoded)) {
                continue;               // 정상 배열이거나 해석 불가 — 그대로 둔다
            }

            $inner = json_decode($decoded, true);
            if (! is_array($inner)) {
                $skipped++;
                $this->warn("  id={$row->id} 안쪽이 배열이 아님 — 건너뜀: ".mb_substr($decoded, 0, 60));

                continue;
            }

            $clean = array_values(array_filter($inner, fn ($u) => is_string($u) && $u !== ''));
            $fixed++;

            if ($dry) {
                $this->line("  id={$row->id} → ".json_encode($clean, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
            } else {
                DB::table('products')->where('id', $row->id)
                    ->update(['images' => json_encode($clean, JSON_UNESCAPED_UNICODE)]);
            }
        }

        $this->info(($dry ? '[미리보기] ' : '').'이중 인코딩 '.$fixed.'건 '.($dry ? '복구 예정' : '복구 완료')
            .($skipped ? " (건너뜀 {$skipped}건)" : ''));

        if (! $dry && $fixed > 0) {
            $bad = Product::query()->cursor()
                ->filter(fn ($p) => $p->getRawOriginal('images') !== null && ! is_array(json_decode((string) $p->getRawOriginal('images'), true)))
                ->count();
            $this->line('  남은 비정상 행: '.$bad);
        }

        return self::SUCCESS;
    }
}
