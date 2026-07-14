<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Support\ApiSerializer as S;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /** 전체 카테고리 트리 */
    public function categories(Request $request)
    {
        $roots = Category::whereNull('parent_id')->where('is_active', true)
            ->orderBy('sort_order')->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('sort_order')
                ->with(['children' => fn ($q2) => $q2->where('is_active', true)->orderBy('sort_order')])])
            ->get();

        return response()->json([
            'categories' => $roots->map(fn ($c) => S::categoryTree($c)),
        ]);
    }

    public function index(Request $request)
    {
        return $this->listResponse($request, Product::active(), null);
    }

    public function category(Request $request, string $slug)
    {
        $category = Category::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $base = Product::active()->whereIn('category_id', $category->descendantIds());

        return $this->listResponse($request, $base, $category);
    }

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

        return $this->listResponse($request, $base, null, ['keyword' => $q]);
    }

    private function listResponse(Request $request, $base, ?Category $category, array $extra = [])
    {
        // 필터 UI용 브랜드 목록 (필터 적용 전 스코프)
        $scopeBrandIds = (clone $base)->whereNotNull('brand_id')->distinct()->pluck('brand_id');
        $brands = Brand::whereIn('id', $scopeBrandIds)->orderBy('name')
            ->withCount(['products' => fn ($q) => $q->active()])->get();

        $query = $base->with('brand');

        // 필터
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

        // 정렬
        $query = match ($request->get('sort')) {
            'price_low'  => $query->orderBy('price'),
            'price_high' => $query->orderByDesc('price'),
            'popular'    => $query->orderByDesc('view_count'),
            'name'       => $query->orderBy('name'),
            default      => $query->latest(),
        };

        $page = $query->paginate(20)->withQueryString();

        return response()->json(array_merge([
            'category' => $category ? S::categoryBrief($category) : null,
            'products' => collect($page->items())->map(fn ($p) => S::productCard($p, $request)),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page'    => $page->lastPage(),
                'total'        => $page->total(),
                'per_page'     => $page->perPage(),
                'has_more'     => $page->hasMorePages(),
            ],
            'filters' => [
                'brands' => $brands->map(fn ($b) => [
                    'id' => $b->id, 'name' => $b->name, 'slug' => $b->slug,
                    'count' => $b->products_count,
                ]),
                'sort'      => $request->get('sort', 'new'),
                'sel_brands' => array_map('intval', (array) $request->get('brand', [])),
                'price_min' => $request->get('price_min'),
                'price_max' => $request->get('price_max'),
            ],
        ], $extra));
    }

    public function show(Request $request, string $slug)
    {
        $product = Product::active()
            ->with(['brand', 'category', 'reviews' => fn ($q) => $q->visible()->latest()])
            ->where('slug', $slug)->firstOrFail();
        $product->increment('view_count');

        $related = Product::active()
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->with('brand')->take(6)->get();

        $wished = false;
        if ($user = $request->user()) {
            $wished = $user->wishlists()->where('product_id', $product->id)->exists();
        }

        return response()->json([
            'product'   => S::productDetail($product, $request),
            'related'   => $related->map(fn ($p) => S::productCard($p, $request)),
            'wished'    => $wished,
        ]);
    }

    public function storeReview(Request $request, Product $product)
    {
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title'  => ['required', 'string', 'max:100'],
            'body'   => ['required', 'string', 'max:2000'],
        ]);

        $review = $product->reviews()->create($data + [
            'user_id'     => $request->user()->id,
            'author_name' => $request->user()->name,
        ]);

        return response()->json([
            'message' => '후기가 등록되었습니다.',
            'review'  => S::review($review),
        ], 201);
    }
}
