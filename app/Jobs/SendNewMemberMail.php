<?php

namespace App\Jobs;

use App\Mail\NewMemberMail;
use App\Models\User;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;

/**
 * 신규 회원가입 알림 메일 — 가입 응답을 보낸 뒤(dispatchAfterResponse) 발송.
 * SMTP 가 느리거나 실패해도 가입은 막지 않고 report() 로만 남긴다.
 */
class SendNewMemberMail
{
    use Dispatchable;

    public function __construct(public int $userId, public string $channel = '웹') {}

    public function handle(): void
    {
        $to = array_values(array_filter((array) config('site.signup_mail_to', [])));
        $user = User::find($this->userId);
        if (! $user || ! $to) {
            return;
        }

        try {
            Mail::to($to)->send(new NewMemberMail($user, $this->channel));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
