<?php

namespace App\Console\Commands;

use App\Models\Concerns\HasImagePaths;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * DB에 굳어 있는 이미지 절대경로를 상대경로로 정규화한다.
 *
 * 저장은 상대경로(`product/...`), 출력은 모델 접근자가 asset() 으로 조립하므로
 * 같은 DB를 로컬(서브폴더)·운영(도메인 루트)에서 그대로 쓸 수 있다.
 * 특정 호스트에 묶이지 않으므로 도메인 이전·환경 이동 시 재작업이 필요 없다.
 *
 *   php artisan images:relativize            # 전 컬럼 정규화
 *   php artisan images:relativize --dry      # 미리보기(변경 없음)
 *
 * 외부(제휴) 이미지 URL과 data URI 는 건드리지 않는다.
 */
class RelativizeImageUrls extends Command
{
    use HasImagePaths;

    protected $signature = 'images:relativize {--dry : 실제 변경 없이 대상 건수만 표시}';

    protected $description = '이미지 절대경로를 상대경로로 정규화 (환경 독립)';

    /** [테이블 => [단일경로 컬럼...], json 배열 컬럼, HTML 컬럼] */
    private const TARGETS = [
        'products' => ['path' => ['thumbnail'], 'json' => ['images'], 'html' => ['description']],
        'banners'  => ['path' => ['image']],
        'ads'      => ['path' => ['image']],
        'brands'   => ['path' => ['logo']],
        'categories' => ['path' => []],
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $this->info(($dry ? '[미리보기] ' : '').'이미지 경로 정규화 — 절대 URL·서브폴더 접두사 제거');

        $total = 0;
        foreach (self::TARGETS as $table => $cols) {
            if (! DB::getSchemaBuilder()->hasTable($table)) {
                continue;
            }
            $changed = 0;

            foreach (DB::table($table)->select('id')->orderBy('id')->cursor() as $row) {
                $orig = (array) DB::table($table)->where('id', $row->id)->first();
                $update = [];

                foreach ($cols['path'] ?? [] as $c) {
                    if (! array_key_exists($c, $orig)) {
                        continue;
                    }
                    $new = self::toRelativeImagePath($orig[$c]);
                    if ($new !== $orig[$c]) {
                        $update[$c] = $new;
                    }
                }

                foreach ($cols['json'] ?? [] as $c) {
                    if (! array_key_exists($c, $orig) || ! is_string($orig[$c]) || $orig[$c] === '') {
                        continue;
                    }
                    $list = json_decode($orig[$c], true);
                    if (is_string($list)) {                       // 이중 인코딩 데이터 방어
                        $list = json_decode($list, true);
                    }
                    if (! is_array($list)) {
                        continue;
                    }
                    $fixed = array_values(array_filter(array_map(
                        fn ($u) => is_string($u) ? self::toRelativeImagePath($u) : null,
                        $list,
                    )));
                    $json = json_encode($fixed, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    if ($json !== $orig[$c]) {
                        $update[$c] = $json;
                    }
                }

                foreach ($cols['html'] ?? [] as $c) {
                    if (! array_key_exists($c, $orig) || ! is_string($orig[$c]) || $orig[$c] === '') {
                        continue;
                    }
                    $new = preg_replace_callback(
                        '#https?://[^"\'\s)]+\.(?:png|jpe?g|gif|webp|svg)#i',
                        fn ($m) => self::isOwnHost($m[0]) ? (string) self::toRelativeImagePath($m[0]) : $m[0],
                        $orig[$c],
                    );
                    if ($new !== $orig[$c]) {
                        $update[$c] = $new;
                    }
                }

                if ($update) {
                    $changed++;
                    if (! $dry) {
                        DB::table($table)->where('id', $row->id)->update($update);
                    }
                }
            }

            $total += $changed;
            if ($changed) {
                $this->line("  {$table}: {$changed}건 ".($dry ? '변환 예정' : '변환'));
            }
        }

        $this->info(($dry ? '변환 예정 ' : '변환 완료 ')."총 {$total}건");

        return self::SUCCESS;
    }
}
