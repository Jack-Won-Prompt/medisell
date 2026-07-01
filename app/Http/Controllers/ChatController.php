<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageSent;
use App\Models\ChatRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    /** 대화방 열기 — 연락처 입력 여부 + 이력 반환 */
    public function open(Request $request)
    {
        $room = $this->resolveRoom($request);

        if ($room->unread_user > 0) {
            $room->update(['unread_user' => 0]);
        }

        return response()->json([
            'roomToken' => $room->token,
            'hasContact' => $room->hasContact(),
            // 회원이면 프로필 값으로 프리필
            'name'      => $room->guest_name ?: ($request->user()->name ?? ''),
            'phone'     => $room->guest_phone ?: ($request->user()->phone ?? ''),
            'messages'  => $room->messages()->get()->map->toWire(),
        ]);
    }

    /** 상담 시작 — 이름/전화번호 저장 */
    public function start(Request $request)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:30'],
            'phone' => ['required', 'string', 'max:30'],
        ]);

        $room = $this->resolveRoom($request);
        $room->update(['guest_name' => $data['name'], 'guest_phone' => $data['phone'], 'status' => 'open']);

        return response()->json([
            'roomToken' => $room->token,
            'messages'  => $room->messages()->get()->map->toWire(),
        ]);
    }

    /** 사용자 메시지 전송 */
    public function send(Request $request)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        $room = $this->resolveRoom($request);

        // 연락처 미입력 상태면 차단
        if (! $room->hasContact()) {
            return response()->json(['message' => '이름과 전화번호를 먼저 입력해 주세요.'], 422);
        }

        $msg = $room->messages()->create([
            'sender' => 'user',
            'body'   => $data['body'],
        ]);

        $room->forceFill([
            'status'          => 'open',
            'unread_admin'    => $room->unread_admin + 1,
            'last_message_at' => now(),
        ])->save();

        broadcast(new ChatMessageSent($msg));

        return response()->json(['message' => $msg->toWire(), 'roomToken' => $room->token]);
    }

    /** 회원=user_id 기준, 비회원=세션 토큰 기준 방 확보 */
    private function resolveRoom(Request $request): ChatRoom
    {
        if ($request->user()) {
            return ChatRoom::firstOrCreate(
                ['user_id' => $request->user()->id],
                ['token' => $this->newToken()]
            );
        }

        $token = $request->session()->get('chat_token');
        $room = $token ? ChatRoom::where('token', $token)->whereNull('user_id')->first() : null;

        if (! $room) {
            $room = ChatRoom::create(['token' => $this->newToken()]);
            $request->session()->put('chat_token', $room->token);
        }

        return $room;
    }

    private function newToken(): string
    {
        return Str::lower(Str::random(32));
    }
}
