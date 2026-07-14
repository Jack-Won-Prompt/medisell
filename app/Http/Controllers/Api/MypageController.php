<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiSerializer as S;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MypageController extends Controller
{
    public function summary(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user'          => S::user($user),
            'order_count'   => $user->orders()->count(),
            'point'         => (int) $user->point,
            'wishlist_count' => $user->wishlists()->count(),
            'coupon_count'  => $user->availableCoupons()->count(),
            'recent_orders' => $user->orders()->withCount('items')->latest()->take(5)->get()
                ->map(fn ($o) => S::order($o, $request)),
        ]);
    }

    public function points(Request $request)
    {
        $logs = $request->user()->pointLogs()->paginate(20);

        return response()->json([
            'balance' => (int) $request->user()->point,
            'logs' => collect($logs->items())->map(fn ($l) => [
                'id'      => $l->id,
                'amount'  => (int) $l->amount,
                'balance' => (int) $l->balance,
                'reason'  => $l->reason,
                'date'    => $l->created_at?->format('Y-m-d H:i'),
            ]),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page'    => $logs->lastPage(),
                'has_more'     => $logs->hasMorePages(),
            ],
        ]);
    }

    public function coupons(Request $request)
    {
        $user = $request->user();
        $available = $user->availableCoupons()->map(fn ($uc) => S::coupon($uc->coupon));
        $used = $user->userCoupons()->with('coupon')->whereNotNull('used_at')
            ->latest('used_at')->get()
            ->filter(fn ($uc) => $uc->coupon)
            ->map(fn ($uc) => array_merge(S::coupon($uc->coupon), [
                'used_at' => $uc->used_at?->format('Y-m-d'),
            ]))->values();

        return response()->json(['available' => $available, 'used' => $used]);
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

        return response()->json([
            'message' => '회원정보가 수정되었습니다.',
            'user'    => S::user($user->fresh()),
        ]);
    }
}
