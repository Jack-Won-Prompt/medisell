<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $items = $user->cartItems()->with('product.brand')->get()
            ->filter(fn ($i) => $i->product !== null);

        $summary = $this->summarize($items, $user);

        return view('cart.index', compact('items', 'summary'));
    }

    public function add(Request $request, Product $product)
    {
        $qty = max(1, (int) $request->input('quantity', 1));
        $user = $request->user();

        $item = CartItem::firstOrNew(['user_id' => $user->id, 'product_id' => $product->id]);
        $item->quantity = ($item->exists ? $item->quantity : 0) + $qty;
        $item->save();

        if ($request->boolean('buy_now')) {
            return redirect()->route('order.checkout');
        }

        return back()->with('ok', '장바구니에 담았습니다.');
    }

    public function update(Request $request, CartItem $item)
    {
        abort_unless($item->user_id === $request->user()->id, 403);
        $item->update(['quantity' => max(1, (int) $request->input('quantity', 1))]);

        return back()->with('ok', '수량이 변경되었습니다.');
    }

    public function remove(Request $request, CartItem $item)
    {
        abort_unless($item->user_id === $request->user()->id, 403);
        $item->delete();

        return back()->with('ok', '상품을 삭제했습니다.');
    }

    /** 장바구니 합계 계산 (회원유형별 단가 적용) */
    public static function summarize($items, $user): array
    {
        $subtotal = 0;
        foreach ($items as $i) {
            $subtotal += $i->product->priceFor($user) * $i->quantity;
        }
        $freeOver = config('site.free_ship_over');
        $shipping = ($subtotal > 0 && $subtotal < $freeOver) ? config('site.shipping_fee') : 0;

        return [
            'subtotal' => $subtotal,
            'shipping' => $shipping,
            'total'    => $subtotal + $shipping,
            'count'    => $items->count(),
        ];
    }
}
