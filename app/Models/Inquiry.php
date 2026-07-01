<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    protected $fillable = [
        'user_id', 'type', 'name', 'phone', 'email', 'subject', 'body',
        'status', 'answer', 'answered_at', 'is_secret',
    ];

    protected $casts = [
        'answered_at' => 'datetime',
        'is_secret'   => 'boolean',
    ];

    public const TYPES = [
        'quote'   => '견적문의',
        'qna'     => '1:1문의',
        'request' => '상품요청',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
