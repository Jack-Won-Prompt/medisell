<?php

namespace App\Models;

use App\Models\Concerns\HasImagePaths;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Banner extends Model
{
    use HasImagePaths;

    protected $fillable = [
        'title', 'subtitle', 'image', 'link', 'bg_color', 'position', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** 이미지 — 저장은 상대경로, 출력은 현재 환경 절대 URL */
    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn ($v) => self::toImageUrl($v),
            set: fn ($v) => self::toRelativeImagePath($v),
        );
    }
}
