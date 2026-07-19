<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 구매 대행자가 대신 구매해 주는 구매자(병원).
 * 1 대행자(agent) → N 구매자(buyer).
 */
class AgentBuyer extends Model
{
    protected $fillable = [
        'agent_id', 'hospital_name', 'buyer_name', 'buyer_phone', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    /** "병원명 · 구매자" 표기 */
    public function label(): string
    {
        return trim($this->hospital_name.' · '.$this->buyer_name, ' ·');
    }
}
