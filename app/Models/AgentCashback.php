<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 대행자 캐쉬백 원장 — 주문 1건당 1행.
 * status: pending(적립대기) → paid(정산완료) / cancelled(주문취소로 무효)
 */
class AgentCashback extends Model
{
    protected $fillable = [
        'agent_id', 'order_id', 'amount', 'rate', 'status', 'paid_at',
    ];

    protected $casts = [
        'rate'    => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending'   => '적립대기',
        'paid'      => '정산완료',
        'cancelled' => '취소',
    ];

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
