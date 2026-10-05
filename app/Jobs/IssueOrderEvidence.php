<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\CashReceipt\CashReceiptIssueService;
use App\Services\TaxInvoice\TaxInvoiceIssueService;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * 결제 확인(markPaid) 후 증빙 자동 발행 — 응답을 보낸 뒤 실행, 실패해도 결제와 무관(이력에 'failed' 로 남고 관리자가 재발행).
 * - 승인 병원 회원(사업자번호 있음) 주문 → 세금계산서
 * - 그 외 무통장 주문에서 현금영수증을 신청했으면 → 현금영수증
 * - 카드 결제는 둘 다 발행하지 않는다 (카드매출전표가 증빙)
 */
class IssueOrderEvidence
{
    use Dispatchable;

    public function __construct(public int $orderId) {}

    public function handle(TaxInvoiceIssueService $taxInvoices, CashReceiptIssueService $cashReceipts): void
    {
        $order = Order::with('user')->find($this->orderId);
        if (! $order || $order->isCardPayment()) {
            return;
        }
        $user = $order->user;

        try {
            if ($user && $user->isApprovedBusiness() && $user->biz_no) {
                if (config('popbill.auto_taxinvoice', true)) {
                    $taxInvoices->issueForOrder($order, '메디셀 주문 '.$order->order_no);
                }
            } elseif ($order->payment_method === 'bank' && $order->cash_receipt_type && $order->cash_receipt_identity) {
                $cashReceipts->issueForOrder($order, $order->cash_receipt_type, $order->cash_receipt_identity);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
