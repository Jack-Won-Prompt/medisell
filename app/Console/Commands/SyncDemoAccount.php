<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

/**
 * 로그인 화면에 안내 중인 테스트 계정을 실제로 만들어 준다.
 *
 * 관리자 > 사이트설정의 "테스트 계정 안내"는 화면에 문구를 띄울 뿐이라
 * 계정 자체는 따로 있어야 한다. 회원가입 폼은 비밀번호 8자 이상이라
 * 짧은 심사용 비밀번호를 만들 수 없어 이 명령으로 처리한다.
 *
 * 이미 있는 계정이면 비밀번호만 안내값에 맞춰 갱신한다(주문 이력 보존).
 */
class SyncDemoAccount extends Command
{
    protected $signature = 'demo:account
        {--email= : 사이트설정 대신 쓸 이메일}
        {--password= : 사이트설정 대신 쓸 비밀번호}
        {--name=테스트계정 : 회원 이름}';

    protected $description = '로그인 화면 안내용 테스트 계정 생성/비밀번호 갱신';

    public function handle(): int
    {
        $demo = config('site.demo_login', []);
        $email = (string) ($this->option('email') ?: ($demo['email'] ?? ''));
        $password = (string) ($this->option('password') ?: ($demo['password'] ?? ''));

        if ($email === '' || $password === '') {
            $this->error('이메일/비밀번호가 비어 있습니다. 관리자 > 사이트설정에서 먼저 입력하거나 --email --password 로 지정하세요.');

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
        $this->line('  로그인 화면 노출: '.(($demo['enabled'] ?? false) ? '켜짐' : '꺼짐 — 관리자 > 사이트설정에서 켜세요'));

        if (($demo['email'] ?? '') !== '' && $demo['email'] !== $email) {
            $this->warn("주의: 로그인 화면에 안내 중인 이메일({$demo['email']})과 다릅니다.");
        }

        return self::SUCCESS;
    }
}
