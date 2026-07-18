<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\CartController as WebCart;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Order;
use App\Support\ApiSerializer as S;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /** 체크아웃 요약 (장바구니 합계 + 쿠폰 미리적용 + 사용가능 쿠폰 + 배송지 기본값) */
    public function checkout(Request $request)
    {
        $user = $request->user();
        $items = $user->cartItems()->with('product.brand')->get()
            ->filter(fn ($i) => $i->product !== null)->values();

        if ($items->isEmpty()) {
            return response()->json(['message' => '장바구니가 비어 있습니다.'], 422);
        }

        $summary = WebCart::summarize($items, $user);

        [$coupon, $couponDiscount, $couponError] = $this->resolveCoupon(
            $request->input('coupon_code'), $user, $summary['subtotal']
        );

        $available = $user->availableCoupons()
            ->filter(fn ($uc) => ! $coupon || $uc->coupon_id !== $coupon->id)
            ->map(fn ($uc) => S::coupon($uc->coupon, $summary['subtotal']))->values();

        return response()->json([
            'items'   => $items->map(fn ($i) => [
                'quantity' => (int) $i->quantity,
                'price'    => $i->product->priceFor($user),
                'product'  => S::productCard($i->product, $request),
            ]),
            'summary' => [
                'subtotal' => (int) $summary['subtotal'],
                'shipping' => (int) $summary['shipping'],
                'count'    => (int) $summary['count'],
            ],
            'coupon'          => $coupon ? S::coupon($coupon, $summary['subtotal']) : null,
            'coupon_discount' => (int) $couponDiscount,
            'coupon_error'    => $couponError,
            'available_coupons' => $available,
            'point'           => (int) $user->point,
            // 저장된 배송지 주소록 + 기본배송지
            'addresses'       => $user->addresses->map(fn ($a) => S::address($a)),
            'default_address' => ($def = $user->defaultAddress()) ? S::address($def) : null,
            'address' => [
                'receiver_name'  => $user->name,
                'receiver_phone' => $user->phone,
                'postcode'       => $user->postcode,
                'address1'       => $user->address1,
                'address2'       => $user->address2,
            ],
            'banks'      => config('site.banks', []),
            'payment_pg' => config('site.payment_pg', 'toss'),
        ]);
    }

    /** 쿠폰 코드 검증 (적용 미리보기) */
    public function previewCoupon(Request $request)
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:50']]);
        $user = $request->user();

        $subtotal = WebCart::summarize(
            $user->cartItems()->with('product')->get()->filter(fn ($i) => $i->product !== null),
            $user
        )['subtotal'];

        $coupon = Coupon::findByCode($data['code']);
        if (! $coupon) {
            return response()->json(['ok' => false, 'message' => '존재하지 않는 쿠폰 코드입니다.'], 422);
        }
        [$ok, $msg] = $coupon->validateFor($user, $subtotal);
        if (! $ok) {
            return response()->json(['ok' => false, 'message' => $msg], 422);
        }

        return response()->json([
            'ok'       => true,
            'coupon'   => S::coupon($coupon, $subtotal),
            'discount' => $coupon->discountFor($subtotal),
            'message'  => number_format($coupon->discountFor($subtotal)).'원 할인이 적용됩니다.',
        ]);
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
            'coupon_code'    => ['nullable', 'string', 'max:50'],
        ]);

        $isPg = $data['payment_method'] !== 'bank';
        $user = $request->user();
        $items = $user->cartItems()->with('product')->get()
            ->filter(fn ($i) => $i->product !== null);

        if ($items->isEmpty()) {
            return response()->json(['message' => '장바구니가 비어 있습니다.'], 422);
        }

        $summary = WebCart::summarize($items, $user);
        [$coupon, $couponDiscount] = $this->resolveCoupon($data['coupon_code'] ?? null, $user, $summary['subtotal']);

        $pointCap  = max(0, $summary['subtotal'] - $couponDiscount);
        $pointUsed = min((int) ($data['point_used'] ?? 0), $user->point, $pointCap);
        $total     = max(0, $summary['subtotal'] + $summary['shipping'] - $couponDiscount - $pointUsed);

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

            if ($coupon) {
                $coupon->increment('used_count');
                CouponRedemption::create([
                    'coupon_id' => $coupon->id, 'user_id' => $user->id,
                    'order_id' => $order->id, 'discount' => $couponDiscount,
                ]);
                if (! $coupon->is_public) {
                    $coupon->userCoupons()->where('user_id', $user->id)->whereNull('used_at')
                        ->limit(1)->update(['used_at' => now(), 'order_id' => $order->id]);
                }
            }

            $user->cartItems()->delete();

            return $order;
        });

        $order->load('items.product');

        return response()->json([
            'message'  => $isPg ? '주문이 생성되었습니다. 결제를 진행해주세요.' : '주문이 접수되었습니다.',
            'order'    => S::order($order, $request, true),
            'needs_payment' => $isPg,
            // 카드결제(웹뷰)용 서명 URL — 세션 없이 결제 위젯 렌더
            'payment_url'   => $isPg
                ? \Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'pay.app', now()->addHours(2), ['order' => $order->id])
                : null,
        ], 201);
    }

    public function index(Request $request)
    {
        $orders = $request->user()->orders()->withCount('items')->latest()->paginate(10);

        return response()->json([
            'orders' => collect($orders->items())->map(fn ($o) => S::order($o, $request)),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page'    => $orders->lastPage(),
                'total'        => $orders->total(),
                'has_more'     => $orders->hasMorePages(),
            ],
        ]);
    }

    public function show(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $order->load('items.product');

        return response()->json(['order' => S::order($order, $request, true)]);
    }

    public function cancel(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        abort_unless(in_array($order->status, ['pending', 'paid']), 400, '준비중 이후 주문은 취소할 수 없습니다.');

        $res = $order->cancel('고객 취소');
        if (! $res['ok']) {
            return response()->json(['message' => $res['message']], 422);
        }

        $order->refresh()->load('items.product');

        return response()->json([
            'message' => '주문이 취소되었습니다.',
            'order'   => S::order($order, $request, true),
        ]);
    }

    /** 쿠폰 코드 해석 → [?Coupon, discount, ?error] */
    private function resolveCoupon(?string $code, $user, int $subtotal): array
    {
        if (! $code) {
            return [null, 0, null];
        }
        $coupon = Coupon::findByCode($code);
        if (! $coupon) {
            return [null, 0, '존재하지 않는 쿠폰 코드입니다.'];
        }
        [$ok, $msg] = $coupon->validateFor($user, $subtotal);
        if (! $ok) {
            return [null, 0, $msg];
        }

        return [$coupon, $coupon->discountFor($subtotal), null];
    }
}
