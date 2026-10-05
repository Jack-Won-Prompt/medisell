<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CashReceipt;
use App\Models\Order;
use App\Services\CashReceipt\CashReceiptIssueService;
use Illuminate\Http\Request;

class CashReceiptController extends Controller
{
    public function __construct(private CashReceiptIssueService $service) {}

    /** 주문에 대한 현금영수증 발행(또는 재발행) */
    public function issue(Request $request, Order $order)
    {
        $data = $request->validate([
            'type'     => ['required', 'in:income,expense'],
            'identity' => ['required', 'string', 'max:30'],
        ]);

        try {
            $cr = $this->service->issueForOrder($order, $data['type'], $data['identity']);

            return back()->with('ok', $cr->status === 'simulated'
                ? '현금영수증이 발행되었습니다. (시뮬레이트 모드 — 실제 팝빌 발행 아님)'
                : '현금영수증이 발행되었습니다.');
        } catch (\Throwable $e) {
            return back()->with('error', '발행 실패: '.$e->getMessage());
        }
    }

    /** 취소 (취소거래 현금영수증 발행) */
    public function cancel(Request $request, CashReceipt $cashReceipt)
    {
        try {
            $this->service->cancel($cashReceipt, $request->input('memo', '주문 취소'));

            return back()->with('ok', '현금영수증이 취소되었습니다.');
        } catch (\Throwable $e) {
            return back()->with('error', '취소 실패: '.$e->getMessage());
        }
    }

    /** 팝빌 원본 보기 */
    public function popup(CashReceipt $cashReceipt)
    {
        try {
            $url = $this->service->popupUrl($cashReceipt);
            abort_unless($url, 404, '시뮬레이트 발행은 원본이 없습니다.');

            return redirect()->away($url);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
