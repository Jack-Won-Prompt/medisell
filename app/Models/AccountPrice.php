<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 거래처별 제품 전용 단가 (account_id + product_id → price).
 */
class AccountPrice extends Model
{
    protected $fillable = ['account_id', 'product_id', 'price'];

    public function account()
    {
        return $this->belongsTo(Account::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
