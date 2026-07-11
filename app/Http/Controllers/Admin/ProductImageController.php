<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * 관리자 상품 이미지 자동검색 + 확인 후 다운로드.
 * - 후보: 의료몰(mediversal/drmro/openmedical/aboutmedi/medisale) + 네이버 이미지검색
 * - 관리자가 후보 중 맞는 이미지를 선택하면 서버가 내려받아 로컬 저장 → 썸네일 지정
 */
class ProductImageController extends Controller
{
    private array $dic = [
        'silicone' => '실리콘', 'foley' => '폴리', 'catheter' => '카테터', 'suction' => '석션', 'surgical' => '수술',
        'gown' => '가운', 'glove' => '장갑', 'gloves' => '장갑', 'syringe' => '주사기', 'needle' => '니들', 'gauze' => '거즈',
        'dressing' => '드레싱', 'mask' => '마스크', 'cannula' => '캐뉼라', 'tube' => '튜브', 'tubing' => '튜브',
        'drape' => '드레이프', 'suture' => '봉합사', 'silk' => '실크', 'forceps' => '포셉', 'forcep' => '포셉',
        'holder' => '홀더', 'foam' => '폼', 'oxygen' => '산소', 'nasal' => '비강', 'disposable' => '일회용',
        'extension' => '연장', 'stopcock' => '스탑콕', 'infusion' => '수액', 'filter' => '필터', 'stent' => '스텐트',
        'balloon' => '벌룬', 'sheath' => '시스', 'blade' => '블레이드', 'scalpel' => '메스', 'trocar' => '트로카',
        'sponge' => '스폰지', 'drain' => '배액', 'bag' => '백', 'wire' => '와이어', 'guidewire' => '가이드와이어',
        'tape' => '테이프', 'band' => '밴드', 'nephrostomy' => '신루', 'urine' => '소변', 'set' => '세트',
    ];

    /** [소스명, 검색URL템플릿, 종류(cafe24/godo), 이미지 다운로드 referer] */
    private array $sources = [
        ['mediversal', 'https://mediversal.co.kr/product/search.html?keyword=%s', 'cafe24', 'https://mediversal.co.kr/'],
        ['drmro', 'https://www.drmro.com/goods/goods_search.php?keyword=%s', 'godo', null],
        ['openmedical', 'https://openmedical.co.kr/product/search.html?keyword=%s', 'cafe24', 'https://openmedical.co.kr/'],
        ['aboutmedi', 'https://aboutmedi.com/product/search.html?keyword=%s', 'cafe24', 'https://aboutmedi.com/'],
        ['medisale', 'https://medisale.co.kr/product/search.html?keyword=%s', 'cafe24', 'https://medisale.co.kr/'],
    ];

    /** 후보 이미지 검색 → data-URI 미리보기 목록 반환 */
    public function search(Product $product)
    {
        $queries = $this->queries($product->name);
        $cands = [];
        foreach ($this->sources as [$name, $tmpl, $kind, $ref]) {
            foreach ($queries as $q) {
                if ($q === '') continue;
                $html = $this->getHtml(sprintf($tmpl, urlencode($q)), $ref);
                foreach ($this->parse($html, $kind) as [$alt, $src]) {
                    if (! isset($cands[$src])) {
                        $cands[$src] = ['url' => $src, 'alt' => $alt, 'source' => $name, 'ref' => $ref];
                    }
                }
                if (count($cands) >= 40) break 2;
            }
        }
        // 네이버 이미지검색 (특수품 대비, 관리자 확인용)
        foreach ($this->naver($queries[1] ?: $queries[0]) as $src) {
            if (! isset($cands[$src])) $cands[$src] = ['url' => $src, 'alt' => '네이버', 'source' => 'naver', 'ref' => null];
        }

        $out = [];
        foreach (array_slice(array_values($cands), 0, 24) as $c) {
            $img = $this->download($c['url'], $c['ref']);
            if ($img && strlen($img) > 1500) {
                $out[] = [
                    'url' => $c['url'], 'source' => $c['source'], 'alt' => Str::limit($c['alt'], 40),
                    'thumb' => 'data:image/jpeg;base64,'.base64_encode($img),
                ];
            }
        }
        return response()->json(['count' => count($out), 'candidates' => $out]);
    }

    /** 선택한 후보 이미지를 내려받아 썸네일로 지정 */
    public function fetch(Request $request, Product $product)
    {
        $data = $request->validate(['url' => ['required', 'url', 'max:2000']]);
        $ref = $this->refererFor($data['url']);
        $img = $this->download($data['url'], $ref);
        if (! $img || strlen($img) < 1500) {
            return response()->json(['error' => '이미지를 내려받지 못했습니다.'], 422);
        }
        if (! str_starts_with((string) finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $img), 'image/')) {
            return response()->json(['error' => '이미지 파일이 아닙니다.'], 422);
        }
        $dir = public_path('product/picked');
        if (! is_dir($dir)) @mkdir($dir, 0775, true);
        $file = preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($product->code ?: $product->id)).'-'.time().'.jpg';
        file_put_contents($dir.'/'.$file, $img);
        $url = asset('product/picked/'.$file);
        $product->update(['thumbnail' => $url]);

        // 유사 판단(같은 기본상품, 규격만 다른) 이미지 없는 상품에도 동일 적용
        $propagated = $this->propagate($product, $url);

        return response()->json(['thumbnail' => $product->thumbnail, 'propagated' => $propagated]);
    }

    /** 같은 기본상품(제조사+정규화명) 중 이미지 없는 형제에 동일 썸네일 전파 */
    private function propagate(Product $product, string $url): int
    {
        $key = $this->baseKey($product->name, $product->maker);
        if ($key === '' || count(explode(' ', $key)) < 2) return 0;
        $count = 0;
        Product::whereNull('thumbnail')->where('id', '!=', $product->id)
            ->where('maker', $product->maker)
            ->select('id', 'name', 'maker')->chunkById(500, function ($rows) use ($key, $url, &$count) {
                foreach ($rows as $p) {
                    if ($this->baseKey($p->name, $p->maker) === $key) {
                        Product::where('id', $p->id)->update(['thumbnail' => $url]);
                        $count++;
                    }
                }
            });
        return $count;
    }

    private function baseKey(string $name, ?string $maker): string
    {
        $s = preg_replace('/\[[^\]]*\]|\([^)]*\)/u', ' ', $name);
        $s = mb_strtolower($s, 'UTF-8');
        $s = preg_replace('/\b\d+(\.\d+)?\s*(fr|f|g|cc|ml|mm|cm|inch|way|매|개입|호|인치)\b/u', ' ', $s);
        $s = preg_replace('#\b\d+\s*[/x]\s*\d+\b#u', ' ', $s);
        $s = preg_replace('/\b(xxxl|xxl|xl|[smlx])\b/u', ' ', $s);
        $s = preg_replace('/[0-9]+/u', ' ', $s);
        $s = preg_replace('/[^a-z가-힣]+/u', ' ', $s);
        $stop = ['no', 'size', 'type', 'set', 'kit', 'the', 'for', 'and', 'with', 'plus', 'new'];
        $words = [];
        foreach (explode(' ', $s) as $w) {
            if (mb_strlen($w) >= 2 && ! in_array($w, $stop, true)) $words[$w] = true;
        }
        $words = array_keys($words);
        sort($words);
        $mk = $maker ? preg_replace('/[^a-z가-힣]+/u', '', mb_strtolower(mb_substr($maker, 0, 10))) : '';
        return trim($mk.'|'.implode(' ', $words));
    }

    /** 상품명 → [한글변환 쿼리, 원문 정리 쿼리] */
    private function queries(string $name): array
    {
        $bare = trim(preg_replace('/\s+/', ' ', preg_replace('/\[[^\]]*\]|\([^)]*\)/u', ' ', $name)));
        $words = preg_split('/[^a-z]+/', Str::lower($bare));
        $ko = [];
        foreach ($words as $w) {
            if (isset($this->dic[$w]) && ! in_array($this->dic[$w], $ko, true)) $ko[] = $this->dic[$w];
        }
        // 이름에 이미 있는 한글도 쿼리에 포함
        preg_match_all('/[가-힣]{2,}/u', $name, $m);
        $koq = trim(implode(' ', $ko).' '.implode(' ', array_slice($m[0], 0, 2)));

        return [trim($koq), Str::limit($bare, 40, '')];
    }

    private function getHtml(string $url, ?string $ref): string
    {
        try {
            $req = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])->timeout(12);
            if ($ref) $req = $req->withHeaders(['Referer' => $ref]);
            return $req->get($url)->body();
        } catch (\Throwable $e) {
            return '';
        }
    }

    private function parse(string $html, string $kind): array
    {
        $out = [];
        if ($kind === 'cafe24') {
            preg_match_all('#<img[^>]*src="(//?[^"]*?(?:ecimg\.cafe24img\.com|web)/[^"]*?product/(?:medium|big)/[^"]*?\.(?:jpg|png))"[^>]*?alt="([^"]{3,})"#i', $html, $m, PREG_SET_ORDER);
        } else {
            preg_match_all('#<img[^>]*src="(https?://[^"]*?/data/goods/[^"]*?\.(?:jpg|png))"[^>]*?alt="([^"]{3,})"#i', $html, $m, PREG_SET_ORDER);
        }
        foreach ($m as $x) {
            $src = $x[1];
            if (str_starts_with($src, '//')) $src = 'https:'.$src;
            $src = preg_replace('#/product/medium/#', '/product/big/', $src);
            $out[] = [html_entity_decode($x[2]), $src];
        }
        return $out;
    }

    private function naver(string $q): array
    {
        if ($q === '') return [];
        $html = $this->getHtml('https://search.naver.com/search.naver?where=image&query='.urlencode($q), null);
        preg_match_all('#https://[\w.-]*pstatic\.net/[^"\s\\\\]+#', $html, $m);
        $urls = [];
        foreach ($m[0] as $u) {
            $u = str_replace('&amp;', '&', $u);
            if (str_contains($u, 'src=') || str_contains($u, 'type=') || str_contains($u, 'phinf')) {
                $urls[$u] = true;
                if (count($urls) >= 8) break;
            }
        }
        return array_keys($urls);
    }

    private function refererFor(string $url): ?string
    {
        foreach ($this->sources as [$name, $tmpl, $kind, $ref]) {
            if ($ref && str_contains($url, parse_url($ref, PHP_URL_HOST))) return $ref;
        }
        return null;
    }

    private function download(string $url, ?string $ref): string
    {
        try {
            $req = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])->timeout(12);
            if ($ref) $req = $req->withHeaders(['Referer' => $ref]);
            $res = $req->get($url);
            return $res->successful() ? $res->body() : '';
        } catch (\Throwable $e) {
            return '';
        }
    }
}
