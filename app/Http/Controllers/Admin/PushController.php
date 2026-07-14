<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Http\Request;

class PushController extends Controller
{
    public function index(FcmService $fcm)
    {
        return view('admin.push', [
            'enabled'    => $fcm->enabled(),
            'tokenCount' => DeviceToken::count(),
            'userCount'  => DeviceToken::whereNotNull('user_id')->distinct('user_id')->count('user_id'),
        ]);
    }

    public function send(Request $request, FcmService $fcm)
    {
        $data = $request->validate([
            'title'   => ['required', 'string', 'max:60'],
            'body'    => ['required', 'string', 'max:200'],
            'target'  => ['required', 'in:all,business,general,email'],
            'email'   => ['required_if:target,email', 'nullable', 'email'],
            'link'    => ['nullable', 'string', 'max:200'], // 탭 시 이동할 앱 내 경로(예: /product/slug)
        ]);

        if (! $fcm->enabled()) {
            return back()->with('error', 'FCM 설정이 완료되지 않았습니다. (config/fcm.php · 서비스계정 키 확인)');
        }

        $payload = ['type' => 'notice'];
        if (! empty($data['link'])) {
            $payload['link'] = $data['link'];
        }

        $sent = 0;
        switch ($data['target']) {
            case 'all':
                $sent = $fcm->sendToTokens(DeviceToken::pluck('token'), $data['title'], $data['body'], $payload);
                break;
            case 'business':
            case 'general':
                $ids = User::where('member_type', $data['target'])->pluck('id');
                $tokens = DeviceToken::whereIn('user_id', $ids)->pluck('token');
                $sent = $fcm->sendToTokens($tokens, $data['title'], $data['body'], $payload);
                break;
            case 'email':
                $user = User::where('email', $data['email'])->first();
                if (! $user) {
                    return back()->with('error', '해당 이메일의 회원을 찾을 수 없습니다.');
                }
                $tokens = DeviceToken::where('user_id', $user->id)->pluck('token');
                $sent = $fcm->sendToTokens($tokens, $data['title'], $data['body'], $payload);
                break;
        }

        return back()->with('ok', "푸시 알림을 {$sent}건 발송했습니다.");
    }
}
