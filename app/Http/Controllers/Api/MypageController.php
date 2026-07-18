<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use App\Support\ApiSerializer as S;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class MypageController extends Controller
{
    // ===== 배송지 주소록 =====

    public function addresses(Request $request)
    {
        return response()->json([
            'addresses' => $request->user()->addresses->map(fn ($a) => S::address($a)),
        ]);
    }

    public function storeAddress(Request $request)
    {
        $data = $this->validateAddress($request);
        $user = $request->user();

        $makeDefault = $request->boolean('is_default') || $user->addresses()->count() === 0;
        if ($makeDefault) {
            $user->addresses()->update(['is_default' => false]);
        }
        $address = $user->addresses()->create($data + ['is_default' => $makeDefault]);

        return response()->json([
            'message' => '배송지가 추가되었습니다.',
            'address' => S::address($address),
        ], 201);
    }

    public function updateAddress(Request $request, UserAddress $address)
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $data = $this->validateAddress($request);

        if ($request->boolean('is_default')) {
            $request->user()->addresses()->update(['is_default' => false]);
            $data['is_default'] = true;
        }
        $address->update($data);

        return response()->json([
            'message' => '배송지가 수정되었습니다.',
            'address' => S::address($address->fresh()),
        ]);
    }

    public function deleteAddress(Request $request, UserAddress $address)
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault && ($next = $request->user()->addresses()->first())) {
            $next->update(['is_default' => true]);
        }

        return response()->json(['message' => '배송지가 삭제되었습니다.']);
    }

    public function setDefaultAddress(Request $request, UserAddress $address)
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $request->user()->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return response()->json([
            'message' => '기본 배송지로 설정되었습니다.',
            'address' => S::address($address->fresh()),
        ]);
    }

    private function validateAddress(Request $request): array
    {
        return $request->validate([
            'label'          => ['nullable', 'string', 'max:50'],
            'receiver_name'  => ['required', 'string', 'max:50'],
            'receiver_phone' => ['required', 'string', 'max:30'],
            'postcode'       => ['nullable', 'string', 'max:10'],
            'address1'       => ['required', 'string', 'max:200'],
            'address2'       => ['nullable', 'string', 'max:200'],
        ]);
    }

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
