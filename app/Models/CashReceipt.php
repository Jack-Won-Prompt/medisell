<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** 현금영수증 발행 이력 (팝빌) */
class CashReceipt extends Model
{
    protected $fillable = [
        'order_id', 'user_id', 'mgt_key', 'cancel_mgt_key', 'trade_usage', 'identity_num',
        'supply_amount', 'tax_amount', 'total_amount', 'status', 'confirm_num', 'trade_date',
        'popbill_state', 'error_message', 'issued_at', 'cancelled_at',
    ];

    protected $casts = [
        'issued_at'    => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public const STATUSES = [
        'issued'    => '발행완료',
        'simulated' => '시뮬레이트',
        'cancelled' => '취소',
        'failed'    => '실패',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** 화면 표시용 — 휴대폰/사업자번호 가운데를 가린다 */
    public function maskedIdentity(): string
    {
        $n = $this->identity_num;

        return strlen($n) > 6 ? substr($n, 0, 3).str_repeat('*', strlen($n) - 6).substr($n, -3) : $n;
    }
}
