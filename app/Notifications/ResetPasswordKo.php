<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * 한글·메디셀 브랜드 비밀번호 재설정 메일.
 */
class ResetPasswordKo extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);
        $expire = config('auth.passwords.'.config('auth.defaults.passwords').'.expire', 60);

        return (new MailMessage)
            ->subject('[메디셀] 비밀번호 재설정 안내')
            ->greeting('안녕하세요, 메디셀입니다.')
            ->line('비밀번호 재설정 요청을 받았습니다. 아래 버튼을 눌러 새 비밀번호를 설정해 주세요.')
            ->action('비밀번호 재설정', $url)
            ->line("이 링크는 {$expire}분 후 만료됩니다.")
            ->line('본인이 요청하지 않았다면 이 메일을 무시하셔도 됩니다. 비밀번호는 변경되지 않습니다.')
            ->salutation("감사합니다.\n메디셀(MEDISELL) 드림");
    }
}
