<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PortOne\PortOneService;
use App\Services\TossPayments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 모바일 앱 인앱 웹뷰 결제 — 세션 없이 서명 URL(signed)로 진입.
 * 성공/실패 시 /pay/app/result 페이지로 이동하며, 앱은 이 URL을 감지해 웹뷰를 닫는다.
 */
class MobilePaymentController extends Controller
{
    /** 결제 위젯 페이지 (signed URL 로만 접근) */
    public function pay(Request $request, Order $order)
    {
        if ($order->status !== 'pending' || $order->paid_at) {
            return redirect()->route('pay.app.result', ['status' => 'success', 'order_no' => $order->order_no]);
        }

        $order->load('items');
        $first = $order->items->first();
        $orderName = $first
            ? $first->product_name.($order->items->count() > 1 ? ' 외 '.($order->items->count() - 1).'건' : '')
            : '메디셀 주문';

        return view('order.pay_app', [
            'order'       => $order,
            'orderName'   => $orderName,
            'clientKey'   => config('services.toss.client_key'),
            'customerKey' => 'cust_'.substr(sha1($order->user_id.config('app.key')), 0, 24),
            'portone'     => config('portone'),
        ]);
    }

    /** 토스 성공 콜백 (Toss가 paymentKey/orderId/amount 부착) */
    public function tossSuccess(Request $request, TossPayments $toss)
    {
        $data = $request->validate([
            'paymentKey' => ['required', 'string'],
            'orderId'    => ['required', 'string'],
            'amount'     => ['required', 'integer'],
        ]);

        $order = Order::where('order_no', $data['orderId'])->firstOrFail();

        if ((int) $data['amount'] !== (int) $order->total) {
            return $this->result('fail', $order->order_no, '결제 금액이 일치하지 않습니다.');
        }

        $res = $toss->confirm($data['paymentKey'], $data['orderId'], (int) $data['amount']);
        if (! empty($res['error'])) {
            return $this->result('fail', $order->order_no, $res['message'] ?? '결제 승인 실패');
        }

        $order->fill([
            'pay_provider' => 'toss',
            'payment_key'  => $data['paymentKey'],
            'pay_status'   => $res['status'] ?? null,
            'pay_method'   => $res['method'] ?? null,
        ]);

        if (($res['status'] ?? '') === 'DONE') {
            $order->save();
            $order->markPaid();
        } elseif (($res['status'] ?? '') === 'WAITING_FOR_DEPOSIT' && ! empty($res['virtualAccount'])) {
            $va = $res['virtualAccount'];
            $order->fill([
                'status'     => 'pending',
                'va_bank'    => $va['bankCode'] ?? ($va['bank'] ?? null),
                'va_account' => $va['accountNumber'] ?? null,
                'va_holder'  => $va['customerName'] ?? null,
                'va_due_at'  => $va['dueDate'] ?? null,
            ])->save();
        } else {
            $order->save();
        }

        return $this->result('success', $order->order_no);
    }

    public function tossFail(Request $request)
    {
        return $this->result('fail', $request->get('orderId'), $request->get('message', '결제가 취소되었습니다.'));
    }

    /** 포트원 검증 콜백 (webview form POST) */
    public function portoneVerify(Request $request, PortOneService $portone)
    {
        $data = $request->validate([
            'imp_uid'      => ['required', 'string'],
            'merchant_uid' => ['required', 'string'],
        ]);
        $order = Order::where('order_no', $data['merchant_uid'])->firstOrFail();

        $res = $portone->verify($data['imp_uid'], (int) $order->total);
        if (! $res['ok']) {
            return $this->result('fail', $order->order_no, '결제 검증 실패');
        }

        $order->fill([
            'pay_provider' => 'portone',
            'payment_key'  => $data['imp_uid'],
            'pay_status'   => $res['status'],
            'pay_method'   => $res['method'],
        ]);
        if ($res['status'] === 'paid') {
            $order->save();
            $order->markPaid();
        } elseif ($res['status'] === 'ready' && $res['vbank']) {
            $va = $res['vbank'];
            $order->fill([
                'status' => 'pending', 'va_bank' => $va['bank'], 'va_account' => $va['account'],
                'va_holder' => $va['holder'], 'va_due_at' => $va['due'],
            ])->save();
        } else {
            $order->save();
        }

        return $this->result('success', $order->order_no);
    }

    /** 포트원 시뮬레이트 결제 완료 */
    public function portoneSimulate(Request $request, Order $order)
    {
        abort_unless(config('portone.simulate'), 400);
        $order->fill(['pay_provider' => 'portone', 'pay_status' => 'DONE', 'pay_method' => '카드(시뮬레이트)'])->save();
        $order->markPaid();

        return $this->result('success', $order->order_no);
    }

    /** 앱이 감지하는 결과 페이지 */
    public function result(string $status = null, string $orderNo = null, ?string $message = null)
    {
        $status = $status ?? request()->get('status', 'fail');
        $orderNo = $orderNo ?? request()->get('order_no');
        $message = $message ?? request()->get('message');

        return view('order.pay_result', compact('status', 'orderNo', 'message'));
    }
}
