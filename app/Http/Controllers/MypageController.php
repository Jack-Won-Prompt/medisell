<?php

namespace App\Http\Controllers;

use App\Models\AgentBuyer;
use App\Models\Order;
use App\Models\UserAddress;
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

    // ===== 배송지 주소록 =====

    public function addresses(Request $request)
    {
        return view('mypage.addresses', [
            'user'      => $request->user(),
            'addresses' => $request->user()->addresses,
        ]);
    }

    public function storeAddress(Request $request)
    {
        $data = $this->validateAddress($request);
        $user = $request->user();

        // 첫 배송지이거나 기본 지정 시 기본배송지로
        $makeDefault = $request->boolean('is_default') || $user->addresses()->count() === 0;
        if ($makeDefault) {
            $user->addresses()->update(['is_default' => false]);
        }
        $user->addresses()->create($data + ['is_default' => $makeDefault]);

        return back()->with('ok', '배송지가 추가되었습니다.');
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

        return back()->with('ok', '배송지가 수정되었습니다.');
    }

    public function deleteAddress(Request $request, UserAddress $address)
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $wasDefault = $address->is_default;
        $address->delete();

        // 기본배송지를 지웠으면 남은 것 중 하나를 기본으로
        if ($wasDefault && ($next = $request->user()->addresses()->first())) {
            $next->update(['is_default' => true]);
        }

        return back()->with('ok', '배송지가 삭제되었습니다.');
    }

    public function setDefaultAddress(Request $request, UserAddress $address)
    {
        abort_unless($address->user_id === $request->user()->id, 403);
        $request->user()->addresses()->update(['is_default' => false]);
        $address->update(['is_default' => true]);

        return back()->with('ok', '기본 배송지로 설정되었습니다.');
    }

    // ===== 구매 대행자 =====

    /** 대행자만 접근 가능 보장 */
    private function ensureAgent(Request $request): void
    {
        abort_unless($request->user()->isAgent(), 403, '구매 대행자 전용 메뉴입니다.');
    }

    /** 담당 구매자 목록 + 캐쉬백 요약 */
    public function agentBuyers(Request $request)
    {
        $this->ensureAgent($request);
        $user = $request->user();

        return view('mypage.agent-buyers', [
            'user'    => $user,
            'buyers'  => $user->agentBuyers()->get(),
            'pending' => $user->pendingCashback(),
            'paid'    => (int) $user->agentCashbacks()->where('status', 'paid')->sum('amount'),
        ]);
    }

    public function storeAgentBuyer(Request $request)
    {
        $this->ensureAgent($request);
        $data = $this->validateBuyer($request);
        $request->user()->agentBuyers()->create($data + ['is_active' => true]);

        return back()->with('ok', '구매자가 등록되었습니다.');
    }

    public function updateAgentBuyer(Request $request, AgentBuyer $buyer)
    {
        $this->ensureAgent($request);
        abort_unless($buyer->agent_id === $request->user()->id, 403);
        $data = $this->validateBuyer($request);
        $buyer->update($data + ['is_active' => $request->boolean('is_active')]);

        return back()->with('ok', '구매자 정보가 수정되었습니다.');
    }

    public function deleteAgentBuyer(Request $request, AgentBuyer $buyer)
    {
        $this->ensureAgent($request);
        abort_unless($buyer->agent_id === $request->user()->id, 403);
        $buyer->delete();

        return back()->with('ok', '구매자가 삭제되었습니다.');
    }

    /** 캐쉬백 적립 내역 */
    public function agentCashbacks(Request $request)
    {
        $this->ensureAgent($request);
        $user = $request->user();

        return view('mypage.agent-cashbacks', [
            'user'      => $user,
            'cashbacks' => $user->agentCashbacks()->with('order')->paginate(20),
            'pending'   => $user->pendingCashback(),
            'paid'      => (int) $user->agentCashbacks()->where('status', 'paid')->sum('amount'),
        ]);
    }

    private function validateBuyer(Request $request): array
    {
        return $request->validate([
            'hospital_name' => ['required', 'string', 'max:100'],
            'buyer_name'    => ['required', 'string', 'max:50'],
            'buyer_phone'   => ['required', 'string', 'max:30'],
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
}
