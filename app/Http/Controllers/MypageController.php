<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MypageController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        return view('mypage.index', [
            'user'         => $user,
            'recentOrders' => $user->orders()->latest()->take(5)->get(),
            'orderCount'   => $user->orders()->count(),
        ]);
    }

    public function orders(Request $request)
    {
        $orders = $request->user()->orders()->withCount('items')->latest()->paginate(10);

        return view('mypage.orders', compact('orders'));
    }

    public function order(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        $order->load('items');

        return view('mypage.order', compact('order'));
    }

    public function cancelOrder(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);
        abort_unless(in_array($order->status, ['pending', 'paid']), 400, '준비중 이후 주문은 취소할 수 없습니다.');

        // 재고 복구 + 적립금 정산 + (토스결제) 환불
        $res = $order->cancel('고객 취소');
        if (! $res['ok']) {
            return back()->with('error', $res['message']);
        }

        return back()->with('ok', '주문이 취소되었습니다.');
    }

    public function points(Request $request)
    {
        $logs = $request->user()->pointLogs()->paginate(15);

        return view('mypage.points', compact('logs'));
    }

    public function coupons(Request $request)
    {
        $available = $request->user()->availableCoupons();
        $used = $request->user()->userCoupons()->with('coupon')->whereNotNull('used_at')
            ->latest('used_at')->get()->filter(fn ($uc) => $uc->coupon)->values();

        return view('mypage.coupons', compact('available', 'used'));
    }

    public function profile(Request $request)
    {
        return view('mypage.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:50'],
            'phone'    => ['nullable', 'string', 'max:30'],
            'postcode' => ['nullable', 'string', 'max:10'],
            'address1' => ['nullable', 'string', 'max:200'],
            'address2' => ['nullable', 'string', 'max:200'],
            'password' => ['nullable', 'confirmed', 'min:8'],
        ]);

        $user->fill([
            'name' => $data['name'], 'phone' => $data['phone'] ?? null,
            'postcode' => $data['postcode'] ?? null,
            'address1' => $data['address1'] ?? null, 'address2' => $data['address2'] ?? null,
        ]);
        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return back()->with('ok', '회원정보가 수정되었습니다.');
    }
}
