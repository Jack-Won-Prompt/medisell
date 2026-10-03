<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** 신규 회원가입 알림 — 관리자용 (수신처: config('site.signup_mail_to')). 사업자등록증은 첨부하지 않고 관리자 화면 링크만 */
class NewMemberMail extends Mailable
{
    public function __construct(public User $member, public string $channel = '웹') {}

    public function envelope(): Envelope
    {
        $type = $this->member->member_type === 'business' ? '병원 회원(승인 대기)' : '일반 회원';
        $who = $this->member->company_name ? "{$this->member->company_name} {$this->member->name}" : $this->member->name;

        return new Envelope(subject: "[메디셀] 신규 회원가입 · {$type} · {$who}");
    }

    public function content(): Content
    {
        return new Content(view: 'emails.new-member');
    }
}
