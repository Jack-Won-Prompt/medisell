<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasUniqueSlug;

    protected $fillable = [
        'name', 'slug', 'logo', 'description', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** 한글 브랜드명은 brand-7 형태로 생성된다 (기존 데이터 규칙) */
    protected function slugPrefix(): string
    {
        return 'brand';
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
