<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * 블루팜 품목(database/data/bluepharm_items.json 의 코드)을 품목 종류별 하위 카테고리로 다시 분류.
 * - 대분류 6개(slug)는 홈 탭에 쓰이므로 그대로 두고, 그 아래 하위 카테고리를 만든다.
 * - 기타소모품 아래 있던 봉합사·탈지면·붕대·멸균은 성격에 맞는 대분류 아래로 옮긴다.
 * - 콜로플라스트 카테고리는 건드리지 않는다.
 */
class CategorizeBluepharm extends Command
{
    protected $signature = 'bluepharm:categorize {--dry : 미리보기(변경 안 함)} {--map=database/data/bluepharm_items.json : 블루팜 품목 파일}';
    protected $description = '블루팜 품목을 종류별 하위 카테고리로 재분류';

    /** slug => [이름, 부모 slug(null=대분류 자체), 아이콘, 정렬] */
    private array $tree = [
        'inj-syringe'      => ['주사기', 'inj-catheter-tube', 'syringe', 1],
        'inj-needle'       => ['주사침/니들', 'inj-catheter-tube', 'syringe', 2],
        'inj-iv-catheter'  => ['IV카테터', 'inj-catheter-tube', 'catheter', 3],
        'inj-tube'         => ['카테터/튜브', 'inj-catheter-tube', 'catheter', 4],
        'inj-connector'    => ['연결관/부속(3-way·헤파린캡)', 'inj-catheter-tube', 'drop', 5],
        'etc-cotton'       => ['거즈/탈지면/스왑', 'dressing', 'bandage', 1],
        'etc-bandage'      => ['붕대/반창고/테이프', 'dressing', 'bandage', 2],
        'dr-wound'         => ['폼/필름 드레싱', 'dressing', 'bandage', 3],
        'etc-sterile'      => ['멸균/소독용품', 'dressing', 'shield', 4],
        'sg-glove'         => ['장갑', 'surgical', 'safety', 1],
        'sg-wear'          => ['마스크/가운', 'surgical', 'safety', 2],
        'etc-suture'       => ['봉합/스테이플러/메스', 'surgical', 'safety', 3],
        'sg-airway'        => ['에어웨이/호흡', 'surgical', 'safety', 4],
        'etc-drainage'     => ['배액/흡인/소변', 'etc-supplies', 'drop', 1],
        'etc-support'      => ['보호대/압박/정형', 'etc-supplies', 'tools', 2],
        'etc-diagnostic'   => ['진단/검사', 'etc-supplies', 'doc', 3],
    ];

    /** 분류 규칙(위에서부터 먼저 맞는 것) slug => 키워드. 대분류 slug 는 하위 없이 대분류 직속 */
    private array $rules = [
        'sg-glove'        => ['glove', '글러브', '장갑'],
        'sg-wear'         => ['mask', '마스크', 'gown', '가운'],
        'etc-suture'      => ['봉합', 'suture', 'silk', 'nylon', 'vicryl', 'pds', 'prolene', 'maxon', 'ethilon', 'stapler', '스테이플러', '메스', 'blade', 'bond', '본드', 'surgifit', '써지피트'],
        'sg-airway'       => ['airway', 'air way', '에어웨이', 'endo tracheal', 'nasal cannula', '산소', 'i-gel'],
        'medicine'        => ['수액', 'iv set', 'infusion', '연결줄'],
        'inj-connector'   => ['3-way', '3way', '쓰리웨이', 'stopcock', '헤파린캡', 'heparin', 'injection port', 'extension'],
        'inj-iv-catheter' => ['angiocath', 'i.v카테타', 'i.v catheter', '정맥카테타', 'iv catheter'],
        'inj-needle'      => ['needle', '니들', '주사침', '나비침', 'spinal', 'cannula', '캐뉼라'],
        'inj-syringe'     => ['syringe', '주사기', 'posiflush', 'profi tube'],
        'etc-drainage'    => ['barovac', '배액', '유린백', 'urine', '소변기', 'hemovac', '석션고무관', 'panrose', '드레인'],
        'inj-tube'        => ['catheter', '카테타', '카테터', 'levin', '레빈', 'nelaton', '넬라톤', 'foley', 'feeding', 'tube', '튜브'],
        'dr-wound'        => ['medifoam', 'foam', '폼', 'tegaderm', '테가덤', '네오드레싱', 'comfeel', '콤필', 'opsite', 'hydro', '에이덤플러스'],
        'etc-sterile'     => ['인디게이터', '인디케이터', 'indicator', 'e.o gas', 'gas bag', 'medizyme', '메디자임', '에탄올', 'ethanol', '알콜티슈', '와입스', 'hycro', '하이크로', '소독포', '소독테이프', '사니사라'],
        'etc-support'     => ['cast', 'splint', '스프린터', '팔걸이', '복대', '쇄골', '칼라', '스타키넷', 'stokinet', '보호대', '압박', '스타킹', '밸포'],
        'etc-cotton'      => ['거즈', 'gauze', '탈지면', 'cotton', '코튼', '솜', '면봉', 'swab', '스왑', '슬라이스볼', '패드'],
        'etc-bandage'     => ['붕대', 'bandage', '반창고', 'tape', '테이프', 'transpore', '트랜스포', '스테리스트립', '밴드', 'band', '밴드랩', '슈퍼포아', '슈퍼픽스', 'plaster', '방수롤', 'hypafix'],
        'etc-diagnostic'  => ['혈당', 'strip', '스트립', 'lancet', '란셋', '체온', 'electrode', '일렉트로', '초음파', '소노젤리', 'probe', '프루브', '펜라이트', '질경', 'speculum', '마우스피스', 'oximeter', '혈압'],
        'instrument'      => ['설압자', 'tongue', '용기', '트레이', 'tray', 'bowl', '링겔대'],
        'surgical'        => ['보비', 'bovie', 'plate', '탑매트', '깔개', '방수지', 'drape', 'snare', 'razor', '면도기'],
    ];

    public function handle(): int
    {
        $path = base_path($this->option('map'));
        if (! is_file($path)) {
            $this->error("맵 파일 없음: {$path}");
            return 1;
        }
        $codes = array_map('strval', array_keys(json_decode(file_get_contents($path), true) ?: []));
        $dry = (bool) $this->option('dry');

        $roots = Category::whereNull('parent_id')->pluck('id', 'slug');
        foreach (['inj-catheter-tube', 'dressing', 'surgical', 'instrument', 'etc-supplies', 'medicine'] as $s) {
            if (! isset($roots[$s])) {
                $this->error("대분류 없음: {$s}");
                return 1;
            }
        }

        // 하위 카테고리 준비 (기존 slug 는 이름·부모·정렬만 맞춘다)
        $catId = $roots->all();
        foreach ($this->tree as $slug => [$name, $parent, $icon, $ord]) {
            $c = Category::where('slug', $slug)->first();
            $want = ['name' => $name, 'parent_id' => $roots[$parent], 'sort_order' => $ord, 'is_active' => true];
            if (! $c) {
                $c = new Category(['slug' => $slug, 'icon' => $icon] + $want);
                $dry || $c->save();
                $this->line("  + {$name} (".$this->rootName($parent).' 아래)');
            } elseif ($c->name !== $name || (int) $c->parent_id !== (int) $roots[$parent]) {
                $this->line("  ~ {$c->name} → {$name} (".$this->rootName($parent).' 아래)');
                $dry || $c->update($want);
            }
            $catId[$slug] = $c->id;
        }

        $names = Category::pluck('name', 'id');
        $dist = []; $moved = 0; $samples = [];
        foreach (Product::whereIn('code', $codes)->cursor() as $p) {
            $slug = $this->classify($p->name.' '.$p->spec);
            $dist[$slug] = ($dist[$slug] ?? 0) + 1;
            $to = $catId[$slug] ?? null;
            if ($to && (int) $p->category_id !== (int) $to) {
                if (count($samples) < 15) {
                    $samples[] = ($names[$p->category_id] ?? '?').' → '.$this->label($slug).' · '.mb_substr($p->name, 0, 30);
                }
                $dry || Product::where('id', $p->id)->update(['category_id' => $to]);
                $moved++;
            }
        }

        $this->info(($dry ? '[미리보기] ' : '')."재분류 · 이동 {$moved}건");
        foreach ($samples as $s) {
            $this->line("  {$s}");
        }
        $this->line('분포:');
        arsort($dist);
        foreach ($dist as $slug => $n) {
            $this->line('  '.$this->label($slug).": {$n}");
        }

        return 0;
    }

    private function classify(string $text): string
    {
        $n = Str::lower($text);
        foreach ($this->rules as $slug => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($n, Str::lower($kw))) {
                    return $slug;
                }
            }
        }

        return 'etc-supplies';
    }

    private function label(string $slug): string
    {
        if (isset($this->tree[$slug])) {
            return $this->rootName($this->tree[$slug][1]).' > '.$this->tree[$slug][0];
        }

        return $this->rootName($slug);
    }

    private function rootName(string $slug): string
    {
        return ['inj-catheter-tube' => '주사/카테터/튜브', 'dressing' => '드레싱/소독', 'surgical' => '수술용품',
            'instrument' => '기구/용기류', 'etc-supplies' => '기타소모품', 'medicine' => '수액/수혈용품'][$slug] ?? $slug;
    }
}
