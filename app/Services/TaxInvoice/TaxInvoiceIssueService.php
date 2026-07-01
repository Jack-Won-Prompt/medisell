<?php

namespace App\Services\TaxInvoice;

use App\Models\Order;
use App\Models\Product;
use App\Models\TaxInvoice;
use App\Services\Popbill\PopbillTaxinvoiceService;
use Illuminate\Support\Str;

/**
 * 결제완료 주문을 전자세금계산서로 발행한다.
 *  - 공급자: config('popbill.supplier')  (메디셀)
 *  - 공급받는자: 주문 회원(병원=사업자)
 *  - 부가세: 상품 tax_type(taxable/exempt) 기준. 가격은 부가세 포함가로 간주.
 *  - simulate=true 면 실제 팝빌 호출 없이 발행이력만 생성.
 */
class TaxInvoiceIssueService
{
    public function __construct(private PopbillTaxinvoiceService $popbill) {}

    /** 주문에 대한 세금계산서 발행 */
    public function issueForOrder(Order $order, ?string $memo = null): TaxInvoice
    {
        $order->loadMissing('items', 'user');
        $user = $order->user;

        if (! $user || $user->member_type !== 'business' || ! $user->biz_no) {
            throw new \RuntimeException('사업자(병원) 회원 주문만 세금계산서를 발행할 수 있습니다. (사업자등록번호 필요)');
        }
        if (! in_array($order->status, ['paid', 'preparing', 'shipped', 'done'])) {
            throw new \RuntimeException('결제완료(입금확인) 이후 주문만 발행할 수 있습니다.');
        }
        if ($order->taxInvoices()->whereIn('status', ['issued', 'simulated'])->exists()) {
            throw new \RuntimeException('이미 세금계산서가 발행된 주문입니다.');
        }

        [$kind, $supply, $tax, $total] = $this->calcAmounts($order);

        $supplier = config('popbill.supplier');
        $corpNum = preg_replace('/\D/', '', (string) $supplier['corp_num']);
        $recvCorpNum = preg_replace('/\D/', '', (string) $user->biz_no);
        $mgtKey = $this->makeMgtKey($order);

        // 스냅샷
        $snapshot = [
            'order_id'           => $order->id,
            'user_id'            => $user->id,
            'mgt_key'            => $mgtKey,
            'invoice_kind'       => $kind,
            'supply_amount'      => $supply,
            'tax_amount'         => $tax,
            'total_amount'       => $total,
            'receiver_corp_num'  => $recvCorpNum,
            'receiver_corp_name' => $user->company_name,
            'receiver_ceo'       => $user->biz_ceo ?: $user->name,
            'receiver_email'     => $user->email,
        ];

        // 시뮬레이트: 실호출 없이 이력만
        if (config('popbill.simulate', true)) {
            return TaxInvoice::create($snapshot + [
                'status'        => 'simulated',
                'popbill_state' => '시뮬레이트',
                'nts_confirm_num' => 'SIM-'.strtoupper(Str::random(10)),
                'issued_at'     => now(),
            ]);
        }

        // 실발행
        try {
            $invoice = $this->buildInvoice($order, $mgtKey, $kind, $supply, $tax, $total, $supplier, $corpNum, $recvCorpNum, $user);
            $result = $this->popbill->registIssue($corpNum, $invoice, $supplier['user_id'] ?: null, false, $memo);

            $info = $this->popbill->getInfo($corpNum, $mgtKey);

            return TaxInvoice::create($snapshot + [
                'status'          => 'issued',
                'popbill_state'   => $info->stateMemo ?? ($result->message ?? '발행'),
                'nts_confirm_num' => $info->ntsconfirmNum ?? null,
                'issued_at'       => now(),
            ]);
        } catch (\Throwable $e) {
            TaxInvoice::create($snapshot + [
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /** 발행 취소 */
    public function cancel(TaxInvoice $ti, ?string $memo = null): void
    {
        if ($ti->status === 'simulated') {
            $ti->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            return;
        }
        $corpNum = preg_replace('/\D/', '', (string) config('popbill.supplier.corp_num'));
        $this->popbill->cancelIssue($corpNum, $ti->mgt_key, $memo, config('popbill.supplier.user_id') ?: null);
        $ti->update(['status' => 'cancelled', 'cancelled_at' => now()]);
    }

    /** 팝빌 문서 팝업 URL */
    public function popupUrl(TaxInvoice $ti): ?string
    {
        if ($ti->status === 'simulated') {
            return null;
        }
        $corpNum = preg_replace('/\D/', '', (string) config('popbill.supplier.corp_num'));

        return $this->popbill->getPopUpUrl($corpNum, $ti->mgt_key, config('popbill.supplier.user_id') ?: null);
    }

    /** 과세/면세 판정 + 공급가액·세액 계산 (가격=부가세포함가 가정) */
    private function calcAmounts(Order $order): array
    {
        $total = (int) $order->total;

        // 주문 상품의 과세 여부 (하나라도 과세면 세금계산서)
        $productIds = $order->items->pluck('product_id')->filter();
        $hasTaxable = Product::whereIn('id', $productIds)->where('tax_type', '!=', 'exempt')->exists()
            || $productIds->isEmpty(); // 상품정보 없으면 과세 기본

        if ($hasTaxable) {
            $supply = (int) round($total / 1.1);
            $tax = $total - $supply;

            return ['tax', $supply, $tax, $total];
        }

        // 전부 면세 → 계산서
        return ['plain', $total, 0, $total];
    }

    private function makeMgtKey(Order $order): string
    {
        $base = preg_replace('/[^A-Za-z0-9]/', '', $order->order_no);
        $seq = TaxInvoice::where('order_id', $order->id)->count() + 1;

        return Str::limit($base, 20, '').'-'.$seq;
    }

    private function buildInvoice(Order $order, string $mgtKey, string $kind, int $supply, int $tax, int $total, array $s, string $corpNum, string $recvCorpNum, $user)
    {
        $inv = $this->popbill->newInvoice();
        $inv->writeDate = now()->format('Ymd');
        $inv->chargeDirection = '정발행';
        $inv->issueType = '정발행';
        $inv->purposeType = '영수';
        $inv->taxType = $kind === 'plain' ? '면세' : '과세';

        // 공급자
        $inv->invoicerCorpNum = $corpNum;
        $inv->invoicerMgtKey = $mgtKey;
        $inv->invoicerCorpName = $s['corp_name'];
        $inv->invoicerCEOName = $s['ceo_name'];
        $inv->invoicerAddr = $s['addr'];
        $inv->invoicerBizType = $s['biz_type'];
        $inv->invoicerBizClass = $s['biz_class'];
        $inv->invoicerTEL = $s['tel'];
        $inv->invoicerEmail = $s['email'];

        // 공급받는자
        $inv->invoiceeType = '사업자';
        $inv->invoiceeCorpNum = $recvCorpNum;
        $inv->invoiceeCorpName = $user->company_name;
        $inv->invoiceeCEOName = $user->biz_ceo ?: $user->name;
        $inv->invoiceeAddr = trim(($user->address1 ?? '').' '.($user->address2 ?? ''));
        $inv->invoiceeEmail1 = $user->email;

        // 합계
        $inv->supplyCostTotal = (string) $supply;
        $inv->taxTotal = (string) $tax;
        $inv->totalAmount = (string) $total;

        // 품목 (집계 1줄)
        $first = $order->items->first();
        $name = $first ? ($first->product_name.($order->items->count() > 1 ? ' 외 '.($order->items->count() - 1).'건' : '')) : '의료소모품';
        $d = $this->popbill->newDetail();
        $d->serialNum = 1;
        $d->purchaseDT = now()->format('Ymd');
        $d->itemName = Str::limit($name, 90, '');
        $d->qty = '1';
        $d->supplyCost = (string) $supply;
        $d->tax = (string) $tax;
        $inv->detailList = [$d];

        return $inv;
    }
}
