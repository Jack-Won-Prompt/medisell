<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 거래처(병원·기업) — 여러 사용자 계정이 하나의 거래처를 공유하며
 * 거래처 단위로 전용 단가(account_prices) + 등급별 일괄 할인율(discount_rate)을 가진다.
 */
class Account extends Model
{
    protected $fillable = ['name', 'code', 'discount_rate', 'is_active', 'memo'];

    protected $casts = [
        'discount_rate' => 'decimal:2',
        'is_active'     => 'boolean',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function prices()
    {
        return $this->hasMany(AccountPrice::class);
    }

    /** 거래처 전용 단가 맵 [product_id => price] */
    public function priceMap(): array
    {
        return $this->prices()->pluck('price', 'product_id')->all();
    }
}
