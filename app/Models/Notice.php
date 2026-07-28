<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    protected $fillable = ['title', 'body', 'is_pinned', 'views', 'published_at', 'push_send', 'pushed_at'];

    protected $casts = [
        'is_pinned'    => 'boolean',
        'push_send'    => 'boolean',
        'published_at' => 'datetime',
        'pushed_at'    => 'datetime',
    ];

    /** 지금 회원에게 보이는 공지인지 (게시일이 없거나 미래면 아직 아님) */
    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->lte(now());
    }

    /** 푸시를 보내야 하는 상태인지 — 발송 선택 + 게시됨 + 아직 안 보냄 */
    public function shouldPush(): bool
    {
        return $this->push_send && $this->pushed_at === null && $this->isPublished();
    }
}
