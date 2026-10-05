<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_no', 'user_id', 'agent_id', 'status', 'payment_method',
        'pay_provider', 'payment_key', 'pay_status', 'pay_method',
        'receiver_name', 'receiver_phone', 'postcode', 'address1', 'address2', 'memo',
        'buyer_hospital', 'buyer_name', 'buyer_phone', 'cashback_amount',
        'subtotal', 'shipping_fee', 'discount', 'coupon_id', 'coupon_code', 'point_used', 'total',
        'bank', 'depositor', 'paid_at', 'cash_receipt_type', 'cash_receipt_identity',
        'va_bank', 'va_account', 'va_holder', 'va_due_at',
        'courier', 'tracking_no', 'shipped_at', 'cancelled_at', 'cancel_reason',
    ];

    protected $casts = [
        'paid_at'      => 'datetime',
        'va_due_at'    => 'datetime',
        'shipped_at'   => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending'   => '입금대기',
        'paid'      => '입금확인',
        'preparing' => '상품준비중',
        'shipped'   => '배송중',
        'done'      => '배송완료',
        'cancelled' => '취소',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** 대신 결제한 구매 대행자 (있을 때만) */
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function cashback()
    {
        return $this->hasOne(AgentCashback::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function taxInvoices()
    {
        return $this->hasMany(TaxInvoice::class)->latest();
    }

    public function cashReceipts()
    {
        return $this->hasMany(CashReceipt::class)->latest();
    }

    public function smsLogs()
    {
        return $this->hasMany(SmsLog::class)->latest();
    }

    /**
     * 고객 화면(웹·앱)용 증빙 발행 내역 — 실패 건은 숨기고, 발행완료 건만 '보기' 가능.
     * [['type'=>tax_invoice|cash_receipt, 'id', 'label', 'status', 'status_label', 'confirm_num', 'amount', 'issued_at', 'cancelled_at', 'viewable']]
     */
    public function evidenceList(): array
    {
        $rows = [];
        foreach ($this->taxInvoices()->where('status', '!=', 'failed')->get() as $ti) {
            $rows[] = [
                'type' => 'tax_invoice', 'id' => $ti->id, 'label' => '전자세금계산서',
                'status' => $ti->status, 'status_label' => $ti->status === 'simulated' ? '발행완료(테스트)' : $ti->statusLabel(),
                'confirm_num' => $ti->status === 'simulated' ? null : $ti->nts_confirm_num,
                'amount' => (int) $ti->total_amount,
                'issued_at' => $ti->issued_at, 'cancelled_at' => $ti->cancelled_at,
                'viewable' => $ti->status === 'issued',
            ];
        }
        foreach ($this->cashReceipts()->where('status', '!=', 'failed')->get() as $cr) {
            $rows[] = [
                'type' => 'cash_receipt', 'id' => $cr->id, 'label' => "현금영수증({$cr->trade_usage})",
                'status' => $cr->status, 'status_label' => $cr->status === 'simulated' ? '발행완료(테스트)' : $cr->statusLabel(),
                'confirm_num' => $cr->status === 'simulated' ? null : $cr->confirm_num,
                'amount' => (int) $cr->total_amount,
                'issued_at' => $cr->issued_at, 'cancelled_at' => $cr->cancelled_at,
                'viewable' => $cr->status === 'issued',
            ];
        }

        return $rows;
    }

    /** 아직 발행 전인 증빙 안내 문구 (없으면 null) */
    public function evidenceNotice(): ?string
    {
        if ($this->status === 'cancelled' || $this->evidenceList()) {
            return null;
        }
        if ($this->isCardPayment()) {
            return $this->paid_at ? '카드 결제 주문은 카드매출전표가 증빙입니다. 카드사 또는 결제 내역에서 확인하세요.' : null;
        }
        if ($this->user?->isApprovedBusiness() && $this->user->biz_no) {
            return '결제가 확인되면 전자세금계산서가 자동 발행됩니다.';
        }
        if ($this->cash_receipt_type) {
            return '입금이 확인되면 현금영수증('.($this->cash_receipt_type === 'income' ? '소득공제용' : '지출증빙용').')이 자동 발행됩니다.';
        }

        return null;
    }

    /** 안내 문자 받을 번호 — 대행 주문의 구매자 → 주문 회원 → 받는 분 */
    public function notifyPhone(): ?string
    {
        foreach ([$this->buyer_phone, $this->user?->phone, $this->receiver_phone] as $p) {
            $digits = preg_replace('/\D/', '', (string) $p);
            if (preg_match('/^01\d{8,9}$/', $digits)) {
                return $digits;
            }
        }

        return null;
    }

    /** 무통장 주문이 고른 은행의 계좌 정보 (사이트설정 계좌 목록에서 찾음) */
    public function bankAccount(): ?array
    {
        return collect(config('site.banks', []))->firstWhere('bank', $this->bank);
    }

    /**
     * 카드(간편결제 포함) 결제 여부 — 카드매출전표가 증빙이라 세금계산서·현금영수증을 따로 발행하지 않는다.
     * 무통장·가상계좌·계좌이체만 증빙 발행 대상.
     */
    public function isCardPayment(): bool
    {
        if ($this->payment_method === 'bank') {
            return false;
        }

        return ! in_array($this->pay_method, ['가상계좌', '계좌이체', 'VIRTUAL_ACCOUNT', 'TRANSFER', 'vbank', 'trans'], true);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * 주문 취소 — 재고 복구 + 적립금(사용분 반환/적립분 회수) + 토스 결제 시 환불.
     * 반환: ['ok'=>bool, 'message'=>?string]
     */
    public function cancel(string $reason = '주문취소'): array
    {
        if ($this->status === 'cancelled') {
            return ['ok' => true];
        }

        // 토스 결제완료 건이면 먼저 환불 (실패 시 중단)
        if ($this->pay_provider === 'toss' && $this->payment_key && $this->paid_at) {
            $res = app(\App\Services\TossPayments::class)->cancel($this->payment_key, $reason);
            if (! empty($res['error'])) {
                return ['ok' => false, 'message' => $res['message'] ?? '결제 취소에 실패했습니다.'];
            }
        }

        // 재고 복구
        $this->loadMissing('items');
        foreach ($this->items as $it) {
            if ($it->product_id) {
                Product::where('id', $it->product_id)->increment('stock', $it->quantity);
            }
        }

        // 사용 적립금 반환
        if ($this->point_used > 0 && $this->user) {
            $this->user->adjustPoint($this->point_used, "주문취소 적립금 반환 ({$this->order_no})", $this->id);
        }
        // 결제완료였다면 구매 적립금 회수
        if ($this->paid_at && $this->user) {
            $earned = (int) floor($this->total * config('site.point_rate', 0) / 100);
            if ($earned > 0) {
                $this->user->adjustPoint(-$earned, "주문취소 적립금 회수 ({$this->order_no})", $this->id);
            }
        }

        // 쿠폰 사용 롤백 (사용횟수 차감 + 사용기록 삭제 → 재사용 가능)
        if ($this->coupon_id) {
            Coupon::where('id', $this->coupon_id)->where('used_count', '>', 0)->decrement('used_count');
            CouponRedemption::where('order_id', $this->id)->delete();
            // 발행형 쿠폰 발행분 사용해제 → 다시 사용 가능
            UserCoupon::where('order_id', $this->id)->update(['used_at' => null, 'order_id' => null]);
        }

        // 대행자 캐쉬백 무효화 (아직 정산 전인 건만)
        AgentCashback::where('order_id', $this->id)->where('status', 'pending')
            ->update(['status' => 'cancelled']);

        $this->update([
            'status'        => 'cancelled',
            'cancelled_at'  => now(),
            'cancel_reason' => $reason,
        ]);

        // 발행된 세금계산서·현금영수증 자동 취소 (응답 후, 실패는 증빙 이력에 남김)
        \App\Jobs\CancelOrderEvidence::dispatchAfterResponse($this->id);

        return ['ok' => true];
    }

    /** 결제완료(입금확인) 처리 — 결제일 기록 + 구매 적립금 지급 (1회만) */
    public function markPaid(): void
    {
        if ($this->status === 'paid' || $this->paid_at) {
            return;
        }
        $this->status = 'paid';
        $this->paid_at = now();
        $this->save();

        if ($this->user) {
            $point = (int) floor($this->total * config('site.point_rate', 0) / 100);
            if ($point > 0) {
                $this->user->adjustPoint($point, "구매 적립 ({$this->order_no})", $this->id);
            }
        }

        $this->accrueAgentCashback();

        // 결제 확인된 모든 주문 → 주문서 PDF 메일 (응답을 보낸 뒤 발송, 실패해도 결제 처리와 무관)
        \App\Jobs\SendOrderPdfMail::dispatchAfterResponse($this->id);
        // 세금계산서(승인 병원 회원) 또는 신청한 현금영수증 자동 발행 + 결제 완료 문자
        \App\Jobs\IssueOrderEvidence::dispatchAfterResponse($this->id);
        \App\Jobs\SendSmsNotice::dispatchAfterResponse('paid', $this->id);
        \App\Jobs\NotifyAdmin::dispatchAfterResponse('paid', $this->id);   // 관리자 메일+문자
    }

    /**
     * 대행자 캐쉬백 적립 — 대행 주문이고 대행자 비율이 있을 때 원장에 1건 기록(중복 방지).
     * 금액 = 주문 총액 × 대행자 캐쉬백 비율(%).
     */
    protected function accrueAgentCashback(): void
    {
        if (! $this->agent_id) {
            return;
        }
        $agent = $this->agent()->first();
        $rate = $agent ? (float) $agent->cashback_rate : 0;
        if ($rate <= 0) {
            return;
        }
        $amount = (int) floor($this->total * $rate / 100);
        if ($amount <= 0) {
            return;
        }

        $cb = AgentCashback::firstOrCreate(
            ['order_id' => $this->id],
            ['agent_id' => $this->agent_id, 'amount' => $amount, 'rate' => $rate, 'status' => 'pending'],
        );
        if ($cb->wasRecentlyCreated) {
            $this->update(['cashback_amount' => $amount]);
        }
    }
}
