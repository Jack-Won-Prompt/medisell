<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** 고객 안내 문자 발송 이력 (팝빌) */
class SmsLog extends Model
{
    protected $fillable = [
        'order_id', 'user_id', 'kind', 'receiver', 'msg_type', 'content',
        'status', 'receipt_num', 'error_message',
    ];

    public const KINDS = [
        'paid'         => '결제 완료',
        'shipped'      => '배송 시작',
        'bank_guide'   => '무통장 입금 안내',
        'biz_approved' => '병원 회원 승인',
        'biz_rejected' => '병원 회원 반려',
        // 관리자 알림
        'admin_signup'  => '관리자 · 회원가입',
        'admin_inquiry' => '관리자 · 문의',
        'admin_chat'    => '관리자 · 실시간 상담',
        'admin_order'   => '관리자 · 주문',
        'admin_paid'    => '관리자 · 결제 완료',
    ];

    public const STATUSES = [
        'sent'       => '발송',
        'redirected' => '테스트번호 발송',
        'simulated'  => '시뮬레이트',
        'failed'     => '실패',
    ];

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
