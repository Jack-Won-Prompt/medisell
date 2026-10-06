<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\ApiSerializer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
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
            // 앱은 아직 파일 첨부 화면이 없어 선택 — 없으면 관리자가 승인 전에 받아서 올린다 (웹 가입은 필수)
            'biz_cert'     => ['nullable', ...User::BIZ_CERT_RULE],
            'care_code'    => ['nullable', 'string', 'max:20'],
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
            'care_code'    => $isBusiness ? ($data['care_code'] ?? null) : null,
            'biz_status'   => $isBusiness ? 'pending' : 'none',
            'point'        => (int) config('site.signup_point', 0),
        ]);
        if ($isBusiness && $request->hasFile('biz_cert')) {
            $user->storeBizCert($request->file('biz_cert'));
        }
        \App\Jobs\NotifyAdmin::dispatchAfterResponse('signup', $user->id, '앱');   // 관리자 메일+문자

        if ($user->point > 0) {
            $user->pointLogs()->create([
                'amount'  => $user->point,
                'balance' => $user->point,
                'reason'  => '회원가입 적립',
            ]);
        }

        return $this->tokenResponse($user, '회원가입이 완료되었습니다.');
    }

    /** 비밀번호 찾기 — 웹과 같은 재설정 링크 메일 발송 (링크는 웹 재설정 화면으로 열린다) */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => ['required', 'email']], [
            'email.required' => '가입하신 이메일을 입력해 주세요.',
            'email.email'    => '이메일 형식이 올바르지 않습니다.',
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => '비밀번호 재설정 링크를 이메일로 보냈습니다. 메일함(스팸함 포함)을 확인해 주세요.']);
        }
        if ($status === Password::RESET_THROTTLED) {
            return response()->json(['message' => '잠시 후 다시 시도해 주세요.'], 429);
        }

        return response()->json(['message' => '해당 이메일로 가입된 계정을 찾을 수 없습니다.'], 422);
    }

    /**
     * 회원 탈퇴(계정 삭제) 요청 — 웹 /account-deletion 과 같이 접수만 하고 관리자가 처리한다.
     * 구글 플레이 정책: 앱 안에서 계정 삭제를 요청할 수 있어야 한다.
     */
    public function deletionRequest(Request $request)
    {
        $data = $request->validate([
            'reason'  => ['nullable', 'string', 'max:200'],
            'confirm' => ['accepted'],
        ], [
            'confirm.accepted' => '삭제 시 복구가 불가능하다는 점에 동의해 주세요.',
        ]);
        $user = $request->user();

        $pending = \App\Models\AccountDeletionRequest::where('status', 'pending')
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('email', $user->email))->exists();
        if ($pending) {
            return response()->json(['message' => '이미 접수된 요청이 있습니다. 영업일 기준 3일 이내에 처리해 드립니다.', 'already' => true]);
        }

        \App\Models\AccountDeletionRequest::create([
            'user_id'    => $user->id,
            'name'       => $user->name,
            'email'      => $user->email,
            'phone'      => $user->phone,
            'reason'     => $data['reason'] ?? null,
            'status'     => 'pending',
            'ip'         => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 300),
        ]);

        return response()->json(['message' => '계정 삭제 요청이 접수되었습니다. 영업일 기준 3일 이내에 처리 결과를 이메일로 알려드립니다.', 'already' => false], 201);
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
