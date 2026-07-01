<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Coupang\CoupangSearchService;
use Illuminate\Http\Request;

class CoupangController extends Controller
{
    public function __construct(private CoupangSearchService $service) {}

    public function index(Request $request)
    {
        $product = null;
        $keyword = trim((string) $request->get('q', ''));
        $refPrice = null;

        if ($request->filled('product_id')) {
            $product = Product::find($request->get('product_id'));
            if ($product) {
                $keyword = $keyword ?: $product->name;
                $refPrice = $product->price;
            }
        }

        $results = $keyword !== '' ? $this->service->search($keyword, $refPrice) : [];

        // 통계
        $prices = array_column($results, 'price');
        $stats = $prices ? [
            'min'   => min($prices),
            'max'   => max($prices),
            'avg'   => (int) round(array_sum($prices) / count($prices)),
            'count' => count($prices),
        ] : null;

        return view('admin.coupang.index', [
            'products' => Product::orderBy('name')->get(['id', 'name', 'code', 'price']),
            'product'  => $product,
            'keyword'  => $keyword,
            'refPrice' => $refPrice,
            'results'  => $results,
            'stats'    => $stats,
            'simulate' => config('coupang.simulate'),
        ]);
    }
}
