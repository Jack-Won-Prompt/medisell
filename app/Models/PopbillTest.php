<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** 관리자 팝빌 테스트 기록 (문자·현금영수증·세금계산서) */
class PopbillTest extends Model
{
    protected $fillable = [
        'admin_id', 'kind', 'is_test_server', 'receiver', 'receiver_name', 'amount', 'usage', 'content',
        'mgt_key', 'cancel_mgt_key', 'confirm_num', 'trade_date', 'status', 'popbill_state',
        'error_message', 'cancelled_at',
    ];

    protected $casts = [
        'is_test_server' => 'boolean',
        'cancelled_at'   => 'datetime',
    ];

    public const KINDS = ['sms' => '문자', 'cashbill' => '현금영수증', 'taxinvoice' => '세금계산서'];

    public const STATUSES = ['sent' => '발송', 'issued' => '발행', 'cancelled' => '취소', 'failed' => '실패'];

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function cancellable(): bool
    {
        return $this->status === 'issued' && in_array($this->kind, ['cashbill', 'taxinvoice'], true);
    }
}
