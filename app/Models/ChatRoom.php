<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatRoom extends Model
{
    protected $fillable = [
        'user_id', 'token', 'guest_name', 'guest_phone', 'status',
        'unread_admin', 'unread_user', 'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class)->orderBy('id');
    }

    public function displayName(): string
    {
        return $this->guest_name ?: ($this->user?->name ?? '비회원');
    }

    public function displayPhone(): ?string
    {
        return $this->guest_phone ?: $this->user?->phone;
    }

    /** 상담 시작에 필요한 연락처가 입력되었는지 */
    public function hasContact(): bool
    {
        return ! empty($this->guest_name) && ! empty($this->guest_phone);
    }
}
