<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * PG 심사 등에 쓸 테스트 계정을 만들어 준다.
 *
 * 관리자에 회원 생성 기능이 없고 회원가입 폼은 비밀번호 8자 이상이라,
 * 짧은 심사용 비밀번호를 가진 계정은 이 명령으로만 만들 수 있다.
 *
 * 이미 있는 계정이면 비밀번호만 갱신한다(주문 이력 보존).
 */
class SyncDemoAccount extends Command
{
    protected $signature = 'demo:account
        {--email= : 계정 이메일 (필수)}
        {--password= : 계정 비밀번호 (필수)}
        {--name=테스트계정 : 회원 이름}';

    protected $description = 'PG 심사용 테스트 계정 생성/비밀번호 갱신';

    public function handle(): int
    {
        $email = (string) $this->option('email');
        $password = (string) $this->option('password');

        if ($email === '' || $password === '') {
            $this->error('--email 과 --password 를 모두 지정해야 합니다.');

            return self::FAILURE;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("이메일 형식이 올바르지 않습니다: {$email}");

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            if ($user->is_admin) {
                $this->error("{$email} 은 관리자 계정입니다. 테스트 계정으로 쓰지 마세요.");

                return self::FAILURE;
            }
            $user->password = $password;
            $user->save();
            $this->info("기존 계정 비밀번호를 갱신했습니다: {$email} (id={$user->id})");
        } else {
            $user = User::create([
                'name'        => (string) $this->option('name'),
                'email'       => $email,
                'password'    => $password,
                'member_type' => 'general',
                'is_admin'    => false,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
            $this->info("테스트 계정을 만들었습니다: {$email} (id={$user->id})");
        }

        $this->line('  비밀번호: '.$password);

        return self::SUCCESS;
    }
}
