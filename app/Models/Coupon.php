<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code', 'name', 'type', 'value', 'min_order_amount', 'max_discount',
        'starts_at', 'ends_at', 'usage_limit', 'used_count', 'per_user_limit', 'is_active', 'is_public',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at'   => 'datetime',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
    ];

    public function redemptions()
    {
        return $this->hasMany(CouponRedemption::class);
    }

    public function userCoupons()
    {
        return $this->hasMany(UserCoupon::class);
    }

    /** 이 회원이 사용 가능한(발행받고 미사용) 이 쿠폰 발행분 */
    public function issuedUnusedFor(?User $user): ?UserCoupon
    {
        if (! $user) {
            return null;
        }

        return $this->userCoupons()->where('user_id', $user->id)->whereNull('used_at')->first();
    }

    /** 코드로 조회 (대소문자 무시) */
    public static function findByCode(string $code): ?self
    {
        return static::whereRaw('UPPER(code) = ?', [mb_strtoupper(trim($code))])->first();
    }

    /** 주문금액(상품금액)에 대한 할인액 계산 */
    public function discountFor(int $subtotal): int
    {
        if ($this->type === 'percent') {
            $d = (int) floor($subtotal * $this->value / 100);
            if ($this->max_discount) {
                $d = min($d, $this->max_discount);
            }
        } else {
            $d = $this->value;
        }

        return min($d, $subtotal); // 상품금액 초과 불가
    }

    /**
     * 사용 가능 여부 검증. 반환: [ok(bool), message(?string)]
     */
    public function validateFor(?User $user, int $subtotal): array
    {
        if (! $this->is_active) {
            return [false, '사용할 수 없는 쿠폰입니다.'];
        }
        $now = now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return [false, '아직 사용 기간이 아닙니다.'];
        }
        if ($this->ends_at && $now->gt($this->ends_at)) {
            return [false, '사용 기간이 만료된 쿠폰입니다.'];
        }
        if ($subtotal < $this->min_order_amount) {
            return [false, number_format($this->min_order_amount).'원 이상 주문 시 사용 가능합니다.'];
        }
        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return [false, '쿠폰 사용 한도가 모두 소진되었습니다.'];
        }

        // 발행형 쿠폰: 발행받은 미사용 쿠폰이 있어야 사용 가능
        if (! $this->is_public) {
            if (! $user || ! $this->issuedUnusedFor($user)) {
                return [false, '발행받지 않았거나 이미 사용한 쿠폰입니다.'];
            }

            return [true, null];
        }

        // 공개형 쿠폰: 1인당 사용 한도 확인
        if ($user && $this->per_user_limit > 0) {
            $usedByUser = $this->redemptions()->where('user_id', $user->id)->count();
            if ($usedByUser >= $this->per_user_limit) {
                return [false, '이미 이 쿠폰을 사용하셨습니다.'];
            }
        }

        return [true, null];
    }

    public function typeLabel(): string
    {
        return $this->type === 'percent' ? $this->value.'%' : number_format($this->value).'원';
    }
}
