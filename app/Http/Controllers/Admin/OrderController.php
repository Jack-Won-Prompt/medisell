<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with('user')->latest();
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($q = $request->get('q')) {
            $query->where(fn ($w) => $w->where('order_no', 'like', "%{$q}%")
                ->orWhere('receiver_name', 'like', "%{$q}%")
                ->orWhere('depositor', 'like', "%{$q}%"));
        }
        $orders = $query->paginate(15)->withQueryString();

        return view('admin.orders.index', [
            'orders'   => $orders,
            'statuses' => Order::STATUSES,
            'cur'      => $status,
        ]);
    }

    public function show(Order $order)
    {
        $order->load('items', 'user');

        return view('admin.orders.show', [
            'order'    => $order,
            'statuses' => Order::STATUSES,
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(array_keys(Order::STATUSES))],
        ]);

        if ($data['status'] === 'paid') {
            $order->markPaid();      // 결제일 기록 + 적립금 지급 (중복 방지)
        } elseif ($data['status'] === 'cancelled') {
            // 재고 복구 + 적립금 정산 + (토스결제) 환불
            $res = $order->cancel('관리자 취소');
            if (! $res['ok']) {
                return back()->with('error', $res['message']);
            }
        } else {
            $order->update(['status' => $data['status']]);
        }

        return back()->with('ok', "주문 상태를 '{$order->statusLabel()}'(으)로 변경했습니다.");
    }

    /** 송장(택배사·송장번호) 등록 → 배송중 처리 */
    public function updateShipping(Request $request, Order $order)
    {
        $data = $request->validate([
            'courier'     => ['required', 'string', 'max:50'],
            'tracking_no' => ['required', 'string', 'max:60'],
        ]);

        $order->update([
            'courier'     => $data['courier'],
            'tracking_no' => $data['tracking_no'],
            'shipped_at'  => now(),
            // 취소/배송완료가 아니면 배송중으로 전환
            'status'      => in_array($order->status, ['cancelled', 'done']) ? $order->status : 'shipped',
        ]);

        return back()->with('ok', '송장이 등록되어 배송중 처리되었습니다.');
    }
}
