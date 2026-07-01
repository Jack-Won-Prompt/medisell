<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    /** 관심상품 추가/해제 토글 */
    public function toggle(Request $request, Product $product)
    {
        $user = $request->user();
        $existing = Wishlist::where('user_id', $user->id)->where('product_id', $product->id)->first();

        if ($existing) {
            $existing->delete();
            $msg = '관심상품에서 제외했습니다.';
        } else {
            Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id]);
            $msg = '관심상품에 담았습니다.';
        }

        return back()->with('ok', $msg);
    }

    /** 마이페이지 관심상품 목록 */
    public function index(Request $request)
    {
        $items = $request->user()->wishlists()
            ->with('product.brand', 'product.category')
            ->latest()
            ->get()
            ->filter(fn ($w) => $w->product !== null);

        return view('mypage.wishlist', compact('items'));
    }
}
