<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\CartController as WebCart;
use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Support\ApiSerializer as S;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(Request $request)
    {
        return response()->json($this->payload($request));
    }

    public function add(Request $request, Product $product)
    {
        $qty = max(1, (int) $request->input('quantity', 1));
        $user = $request->user();

        $item = CartItem::firstOrNew(['user_id' => $user->id, 'product_id' => $product->id]);
        $item->quantity = ($item->exists ? $item->quantity : 0) + $qty;
        $item->save();

        return response()->json(array_merge(
            ['message' => '장바구니에 담았습니다.'],
            $this->payload($request)
        ));
    }

    public function update(Request $request, CartItem $item)
    {
        abort_unless($item->user_id === $request->user()->id, 403);
        $item->update(['quantity' => max(1, (int) $request->input('quantity', 1))]);

        return response()->json(array_merge(
            ['message' => '수량이 변경되었습니다.'],
            $this->payload($request)
        ));
    }

    public function remove(Request $request, CartItem $item)
    {
        abort_unless($item->user_id === $request->user()->id, 403);
        $item->delete();

        return response()->json(array_merge(
            ['message' => '상품을 삭제했습니다.'],
            $this->payload($request)
        ));
    }

    private function payload(Request $request): array
    {
        $user = $request->user();
        $items = $user->cartItems()->with('product.brand')->get()
            ->filter(fn ($i) => $i->product !== null)->values();

        $summary = WebCart::summarize($items, $user);

        return [
            'items' => $items->map(function ($i) use ($request, $user) {
                $price = $i->product->priceFor($user);

                return [
                    'id'         => $i->id,
                    'quantity'   => (int) $i->quantity,
                    'price'      => $price,
                    'line_total' => $price * $i->quantity,
                    'product'    => S::productCard($i->product, $request),
                ];
            })->all(),
            'summary' => [
                'subtotal' => (int) $summary['subtotal'],
                'shipping' => (int) $summary['shipping'],
                'total'    => (int) $summary['total'],
                'count'    => (int) $summary['count'],
                'free_ship_over' => (int) config('site.free_ship_over'),
            ],
        ];
    }
}
