<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * colscare(cols) DB의 카테고리·상품 + 이미지를 medisell 스키마로 가져온다.
 * - 원본: 콜로플라스트 장루/요루/상처치료/카테터/스킨케어 실제 상품
 * - 썸네일: colscare/storage/app/public/product/thumbnail/{thumbnail} → medisell/public/product/{thumbnail}
 */
class ColsImportSeeder extends Seeder
{
    /** colscare 원본 storage 루트 */
    private string $colsPublic = 'E:/xampp/htdocs/colscare/storage/app/public/';

    /** 대분류(old id) → 아이콘 키 */
    private array $rootIcons = [
        1 => 'bandage',   // 상처 치료
        2 => 'ivbag',     // 장루.요루관리
        3 => 'drop',      // 요루 주머니
        4 => 'catheter',  // 친수성 코팅 카테터
        7 => 'shield',    // 스킨 케어
    ];

    /** 상품명에서 추출할 브랜드(제품 라인) 키워드 */
    private array $brandKeywords = [
        '바이아테인', '컴필', '알터나', '이지플렉스', '브라바', '센슈라', '컨빈', '스피디캐스', '이지캐스', '장루전용',
    ];

    public function run(): void
    {
        // 재실행 안전: 상품이 이미 있으면 재임포트하지 않는다.
        // (서버 등 colscare 'cols' 연결이 없는 환경에서도 여기서 안전하게 스킵)
        if (Product::query()->exists()) {
            $this->command?->warn('ColsImport: 상품이 이미 존재 → colscare 임포트 스킵');
            return;
        }

        $cols = DB::connection('cols');

        $catMap = $this->importCategories($cols);
        $this->importProducts($cols, $catMap);
    }

    /** 카테고리 트리 가져오기. 반환: [old_id => new_id] */
    private function importCategories($cols): array
    {
        $rows = $cols->table('categories')->orderBy('parent_id')->orderBy('position')->orderBy('id')->get();

        // old_id => row
        $byId = [];
        foreach ($rows as $r) {
            $byId[$r->id] = $r;
        }

        // 루트 아이콘을 자식에게 물려주기 위해, old_id → root_old_id 추적
        $rootOf = function ($id) use (&$byId, &$rootOf) {
            $r = $byId[$id] ?? null;
            if (! $r) {
                return $id;
            }

            return ($r->parent_id == 0) ? $id : $rootOf($r->parent_id);
        };

        $map = [];          // old_id => new_id
        $pending = $rows->all();
        $guard = 0;

        // 부모가 먼저 매핑될 때까지 반복 (레벨 순서 보장)
        while ($pending && $guard++ < 20) {
            $next = [];
            foreach ($pending as $r) {
                $parentNew = null;
                if ($r->parent_id != 0) {
                    if (! isset($map[$r->parent_id])) {
                        $next[] = $r;            // 부모 아직 미매핑 → 다음 라운드
                        continue;
                    }
                    $parentNew = $map[$r->parent_id];
                }

                $rootIcon = $this->rootIcons[$rootOf($r->id)] ?? 'box';
                $cat = Category::create([
                    'parent_id' => $parentNew,
                    'name'      => $r->name,
                    'slug'      => 'cat-'.$r->id,
                    'icon'      => $rootIcon,
                    'sort_order' => $r->position ?? 0,
                    'is_active' => true,
                ]);
                $map[$r->id] = $cat->id;
            }
            $pending = $next;
        }

        return $map;
    }

    private function importProducts($cols, array $catMap): void
    {
        $rows = $cols->table('products')->where('status', 1)->orderBy('id')->get();

        $brandCache = [];   // name => Brand
        $i = 0;

        foreach ($rows as $r) {
            $price = (int) round($r->unit_price);
            $name = trim(preg_replace('/\s+/', ' ', $r->name));

            // 잡음(테스트/가격없음) 제외
            if ($price < 100 || str_starts_with($name, '[]')) {
                continue;
            }

            // 가장 하위 카테고리에 매핑
            $oldCat = $this->firstNonEmpty([$r->sub_sub_category_id, $r->sub_category_id, $r->category_id]);
            $newCat = $catMap[$oldCat] ?? ($catMap[$r->category_id] ?? null);
            if (! $newCat) {
                continue;
            }

            // 브랜드(제품 라인) 추출
            $brand = $this->resolveBrand($name, $brandCache);

            // 사업자 회원가: 정가의 약 90% (10원 단위)
            $member = (int) (floor($price * 0.9 / 10) * 10);
            $stock = max(0, min((int) $r->current_stock, 9999));

            // 대표 썸네일 + 갤러리 + 상세설명(이미지 포함) 가져오기
            $thumb = $this->copyProductImage($r->thumbnail);
            $gallery = $this->importGallery($r->images);
            $desc = $this->importDetails($r->details, $name);

            $i++;
            $product = Product::create([
                'category_id'  => $newCat,
                'brand_id'     => $brand?->id,
                'name'         => $name,
                'slug'         => $this->uniqueSlug($r),
                'code'         => $r->code ?: ('MS'.$r->id),
                'unit'         => $r->unit ?: 'EA',
                'maker'        => '콜로플라스트',
                'summary'      => $r->short_name ?: Str::limit($name, 40),
                'description'  => $desc,
                'spec'         => "제품명: {$name}\n판매단위: ".($r->unit ?: 'EA')."\n상품코드: ".($r->code ?: $r->id),
                'price'        => $price,
                'member_price' => $member,
                'stock'        => $stock,
                'thumbnail'    => $thumb,
                'images'       => $gallery,
                'is_active'    => true,
                'is_featured'  => $i % 7 === 0,
                'is_best'      => $i % 5 === 0,
                'is_new'       => $i % 9 === 0,
                'view_count'   => random_int(10, 2000),
                'sort_order'   => $i,
            ]);

            if ($i % 4 === 0) {
                Review::create([
                    'product_id' => $product->id, 'author_name' => '구매회원',
                    'rating' => random_int(4, 5), 'title' => '잘 받았습니다',
                    'body' => '정품이고 배송도 빨라요. 잘 쓰겠습니다.',
                ]);
            }
        }
    }

    private function firstNonEmpty(array $vals): ?int
    {
        foreach ($vals as $v) {
            if ($v !== null && $v !== '' && (int) $v > 0) {
                return (int) $v;
            }
        }

        return null;
    }

    private function resolveBrand(string $name, array &$cache): ?Brand
    {
        $line = '콜로플라스트';
        foreach ($this->brandKeywords as $kw) {
            if (str_contains($name, $kw)) {
                $line = $kw;
                break;
            }
        }
        if (! isset($cache[$line])) {
            $cache[$line] = Brand::firstOrCreate(
                ['name' => $line],
                ['slug' => 'brand-'.(count($cache) + 1), 'sort_order' => count($cache), 'is_active' => true]
            );
        }

        return $cache[$line];
    }

    private function uniqueSlug($r): string
    {
        $base = $r->slug ?: ('p'.$r->id);
        $base = Str::slug($base) ?: ('p'.$r->id);

        return $base.'-'.$r->id;
    }

    /** 상품 이미지(썸네일 변형 우선)를 public/product/ 로 복사. 반환: asset URL */
    private function copyProductImage(?string $rel): ?string
    {
        if (! $rel) {
            return null;
        }
        $rel = ltrim($rel, '/');

        return $this->copyFile('product/'.$rel, [
            $this->colsPublic.'product/thumbnail/'.$rel,  // 압축본(우선)
            $this->colsPublic.'product/'.$rel,            // 원본(폴백)
        ]);
    }

    /** shop 배너 등 기타 이미지 복사 */
    private function copyShopImage(string $rel): ?string
    {
        $rel = ltrim($rel, '/');

        return $this->copyFile('shop/'.$rel, [$this->colsPublic.'shop/'.$rel]);
    }

    /** 첫 번째로 존재하는 원본을 public/{destRel} 로 복사. 반환: asset URL 또는 null */
    private function copyFile(string $destRel, array $srcCandidates): ?string
    {
        $src = null;
        foreach ($srcCandidates as $c) {
            if (is_file($c)) {
                $src = $c;
                break;
            }
        }
        if (! $src) {
            return null;
        }
        $dest = public_path($destRel);
        $dir = dirname($dest);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (! is_file($dest)) {
            @copy($src, $dest);
        }

        return asset($destRel);
    }

    /** images JSON 배열 → 갤러리 이미지 복사 후 asset URL 배열 반환 */
    private function importGallery(?string $json): array
    {
        if (! $json) {
            return [];
        }
        $arr = json_decode($json, true);
        if (! is_array($arr)) {
            return [];
        }
        $out = [];
        foreach ($arr as $rel) {
            if (! is_string($rel)) {
                continue;
            }
            $u = $this->copyProductImage($rel);
            if ($u) {
                $out[] = $u;
            }
        }

        return $out;
    }

    /** 상세설명 HTML: 소개문 + 원본 상세(이미지 포함). 내부 이미지(colscare URL)는 로컬로 복사·재작성 */
    private function importDetails(?string $html, string $name): string
    {
        $intro = '<p>'.e($name).'</p><p>콜로플라스트(Coloplast) 정품 의료소모품입니다. 장루·요루·상처치료 전문 제품으로, 사업자(병의원) 승인 회원에게는 전용가가 적용됩니다.</p>';

        // 텍스트도 이미지도 없으면 소개문만
        if (! $html || (trim(strip_tags($html)) === '' && stripos($html, '<img') === false)) {
            return $intro;
        }

        // https://www.colscare.com/storage/app/public/(product|shop)/경로  또는  storage/app/public/...
        $processed = preg_replace_callback(
            '#(?:https?://[^"\'\s>]+?)?/?storage/app/public/(product|shop)/([^"\'\s)>]+)#i',
            function ($m) {
                [$full, $type, $path] = $m;
                $url = $type === 'product' ? $this->copyProductImage($path) : $this->copyShopImage($path);

                return $url ?? $full;
            },
            $html
        );

        return $intro.'<div class="detail-images">'.$processed.'</div>';
    }
}
