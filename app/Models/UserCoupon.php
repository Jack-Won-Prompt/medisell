<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserCoupon extends Model
{
    protected $fillable = ['coupon_id', 'user_id', 'used_at', 'order_id'];

    protected $casts = ['used_at' => 'datetime'];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopeUnused($q)
    {
        return $q->whereNull('used_at');
    }
}
