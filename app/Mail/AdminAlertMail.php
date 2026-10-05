<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** 관리자 알림 메일 — 회원가입·문의·실시간 상담·주문 공통 (제목 + 항목표 + 관리자 화면 링크) */
class AdminAlertMail extends Mailable
{
    /** @param array<string, string|null> $rows 항목명 => 값 */
    public function __construct(
        public string $subjectLine,
        public string $headline,
        public array $rows,
        public ?string $url = null,
        public ?string $body = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.admin-alert');
    }
}
