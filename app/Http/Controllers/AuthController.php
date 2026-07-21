<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $cred = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($cred, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $to = Auth::user()->is_admin ? route('admin.dashboard') : route('home');

            return redirect()->intended($to)->with('ok', '로그인되었습니다.');
        }

        return back()->withInput($request->only('email'))
            ->withErrors(['email' => '이메일 또는 비밀번호가 올바르지 않습니다.']);
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'member_type' => ['required', Rule::in(['general', 'business'])],
            'name'        => ['required', 'string', 'max:50'],
            'email'       => ['required', 'email', 'unique:users,email'],
            'password'    => ['required', 'confirmed', 'min:8'],
            'phone'       => ['nullable', 'string', 'max:30'],
            // 사업자 전용
            'company_name' => ['required_if:member_type,business', 'nullable', 'string', 'max:100'],
            'biz_no'       => ['required_if:member_type,business', 'nullable', 'string', 'max:20'],
            'biz_type'     => ['nullable', 'string', 'max:50'],
            'agree'        => ['accepted'],
        ]);

        $isBusiness = $data['member_type'] === 'business';
        $signupPoint = config('site.signup_point', 0);

        $user = User::create([
            'name'         => $data['name'],
            'email'        => $data['email'],
            'password'     => Hash::make($data['password']),
            'phone'        => $data['phone'] ?? null,
            'member_type'  => $data['member_type'],
            'company_name' => $isBusiness ? $data['company_name'] : null,
            'biz_no'       => $isBusiness ? $data['biz_no'] : null,
            'biz_type'     => $isBusiness ? ($data['biz_type'] ?? null) : null,
            'biz_status'   => $isBusiness ? 'pending' : 'none',
            'point'        => $signupPoint,
        ]);

        // 가입 적립금 로그
        if ($signupPoint > 0) {
            $user->pointLogs()->create([
                'amount' => $signupPoint, 'balance' => $signupPoint, 'reason' => '신규가입 적립금',
            ]);
        }

        Auth::login($user);

        $msg = $isBusiness
            ? '회원가입이 완료되었습니다. 병원 승인 후 병원별 전용가가 적용됩니다.'
            : '회원가입이 완료되었습니다. 환영합니다!';

        return redirect()->route('home')->with('ok', $msg);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    // ===== 비밀번호 찾기(재설정) =====

    /** 비밀번호 찾기 — 이메일 입력 폼 */
    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    /** 재설정 링크 메일 발송 */
    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('ok', '비밀번호 재설정 링크를 이메일로 보냈습니다. 메일함(스팸함 포함)을 확인해 주세요.')
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => '해당 이메일로 가입된 계정을 찾을 수 없습니다.']);
    }

    /** 재설정 폼 (메일 링크의 토큰) */
    public function showResetPassword(Request $request, string $token)
    {
        return view('auth.reset-password', ['token' => $token, 'email' => $request->email]);
    }

    /** 새 비밀번호 저장 */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('ok', '비밀번호가 변경되었습니다. 새 비밀번호로 로그인해 주세요.')
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => '재설정 링크가 만료되었거나 올바르지 않습니다. 다시 시도해 주세요.']);
    }
}
