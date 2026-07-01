<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'category_id', 'brand_id', 'name', 'slug', 'code', 'unit', 'maker',
        'summary', 'description', 'spec', 'price', 'member_price', 'tax_type', 'stock',
        'thumbnail', 'images', 'is_active', 'is_featured', 'is_best', 'is_new',
        'badge', 'view_count', 'sort_order',
    ];

    protected $casts = [
        'images'      => 'array',
        'is_active'   => 'boolean',
        'is_featured' => 'boolean',
        'is_best'     => 'boolean',
        'is_new'      => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    public function hospitalPrices()
    {
        return $this->hasMany(HospitalPrice::class);
    }

    /**
     * 회원에 따른 실제 판매가.
     * 우선순위: 병원 전용가(병원별 계약가) → 기본 병원가(member_price) → 정가
     */
    public function priceFor(?User $user): int
    {
        if ($user && $user->isApprovedBusiness()) {
            $map = $user->priceMap();
            if (isset($map[$this->id])) {
                return (int) $map[$this->id];
            }
            if ($this->member_price) {
                return $this->member_price;
            }
        }

        return $this->price;
    }

    /** 해당 회원에게 병원 전용가(정가보다 낮은 가격)가 적용되는지 */
    public function hasSpecialPriceFor(?User $user): bool
    {
        return $user && $user->isApprovedBusiness() && $this->priceFor($user) < $this->price;
    }

    /** 정가 대비 할인율(%) — 주어진 판매가 기준 */
    public function discountRateFor(int $sell): int
    {
        if ($this->price > 0 && $sell < $this->price) {
            return (int) round(($this->price - $sell) / $this->price * 100);
        }

        return 0;
    }

    /** 기본 병원가 기준 할인율(%) — 비로그인/일반 표시용 */
    public function discountRate(): int
    {
        if ($this->member_price && $this->price > 0 && $this->member_price < $this->price) {
            return (int) round(($this->price - $this->member_price) / $this->price * 100);
        }

        return 0;
    }
}
