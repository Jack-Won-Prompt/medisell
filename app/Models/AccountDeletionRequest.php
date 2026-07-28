<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountDeletionRequest extends Model
{
    protected $fillable = [
        'user_id', 'name', 'email', 'phone', 'reason',
        'status', 'note', 'processed_at', 'ip', 'user_agent',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending'  => '접수',
        'done'     => '처리완료',
        'rejected' => '반려',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
