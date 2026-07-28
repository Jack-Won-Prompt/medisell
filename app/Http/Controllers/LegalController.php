<?php

namespace App\Http\Controllers;

use App\Models\AccountDeletionRequest;
use Illuminate\Http\Request;

/**
 * 약관·개인정보처리방침·계정 삭제 요청.
 *
 * 세 페이지 모두 로그인 없이 열려야 한다 — 구글 플레이 심사에서
 * 앱 밖에서 접근 가능한 URL 을 요구하기 때문이다.
 */
class LegalController extends Controller
{
    public function terms()
    {
        return view('legal.terms');
    }

    public function privacy()
    {
        return view('legal.privacy');
    }

    public function accountDeletion()
    {
        return view('legal.account-deletion');
    }

    public function storeAccountDeletion(Request $request)
    {
        $data = $request->validate([
            'name'    => ['required', 'string', 'max:50'],
            'email'   => ['required', 'email', 'max:100'],
            'phone'   => ['nullable', 'string', 'max:30'],
            'reason'  => ['nullable', 'string', 'max:200'],
            'confirm' => ['accepted'],
        ], [
            'name.required'    => '이름을 입력해 주세요.',
            'email.required'   => '가입하신 이메일을 입력해 주세요.',
            'email.email'      => '이메일 형식이 올바르지 않습니다.',
            'confirm.accepted' => '삭제 시 복구가 불가능하다는 점에 동의해 주세요.',
        ]);

        // 같은 이메일로 접수 중인 건이 있으면 중복 접수하지 않는다
        $pending = AccountDeletionRequest::where('email', $data['email'])
            ->where('status', 'pending')->first();

        if ($pending) {
            return back()->with('ok', '이미 접수된 요청이 있습니다. 영업일 기준 3일 이내에 처리해 드립니다.');
        }

        AccountDeletionRequest::create($data + [
            'user_id'    => $request->user()?->id,
            'status'     => 'pending',
            'ip'         => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 300),
        ]);

        return back()->with('ok', '계정 삭제 요청이 접수되었습니다. 영업일 기준 3일 이내에 처리 결과를 이메일로 알려드립니다.');
    }
}
