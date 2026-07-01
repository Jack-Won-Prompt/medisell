<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
}
