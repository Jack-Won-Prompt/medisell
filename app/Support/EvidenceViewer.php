<?php

namespace App\Support;

use App\Models\Order;
use App\Services\CashReceipt\CashReceiptIssueService;
use App\Services\TaxInvoice\TaxInvoiceIssueService;

/** 고객용 증빙 보기 URL — 웹 마이페이지·앱 API 공통. 해당 주문의 발행완료 건만. */
class EvidenceViewer
{
    public static function url(Order $order, string $type, int $id): ?string
    {
        try {
            if ($type === 'tax_invoice') {
                $doc = $order->taxInvoices()->where('id', $id)->where('status', 'issued')->first();

                return $doc ? app(TaxInvoiceIssueService::class)->customerUrl($doc) : null;
            }
            if ($type === 'cash_receipt') {
                $doc = $order->cashReceipts()->where('id', $id)->where('status', 'issued')->first();

                return $doc ? app(CashReceiptIssueService::class)->customerUrl($doc) : null;
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return null;
    }
}
