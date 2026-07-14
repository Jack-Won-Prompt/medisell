<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiSerializer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'member_type'  => ['required', 'in:general,business'],
            'name'         => ['required', 'string', 'max:50'],
            'email'        => ['required', 'email', 'max:100', 'unique:users,email'],
            'password'     => ['required', 'string', 'min:8', 'confirmed'],
            'phone'        => ['nullable', 'string', 'max:30'],
            'company_name' => ['required_if:member_type,business', 'nullable', 'string', 'max:100'],
            'biz_no'       => ['required_if:member_type,business', 'nullable', 'string', 'max:20'],
            'biz_type'     => ['nullable', 'string', 'max:50'],
        ]);

        $isBusiness = $data['member_type'] === 'business';

        $user = User::create([
            'member_type'  => $data['member_type'],
            'name'         => $data['name'],
            'email'        => $data['email'],
            'password'     => Hash::make($data['password']),
            'phone'        => $data['phone'] ?? null,
            'company_name' => $isBusiness ? ($data['company_name'] ?? null) : null,
            'biz_no'       => $isBusiness ? ($data['biz_no'] ?? null) : null,
            'biz_type'     => $isBusiness ? ($data['biz_type'] ?? null) : null,
            'biz_status'   => $isBusiness ? 'pending' : 'none',
            'point'        => (int) config('site.signup_point', 0),
        ]);

        if ($user->point > 0) {
            $user->pointLogs()->create([
                'amount'  => $user->point,
                'balance' => $user->point,
                'reason'  => '회원가입 적립',
            ]);
        }

        return $this->tokenResponse($user, '회원가입이 완료되었습니다.');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
            'device'   => ['nullable', 'string', 'max:80'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['이메일 또는 비밀번호가 올바르지 않습니다.'],
            ]);
        }

        return $this->tokenResponse($user, '로그인되었습니다.', $data['device'] ?? 'mobile');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => '로그아웃되었습니다.']);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => ApiSerializer::user($request->user())]);
    }

    private function tokenResponse(User $user, string $message, string $device = 'mobile')
    {
        $token = $user->createToken($device)->plainTextToken;

        return response()->json([
            'message' => $message,
            'token'   => $token,
            'user'    => ApiSerializer::user($user),
        ]);
    }
}
