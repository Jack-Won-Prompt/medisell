<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\TossPayments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    /** 토스 결제위젯 페이지 (주문 생성 후 결제 진행) */
    public function pay(Request $request, Order $order)
    {
        abort_unless($order->user_id === $request->user()->id, 403);

        // 이미 결제완료된 주문이면 완료 페이지로
        if ($order->status !== 'pending' || $order->paid_at) {
            return redirect()->route('order.complete', $order);
        }

        $order->load('items');
        $first = $order->items->first();
        $orderName = $first
            ? $first->product_name.($order->items->count() > 1 ? ' 외 '.($order->items->count() - 1).'건' : '')
            : '메디셀 주문';

        return view('order.pay', [
            'order'       => $order,
            'orderName'   => $orderName,
            'clientKey'   => config('services.toss.client_key'),
            'customerKey' => 'cust_'.substr(sha1($order->user_id.config('app.key')), 0, 24),
        ]);
    }

    /** 결제 성공 리다이렉트 → 서버 승인 */
    public function success(Request $request, TossPayments $toss)
    {
        $data = $request->validate([
            'paymentKey' => ['required', 'string'],
            'orderId'    => ['required', 'string'],
            'amount'     => ['required', 'integer'],
        ]);

        $order = Order::where('order_no', $data['orderId'])->firstOrFail();
        abort_unless($order->user_id === $request->user()->id, 403);

        // 금액 위변조 방지
        if ((int) $data['amount'] !== (int) $order->total) {
            return redirect()->route('order.pay', $order)->with('error', '결제 금액이 일치하지 않습니다.');
        }

        $res = $toss->confirm($data['paymentKey'], $data['orderId'], (int) $data['amount']);

        if (! empty($res['error'])) {
            return redirect()->route('order.pay', $order)->with('error', '결제 승인 실패: '.$res['message']);
        }

        $order->fill([
            'pay_provider' => 'toss',
            'payment_key'  => $data['paymentKey'],
            'pay_status'   => $res['status'] ?? null,
            'pay_method'   => $res['method'] ?? null,
        ]);

        if (($res['status'] ?? '') === 'DONE') {
            // 카드/계좌이체 등 즉시 결제완료
            $order->save();
            $order->markPaid();
        } elseif (($res['status'] ?? '') === 'WAITING_FOR_DEPOSIT' && ! empty($res['virtualAccount'])) {
            // 가상계좌 발급 → 입금대기
            $va = $res['virtualAccount'];
            $order->fill([
                'status'     => 'pending',
                'va_bank'    => $va['bankCode'] ?? ($va['bank'] ?? null),
                'va_account' => $va['accountNumber'] ?? null,
                'va_holder'  => $va['customerName'] ?? null,
                'va_due_at'  => $va['dueDate'] ?? null,
            ]);
            $order->save();
        } else {
            $order->save();
        }

        return redirect()->route('order.complete', $order)->with('ok', '결제가 정상 처리되었습니다.');
    }

    /** 결제 실패/취소 리다이렉트 */
    public function fail(Request $request)
    {
        $orderNo = $request->get('orderId');
        $order = $orderNo ? Order::where('order_no', $orderNo)->first() : null;

        return view('order.fail', [
            'order'   => $order,
            'code'    => $request->get('code'),
            'message' => $request->get('message', '결제가 취소되었거나 실패했습니다.'),
        ]);
    }

    /** 토스 웹훅 — 가상계좌 입금확인 등 비동기 알림 */
    public function webhook(Request $request, TossPayments $toss)
    {
        $payload = $request->all();
        $eventType = $payload['eventType'] ?? null;
        $data = $payload['data'] ?? [];

        $orderNo = $data['orderId'] ?? null;
        $status = $data['status'] ?? null;

        if ($orderNo && $status === 'DONE') {
            $order = Order::where('order_no', $orderNo)->first();
            if ($order) {
                // paymentKey로 실제 상태 재확인(신뢰성)
                if (! empty($data['paymentKey'])) {
                    $verify = $toss->get($data['paymentKey']);
                    if (($verify['status'] ?? null) === 'DONE') {
                        $order->update(['pay_status' => 'DONE']);
                        $order->markPaid();
                    }
                } else {
                    $order->update(['pay_status' => 'DONE']);
                    $order->markPaid();
                }
            }
        }

        Log::info('toss.webhook', ['event' => $eventType, 'orderId' => $orderNo, 'status' => $status]);

        return response()->json(['ok' => true]);
    }
}
