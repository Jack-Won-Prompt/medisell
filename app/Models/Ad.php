<?php

namespace App\Models;

use App\Models\Concerns\HasImagePaths;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/**
 * 사이드 광고 — 넓은 화면의 좌/우 여백에 노출되는 관리형 제품 광고.
 * 메디셀 카탈로그와 무관한 외부/제휴 광고를 관리자에서 등록.
 */
class Ad extends Model
{
    use HasImagePaths;

    protected $fillable = [
        'title', 'subtitle', 'image', 'bg_color', 'price',
        'badge', 'link', 'position', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'price'     => 'integer',
    ];

    /** 이미지 — 저장은 상대경로, 출력은 현재 환경 절대 URL */
    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn ($v) => self::toImageUrl($v),
            set: fn ($v) => self::toRelativeImagePath($v),
        );
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    /** 특정 레일(left/right) 대상 광고 — position이 both이면 양쪽 노출 */
    public function scopeForRail($q, string $side)
    {
        return $q->whereIn('position', [$side, 'both']);
    }
}
