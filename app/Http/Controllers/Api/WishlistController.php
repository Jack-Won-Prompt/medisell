<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\ApiSerializer as S;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $items = $request->user()->wishlists()
            ->with('product.brand')->latest()->get()
            ->filter(fn ($w) => $w->product !== null)->values();

        return response()->json([
            'products' => $items->map(fn ($w) => S::productCard($w->product, $request)),
        ]);
    }

    public function toggle(Request $request, Product $product)
    {
        $user = $request->user();
        $existing = $user->wishlists()->where('product_id', $product->id)->first();

        if ($existing) {
            $existing->delete();
            $wished = false;
        } else {
            $user->wishlists()->create(['product_id' => $product->id]);
            $wished = true;
        }

        return response()->json([
            'wished'  => $wished,
            'count'   => $user->wishlists()->count(),
            'message' => $wished ? '관심상품에 추가했습니다.' : '관심상품에서 제거했습니다.',
        ]);
    }
}
