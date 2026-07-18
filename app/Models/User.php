<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name', 'email', 'password',
        'member_type', 'phone', 'postcode', 'address1', 'address2',
        'company_name', 'biz_no', 'biz_type', 'biz_status', 'grade',
        'point', 'is_admin',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'is_admin'          => 'boolean',
        ];
    }

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function addresses()
    {
        return $this->hasMany(UserAddress::class)->orderByDesc('is_default')->orderByDesc('id');
    }

    public function defaultAddress()
    {
        return $this->addresses()->where('is_default', true)->first()
            ?? $this->addresses()->first();
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function pointLogs()
    {
        return $this->hasMany(PointLog::class)->latest();
    }

    public function hospitalPrices()
    {
        return $this->hasMany(HospitalPrice::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function userCoupons()
    {
        return $this->hasMany(UserCoupon::class);
    }

    /** 발행받은 미사용 쿠폰(유효한 것) 목록 */
    public function availableCoupons()
    {
        return UserCoupon::with('coupon')
            ->where('user_id', $this->id)
            ->whereNull('used_at')
            ->get()
            ->filter(fn ($uc) => $uc->coupon && $uc->coupon->is_active
                && (! $uc->coupon->ends_at || $uc->coupon->ends_at->isFuture())
                && (! $uc->coupon->starts_at || $uc->coupon->starts_at->isPast()))
            ->values();
    }

    /** 승인된 병원(사업자) 회원 여부 — 병원 전용가 적용 대상 */
    public function isApprovedBusiness(): bool
    {
        return $this->member_type === 'business' && $this->biz_status === 'approved';
    }

    /** 가독성 별칭 */
    public function isHospital(): bool
    {
        return $this->isApprovedBusiness();
    }

    /** 병원 전용 단가 맵 [product_id => price] (요청당 1회 조회) */
    protected ?array $priceMapCache = null;

    public function priceMap(): array
    {
        if ($this->priceMapCache === null) {
            $this->priceMapCache = $this->isApprovedBusiness()
                ? $this->hospitalPrices()->pluck('price', 'product_id')->all()
                : [];
        }

        return $this->priceMapCache;
    }

    /** 적립금 변동 + 로그 기록 */
    public function adjustPoint(int $amount, string $reason, ?int $orderId = null): void
    {
        $this->point = max(0, $this->point + $amount);
        $this->save();

        $this->pointLogs()->create([
            'amount'   => $amount,
            'balance'  => $this->point,
            'reason'   => $reason,
            'order_id' => $orderId,
        ]);
    }
}
