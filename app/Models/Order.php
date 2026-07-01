<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'order_no', 'user_id', 'status', 'payment_method',
        'pay_provider', 'payment_key', 'pay_status', 'pay_method',
        'receiver_name', 'receiver_phone', 'postcode', 'address1', 'address2', 'memo',
        'subtotal', 'shipping_fee', 'discount', 'coupon_id', 'coupon_code', 'point_used', 'total',
        'bank', 'depositor', 'paid_at',
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

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function taxInvoices()
    {
        return $this->hasMany(TaxInvoice::class)->latest();
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

        $this->update([
            'status'        => 'cancelled',
            'cancelled_at'  => now(),
            'cancel_reason' => $reason,
        ]);

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
    }
}
