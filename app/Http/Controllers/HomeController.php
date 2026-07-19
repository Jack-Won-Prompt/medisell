<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Notice;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        // 오늘의 특가: 관리자 설정(deal_mode)에 따라 구성 — random(기본)/discount/best
        $dealBase = Product::active()->where('price', '>', 0);
        $dealProducts = match (config('site.deal_mode', 'random')) {
            'discount' => (clone $dealBase)
                ->whereColumn('member_price', '<', 'price')->where('member_price', '>', 0)
                ->orderByRaw('(price - member_price) / price DESC')->take(12)->get(),
            'best' => (clone $dealBase)->where('is_best', true)->latest('view_count')->take(12)->get(),
            default => (clone $dealBase)->inRandomOrder()->take(12)->get(),   // random
        };

        // 카테고리 탭형 베스트: 대분류별 인기 상품 (상품 있는 카테고리만)
        // 하위 트리를 한 번에 eager-load 하여 descendantIds() 반복 쿼리를 제거
        $categoryTabs = Category::whereNull('parent_id')->where('is_active', true)
            ->orderBy('sort_order')->with('children.children.children')->get()
            ->map(fn ($c) => [
                'category' => $c,
                // 옵션(규격) 변형은 하나로 묶어 서로 다른 제품 10개만
                'products' => Product::active()->whereIn('category_id', $c->descendantIds())
                    ->latest('view_count')->take(80)->get()
                    ->unique(fn ($p) => $p->group_key ?: ("id:".$p->id))
                    ->take(10)->values(),
            ])
            ->filter(fn ($t) => $t['products']->isNotEmpty())->values();

        // BEST ITEM: 실제 판매량(결제완료·미취소) 순 + 옵션 중복 제거
        // 상관 서브쿼리로 판매수량 계산 (GROUP BY 회피 → ONLY_FULL_GROUP_BY 안전)
        $bestProducts = Product::active()
            ->select('products.*')
            ->selectSub(function ($q) {
                $q->from('order_items')
                    ->join('orders', 'orders.id', '=', 'order_items.order_id')
                    ->whereColumn('order_items.product_id', 'products.id')
                    ->whereNotNull('orders.paid_at')
                    ->where('orders.status', '!=', 'cancelled')
                    ->selectRaw('COALESCE(SUM(order_items.quantity), 0)');
            }, 'sold')
            ->orderByDesc('sold')
            ->orderByDesc('view_count')
            ->take(60)->get()
            ->unique(fn ($p) => $p->group_key ?: ("id:".$p->id))
            ->take(10)->values();

        return view('home', [
            'mainBanners' => Banner::where('is_active', true)->where('position', 'main')->orderBy('sort_order')->get(),
            'subBanners'  => Banner::where('is_active', true)->where('position', 'sub')->orderBy('sort_order')->get(),
            'dealProducts'    => $dealProducts,
            'categoryTabs'    => $categoryTabs,
            'bestProducts'    => $bestProducts,
            'featuredProducts' => Product::active()->where('is_featured', true)->latest()->take(8)->get(),
            'newProducts'     => Product::active()->where('is_new', true)->latest()->take(8)->get(),
            'notices'     => Notice::orderByDesc('is_pinned')->latest('published_at')->take(5)->get(),
            'brands'      => Brand::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }
}
