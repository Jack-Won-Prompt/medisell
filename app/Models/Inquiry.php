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

    protected static function booted(): void
    {
        // 웹·앱 어디서 접수되든 관리자에게 메일+문자
        static::created(fn (Inquiry $q) => \App\Jobs\NotifyAdmin::dispatchAfterResponse('inquiry', $q->id));
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
