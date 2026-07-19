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
        // 오늘의 특가: 할인율(정가 대비 회원가) 높은 순
        $dealProducts = Product::active()
            ->whereColumn('member_price', '<', 'price')->where('member_price', '>', 0)
            ->orderByRaw('(price - member_price) / price DESC')
            ->take(12)->get();

        // 카테고리 탭형 베스트: 대분류별 인기 상품 (상품 있는 카테고리만)
        // 하위 트리를 한 번에 eager-load 하여 descendantIds() 반복 쿼리를 제거
        $categoryTabs = Category::whereNull('parent_id')->where('is_active', true)
            ->orderBy('sort_order')->with('children.children.children')->get()
            ->map(fn ($c) => [
                'category' => $c,
                'products' => Product::active()->whereIn('category_id', $c->descendantIds())
                    ->latest('view_count')->take(10)->get(),
            ])
            ->filter(fn ($t) => $t['products']->isNotEmpty())->values();

        return view('home', [
            'mainBanners' => Banner::where('is_active', true)->where('position', 'main')->orderBy('sort_order')->get(),
            'subBanners'  => Banner::where('is_active', true)->where('position', 'sub')->orderBy('sort_order')->get(),
            'dealProducts'    => $dealProducts,
            'categoryTabs'    => $categoryTabs,
            'bestProducts'    => Product::active()->where('is_best', true)->latest('view_count')->take(10)->get(),
            'featuredProducts' => Product::active()->where('is_featured', true)->latest()->take(8)->get(),
            'newProducts'     => Product::active()->where('is_new', true)->latest()->take(8)->get(),
            'notices'     => Notice::orderByDesc('is_pinned')->latest('published_at')->take(5)->get(),
            'brands'      => Brand::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }
}
