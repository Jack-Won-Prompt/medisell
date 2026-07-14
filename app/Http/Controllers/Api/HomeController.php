<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Notice;
use App\Models\Product;
use App\Support\ApiSerializer as S;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $card = fn ($p) => S::productCard($p, $request);

        $deals = Product::active()
            ->whereColumn('member_price', '<', 'price')->where('member_price', '>', 0)
            ->orderByRaw('(price - member_price) / price DESC')
            ->with('brand')->take(12)->get();

        $categoryTabs = Category::whereNull('parent_id')->where('is_active', true)
            ->orderBy('sort_order')->with('children.children.children')->get()
            ->map(fn ($c) => [
                'category' => S::categoryBrief($c),
                'products' => Product::active()->whereIn('category_id', $c->descendantIds())
                    ->with('brand')->latest('view_count')->take(8)->get()->map($card)->all(),
            ])
            ->filter(fn ($t) => count($t['products']) > 0)->values();

        return response()->json([
            'banners' => [
                'main' => Banner::where('is_active', true)->where('position', 'main')
                    ->orderBy('sort_order')->get()->map(fn ($b) => S::banner($b, $request)),
                'sub'  => Banner::where('is_active', true)->where('position', 'sub')
                    ->orderBy('sort_order')->get()->map(fn ($b) => S::banner($b, $request)),
            ],
            'deals'    => $deals->map($card),
            'category_tabs' => $categoryTabs,
            'best'     => Product::active()->where('is_best', true)->with('brand')
                ->latest('view_count')->take(10)->get()->map($card),
            'featured' => Product::active()->where('is_featured', true)->with('brand')
                ->latest()->take(8)->get()->map($card),
            'new'      => Product::active()->where('is_new', true)->with('brand')
                ->latest()->take(8)->get()->map($card),
            'notices'  => Notice::orderByDesc('is_pinned')->latest('published_at')->take(5)
                ->get()->map(fn ($n) => S::noticeBrief($n)),
            'brands'   => Brand::where('is_active', true)->orderBy('sort_order')->get()
                ->map(fn ($b) => S::brand($b, $request)),
        ]);
    }
}
