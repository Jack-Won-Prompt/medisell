<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasUniqueSlug;

    protected $fillable = [
        'parent_id', 'name', 'slug', 'tagline', 'icon', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /** 한글 카테고리명은 Str::slug 결과가 비어 cat-12 형태로 생성된다 (기존 데이터 규칙) */
    protected function slugPrefix(): string
    {
        return 'cat';
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    /** 자기 + 하위 카테고리 id 모음 (목록 조회용) */
    public function descendantIds(): array
    {
        $ids = [$this->id];
        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->descendantIds());
        }

        return $ids;
    }

    public function scopeRoots($q)
    {
        return $q->whereNull('parent_id');
    }
}
