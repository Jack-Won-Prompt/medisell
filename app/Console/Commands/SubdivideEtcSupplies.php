<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * 기타소모품(catch-all)을 제품 종류별 하위 카테고리로 세분화하고 재분류.
 */
class SubdivideEtcSupplies extends Command
{
    protected $signature = 'products:subdivide-etc {--dry : 미리보기}';
    protected $description = '기타소모품을 하위 카테고리(봉합사·탈지면/스왑·붕대·배액/흡인·멸균소독·보호대·진단)로 세분화';

    /** slug => [이름, 아이콘, 정렬] */
    private array $subs = [
        'etc-suture'     => ['봉합사', 'safety', 1],
        'etc-cotton'     => ['탈지면/스왑', 'bandage', 2],
        'etc-bandage'    => ['붕대/반창고/테이프', 'bandage', 3],
        'etc-drainage'   => ['배액/흡인/소변', 'drop', 4],
        'etc-sterile'    => ['멸균/소독용품', 'shield', 5],
        'etc-support'    => ['보호대/압박/정형', 'tools', 6],
        'etc-diagnostic' => ['진단/검사', 'doc', 7],
    ];

    /** 분류 규칙(우선순위 순) slug => 키워드 */
    private array $rules = [
        'etc-suture'  => ['봉합', 'suture', 'silk', 'nylon', 'vicryl', 'prolene', 'ethilon', 'mersilk', 'mersilene', 'monofit', 'surgifit', 'maxon', 'chromic', 'ethibond', 'dermabond', '본드', '나일론', '실크'],
        'etc-drainage' => ['barovac', '배액', '흡인', '흡입', 'u-tractor', 'utractor', '유린백', 'urine', 'stopcock', '스탑콕', '스톱콕', 'drain', '석션', 'suction', '객담', 'hemovac', '헤모박'],
        'etc-sterile' => ['인디게이터', '인디케이터', 'indicator', 'gas bag', 'e.o gas', 'eo gas', 'medizyme', '메디자임', 'attest', '멸균', '소독포', '세척티슈', 'sterile'],
        'etc-support' => ['보호대', '스타킹', 'stocking', '압박', '팔걸이', 'cast', 'splint', '부목', '슬링', 'sling', '복대', '손목', '무릎보호', '관상붕대', '스타키넷', 'stokinet'],
        'etc-diagnostic' => ['혈당', 'strip', '마우스피스', '질경', 'speculum', '체온', 'oximeter', '산소포화', '혈압', 'test', '검사지', 'lancet', '란셋'],
        'etc-cotton'  => ['탈지면', '코튼', 'cotton', '솜', '스왑', 'swab', '거즈', 'gauze', '면봉'],
        'etc-bandage' => ['붕대', 'bandage', '반창고', 'tape', 'transpore', '트랜스포', '스테리스트립', '코반', 'coban', '테가덤', 'tegaderm', 'opsite', '드레싱', 'dressing', '밴드', 'band', '필름', 'film', '탄력'],
    ];

    public function handle(): int
    {
        $dry = (bool) $this->option('dry');
        $parent = Category::where('slug', 'etc-supplies')->orWhere('name', '기타소모품')->first();
        if (! $parent) {
            $this->error('기타소모품 카테고리 없음');
            return 1;
        }

        // 하위 카테고리 생성
        $subId = [];
        foreach ($this->subs as $slug => [$name, $icon, $ord]) {
            $subId[$slug] = Category::firstOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'icon' => $icon, 'sort_order' => $ord, 'parent_id' => $parent->id, 'is_active' => true],
            )->id;
        }

        // 부모 직속(세분화 전) 상품만 재분류 — 이미 하위로 옮긴 건 제외
        $dist = []; $moved = 0;
        foreach (Product::where('category_id', $parent->id)->cursor() as $p) {
            $slug = $this->classify($p->name);
            if (! $slug) { $dist['(기타 유지)'] = ($dist['(기타 유지)'] ?? 0) + 1; continue; }
            $dist[$slug] = ($dist[$slug] ?? 0) + 1;
            if (! $dry) {
                Product::where('id', $p->id)->update(['category_id' => $subId[$slug]]);
            }
            $moved++;
        }

        $this->info(($dry ? '[미리보기] ' : '')."세분화 완료 · 이동 {$moved}건 · 하위 ".count($this->subs).'개');
        foreach ($dist as $slug => $n) {
            $label = $this->subs[$slug][0] ?? $slug;
            $this->line("  {$label}: {$n}");
        }
        return 0;
    }

    private function classify(string $name): ?string
    {
        $n = Str::lower($name);
        foreach ($this->rules as $slug => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($n, Str::lower($kw))) {
                    return $slug;
                }
            }
        }
        return null; // 매칭 없으면 기타소모품에 유지
    }
}
