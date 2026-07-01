<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'product_id', 'user_id', 'author_name', 'rating', 'title', 'body', 'is_hidden',
    ];

    protected $casts = ['is_hidden' => 'boolean'];

    public function scopeVisible($q)
    {
        return $q->where('is_hidden', false);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
