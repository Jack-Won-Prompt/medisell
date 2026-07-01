<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxInvoice extends Model
{
    protected $fillable = [
        'order_id', 'user_id', 'mgt_key', 'invoice_kind',
        'supply_amount', 'tax_amount', 'total_amount',
        'receiver_corp_num', 'receiver_corp_name', 'receiver_ceo', 'receiver_email',
        'status', 'popbill_state', 'nts_confirm_num', 'error_message',
        'issued_at', 'cancelled_at',
    ];

    protected $casts = [
        'issued_at'    => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function kindLabel(): string
    {
        return $this->invoice_kind === 'plain' ? '계산서(면세)' : '세금계산서(과세)';
    }

    public function statusLabel(): string
    {
        return [
            'issued'    => '발행완료',
            'simulated' => '발행(시뮬레이트)',
            'cancelled' => '취소',
            'failed'    => '실패',
        ][$this->status] ?? $this->status;
    }
}
