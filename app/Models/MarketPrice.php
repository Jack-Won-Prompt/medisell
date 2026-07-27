<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 인터넷 최저가 비교 — 상품·채널당 최저가 1건.
 * 수집은 MarketPriceService 가 담당하고, 이 모델은 저장/표시만 책임진다.
 */
class MarketPrice extends Model
{
    protected $fillable = [
        'product_id', 'channel', 'seller', 'title', 'price', 'delivery', 'url', 'fetched_at',
    ];

    protected $casts = [
        'fetched_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /** 채널 표시명 (쿠팡 / 네이버 / 의료소모품몰) */
    public function channelLabel(): string
    {
        return config('market.channels.'.$this->channel, $this->channel);
    }

    /** TTL(기본 7일) 지난 데이터인지 */
    public function isStale(): bool
    {
        if (! $this->fetched_at) {
            return true;
        }

        return $this->fetched_at->lt(now()->subDays((int) config('market.ttl_days', 7)));
    }
}
