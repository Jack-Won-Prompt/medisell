<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankDeposit extends Model
{
    protected $fillable = [
        'tid', 'bank_code', 'account_num', 'trade_date', 'trade_time',
        'amount', 'balance', 'depositor', 'matched_order_id', 'confirmed_at',
    ];

    protected $casts = [
        'trade_date'   => 'date',
        'confirmed_at' => 'datetime',
    ];

    public function matchedOrder()
    {
        return $this->belongsTo(Order::class, 'matched_order_id');
    }

    public function isMatched(): bool
    {
        return $this->matched_order_id !== null;
    }

    /** 입금자명 정규화 (공백/특수문자 제거) */
    public static function normalize(?string $name): string
    {
        return preg_replace('/\s+/', '', trim(mb_strtolower((string) $name)));
    }
}
