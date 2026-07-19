<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 로그인 이력 — 성공/실패 시도 기록.
 * created_at만 사용(수정 개념 없음) → $timestamps=false, created_at 직접 관리.
 */
class LoginLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'email', 'success', 'ip', 'user_agent', 'guard', 'created_at',
    ];

    protected $casts = [
        'success'    => 'boolean',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
