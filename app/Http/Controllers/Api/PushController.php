<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class PushController extends Controller
{
    /** 디바이스 토큰 등록/갱신 (로그인 회원과 연결) */
    public function register(Request $request)
    {
        $data = $request->validate([
            'token'    => ['required', 'string', 'max:255'],
            'platform' => ['nullable', 'in:android,ios,web'],
        ]);

        DeviceToken::updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id'      => $request->user()->id,
                'platform'     => $data['platform'] ?? 'android',
                'last_used_at' => now(),
            ]
        );

        return response()->json(['message' => '알림이 활성화되었습니다.']);
    }

    /** 토큰 해제 (로그아웃/알림끄기) */
    public function unregister(Request $request)
    {
        $data = $request->validate(['token' => ['required', 'string', 'max:255']]);
        DeviceToken::where('token', $data['token'])->delete();

        return response()->json(['message' => '알림이 해제되었습니다.']);
    }
}
