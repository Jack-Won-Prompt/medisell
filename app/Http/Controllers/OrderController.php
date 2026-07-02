<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function checkout(Request $request)
    {
        $user = $request->user();
        $items = $user->cartItems()->with('product.brand')->get()
            ->filter(fn ($i) => $i->product !== null);

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', '장바구니가 비어 있습니다.');
        }

        $summary = CartController::summarize($items, $user);
        [$coupon, $couponDiscount, $couponError] = $this->resolveCoupon($user, $summary['subtotal']);

        // 보유(발행받은) 쿠폰 — 이미 적용된 것 제외
        $availableCoupons = $user->availableCoupons()
            ->filter(fn ($uc) => ! $coupon || $uc->coupon_id !== $coupon->id)->values();

        return view('order.checkout', [
            'items' => $items, 'summary' => $summary, 'user' => $user,
            'coupon' => $coupon, 'couponDiscount' => $couponDiscount, 'couponError' => $couponError,
            'availableCoupons' => $availableCoupons,
        ]);
    }

    /** 쿠폰 적용 (세션에 코드 저장) */
    public function applyCoupon(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:50']]);

        $user = $request->user();
        $subtotal = CartController::summarize(
            $user->cartItems()->with('product')->get()->filter(fn ($i) => $i->product !== null),
            $user
        )['subtotal'];

        $coupon = Coupon::findByCode($data['code']);
        if (! $coupon) {
            return back()->with('error', '존재하지 않는 쿠폰 코드입니다.');
        }
        [$ok, $msg] = $coupon->validateFor($user, $subtotal);
        if (! $ok) {
            return back()->with('error', $msg);
        }

        $request->session()->put('coupon_code', $coupon->code);

        return back()->with('ok', '쿠폰이 적용되었습니다. ('.number_format($coupon->discountFor($subtotal)).'원 할인)');
    }

    public function removeCoupon(Request $request)
    {
        $request->session()->forget('coupon_code');

        return back()->with('ok', '쿠폰이 해제되었습니다.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'receiver_name'  => ['required', 'string', 'max:50'],
            'receiver_phone' => ['required', 'string', 'max:30'],
            'postcode'       => ['nullable', 'string', 'max:10'],
            'address1'       => ['required', 'string', 'max:200'],
            'address2'       => ['nullable', 'string', 'max:200'],
            'memo'           => ['nullable', 'string', 'max:300'],
            'payment_method' => ['required', 'in:bank,toss,portone'],
            'depositor'      => ['required_if:payment_method,bank', 'nullable', 'string', 'max:50'],
            'bank'           => ['required_if:payment_method,bank', 'nullable', 'string', 'max:50'],
            'point_used'     => ['nullable', 'integer', 'min:0'],
        ]);

        $isPg = $data['payment_method'] !== 'bank';

        $user = $request->user();
        $items = $user->cartItems()->with('product')->get()
            ->filter(fn ($i) => $i->product !== null);

        if ($items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', '장바구니가 비어 있습니다.');
        }

        $summary = CartController::summarize($items, $user);
        [$coupon, $couponDiscount] = $this->resolveCoupon($user, $summary['subtotal']);

        // 적립금은 (상품금액 - 쿠폰할인) 까지만 사용 가능
        $pointCap = max(0, $summary['subtotal'] - $couponDiscount);
        $pointUsed = min((int) ($data['point_used'] ?? 0), $user->point, $pointCap);
        $total = max(0, $summary['subtotal'] + $summary['shipping'] - $couponDiscount - $pointUsed);

        $order = DB::transaction(function () use ($user, $items, $summary, $data, $pointUsed, $isPg, $coupon, $couponDiscount, $total) {
            $order = Order::create([
                'order_no'       => 'MS'.now()->format('ymd').strtoupper(substr(uniqid(), -5)),
                'user_id'        => $user->id,
                'status'         => 'pending',
                'payment_method' => $data['payment_method'],
                'pay_provider'   => $isPg ? $data['payment_method'] : null,
                'receiver_name'  => $data['receiver_name'],
                'receiver_phone' => $data['receiver_phone'],
                'postcode'       => $data['postcode'] ?? null,
                'address1'       => $data['address1'],
                'address2'       => $data['address2'] ?? null,
                'memo'           => $data['memo'] ?? null,
                'subtotal'       => $summary['subtotal'],
                'shipping_fee'   => $summary['shipping'],
                'discount'       => $couponDiscount,
                'coupon_id'      => $coupon?->id,
                'coupon_code'    => $coupon?->code,
                'point_used'     => $pointUsed,
                'total'          => $total,
                'bank'           => $isPg ? null : ($data['bank'] ?? null),
                'depositor'      => $isPg ? null : ($data['depositor'] ?? null),
            ]);

            foreach ($items as $i) {
                $price = $i->product->priceFor($user);
                $order->items()->create([
                    'product_id'   => $i->product_id,
                    'product_name' => $i->product->name,
                    'unit'         => $i->product->unit,
                    'price'        => $price,
                    'quantity'     => $i->quantity,
                    'subtotal'     => $price * $i->quantity,
                ]);
                $i->product->decrement('stock', min($i->quantity, $i->product->stock));
            }

            if ($pointUsed > 0) {
                $user->adjustPoint(-$pointUsed, "주문 사용 ({$order->order_no})", $order->id);
            }

            // 쿠폰 사용 확정
            if ($coupon) {
                $coupon->increment('used_count');
                CouponRedemption::create([
                    'coupon_id' => $coupon->id, 'user_id' => $user->id,
                    'order_id' => $order->id, 'discount' => $couponDiscount,
                ]);
                // 발행형 쿠폰: 발행분을 사용처리
                if (! $coupon->is_public) {
                    $coupon->userCoupons()->where('user_id', $user->id)->whereNull('used_at')
                        ->limit(1)->update(['used_at' => now(), 'order_id' => $order->id]);
                }
            }

            $user->cartItems()->delete();

            return $order;
        });

        $request->session()->forget('coupon_code');

        if ($isPg) {
            return redirect()->route('order.pay', $order);
        }

        return redirect()->route('order.complete', $order)->with('ok', '주문이 접수되었습니다.');
    }

    /** 세션 쿠폰 해석 → [?Coupon, discount, ?error] */
    private function resolveCoupon($user, int $subtotal): array
    {
        $code = session('coupon_code');
        if (! $code) {
            return [null, 0, null];
        }
        $coupon = Coupon::findByCode($code);
        if (! $coupon) {
            session()->forget('coupon_code');

            return [null, 0, null];
        }
        [$ok, $msg] = $coupon->validateFor($user, $subtotal);
        if (! $ok) {
            return [null, 0, $msg];
        }

        return [$coupon, $coupon->discountFor($subtotal), null];
    }

    public function complete(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $order->load('items');

        return view('order.complete', compact('order'));
    }
}
