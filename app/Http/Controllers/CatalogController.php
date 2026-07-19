<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /** 전체 상품 목록 */
    public function index(Request $request)
    {
        return $this->renderList($request, Product::active(), '전체 상품', null);
    }

    /** 카테고리별 목록 (대분류면 하위 포함) */
    public function category(Request $request, string $slug)
    {
        $category = Category::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $base = Product::active()->whereIn('category_id', $category->descendantIds());

        return $this->renderList($request, $base, $category->name, $category);
    }

    /** 검색 */
    public function search(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $base = Product::active();
        if ($q !== '') {
            $base->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('summary', 'like', "%{$q}%")
                    ->orWhere('maker', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%");
            });
        }

        return $this->renderList($request, $base, "'{$q}' 검색 결과", null, ['keyword' => $q]);
    }

    /** 공통 목록 렌더 (필터 + 정렬 + 페이징) */
    private function renderList(Request $request, $base, string $title, ?Category $category, array $extra = [])
    {
        // 필터 적용 전 스코프의 브랜드 목록(필터 UI용)
        $scopeBrandIds = (clone $base)->whereNotNull('brand_id')->distinct()->pluck('brand_id');
        $brands = Brand::whereIn('id', $scopeBrandIds)->orderBy('name')
            ->withCount(['products' => fn ($q) => $q->active()])->get();

        $query = $base->with('brand');
        $this->applyFilters($query, $request);

        // 규격/사이즈 변형은 대표 1개만 노출 (group_key당 최소 id)
        $query->whereIn('products.id', function ($sub) {
            $sub->from('products')->selectRaw('MIN(id)')
                ->where('is_active', true)
                ->groupByRaw('COALESCE(group_key, CONCAT("id:", id))');
        });

        $products = $this->sorted($query, $request)->paginate(20)->withQueryString();

        return view('catalog.list', array_merge([
            'title'    => $title,
            'category' => $category,
            'products' => $products,
            'brands'   => $brands,
            'sort'     => $request->get('sort', 'new'),
            'selBrands' => array_map('intval', (array) $request->get('brand', [])),
            'priceMin' => $request->get('price_min'),
            'priceMax' => $request->get('price_max'),
        ], $extra));
    }

    /** 브랜드 / 가격 필터 */
    private function applyFilters($query, Request $request): void
    {
        $brandIds = array_filter((array) $request->get('brand', []));
        if ($brandIds) {
            $query->whereIn('brand_id', $brandIds);
        }
        if (is_numeric($request->get('price_min'))) {
            $query->where('price', '>=', (int) $request->get('price_min'));
        }
        if (is_numeric($request->get('price_max'))) {
            $query->where('price', '<=', (int) $request->get('price_max'));
        }
    }

    /** 상품 상세 */
    public function show(Request $request, string $slug)
    {
        $product = Product::active()->with(['brand', 'category', 'reviews' => fn ($q) => $q->visible()->latest()])
            ->where('slug', $slug)->firstOrFail();
        $product->increment('view_count');

        // 최근 본 상품 (세션, 최대 12개)
        $rv = array_values(array_diff(session('recently_viewed', []), [$product->id]));
        array_unshift($rv, $product->id);
        session(['recently_viewed' => array_slice($rv, 0, 12)]);

        $related = Product::active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->take(4)->get();

        // 규격/사이즈 변형 (같은 group_key)
        $variants = $product->variants();

        return view('catalog.show', compact('product', 'related', 'variants'));
    }

    public function storeReview(Request $request, Product $product)
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title'  => ['required', 'string', 'max:100'],
            'body'   => ['required', 'string', 'max:2000'],
        ]);
        $product->reviews()->create($data + [
            'user_id'     => $request->user()->id,
            'author_name' => $request->user()->name,
        ]);

        return back()->with('ok', '후기가 등록되었습니다.');
    }

    private function sorted($query, Request $request)
    {
        return match ($request->get('sort')) {
            'price_low'  => $query->orderBy('price'),
            'price_high' => $query->orderByDesc('price'),
            'popular'    => $query->orderByDesc('view_count'),
            'name'       => $query->orderBy('name'),
            default      => $query->latest(),
        };
    }
}
