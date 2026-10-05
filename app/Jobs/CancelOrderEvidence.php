<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\CashReceipt\CashReceiptIssueService;
use App\Services\TaxInvoice\TaxInvoiceIssueService;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * 주문 취소 → 발행된 증빙 자동 취소 (응답 후 실행).
 * - 현금영수증: 취소거래 현금영수증 발행
 * - 세금계산서: 국세청 전송 전이면 발행취소, 전송 후면 수정세금계산서(계약의 해제)
 * 실패해도 주문 취소는 그대로 두고, 실패 사유를 증빙 이력에 남겨 관리자가 주문 상세에서 다시 취소한다.
 */
class CancelOrderEvidence
{
    use Dispatchable;

    public function __construct(public int $orderId) {}

    public function handle(TaxInvoiceIssueService $taxInvoices, CashReceiptIssueService $cashReceipts): void
    {
        $order = Order::find($this->orderId);
        if (! $order) {
            return;
        }

        foreach ($order->cashReceipts()->whereIn('status', ['issued', 'simulated'])->get() as $cr) {
            try {
                $cashReceipts->cancel($cr, '주문 취소');
            } catch (\Throwable $e) {
                $cr->update(['error_message' => '자동취소 실패: '.$e->getMessage()]);
                report($e);
            }
        }

        foreach ($order->taxInvoices()->whereIn('status', ['issued', 'simulated'])->get() as $ti) {
            try {
                $taxInvoices->cancel($ti, '주문 취소');
            } catch (\Throwable $e) {
                $ti->update(['error_message' => '자동취소 실패: '.$e->getMessage()]);
                report($e);
            }
        }
    }
}
