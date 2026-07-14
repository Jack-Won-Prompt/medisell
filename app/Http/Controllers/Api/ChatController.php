<?php

namespace App\Http\Controllers\Api;

use App\Events\ChatMessageSent;
use App\Http\Controllers\Controller;
use App\Models\ChatRoom;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * 모바일 실시간 상담 — 회원(Sanctum) 기준 방 1개.
 * 실시간 수신은 앱이 Pusher 공개채널 chat-{token}, event `message` 를 직접 구독한다.
 */
class ChatController extends Controller
{
    public function open(Request $request)
    {
        $room = $this->room($request);
        if ($room->unread_user > 0) {
            $room->update(['unread_user' => 0]);
        }

        return response()->json([
            'room_token'  => $room->token,
            'has_contact' => $room->hasContact(),
            'name'        => $room->guest_name ?: ($request->user()->name ?? ''),
            'phone'       => $room->guest_phone ?: ($request->user()->phone ?? ''),
            'messages'    => $room->messages()->get()->map->toWire(),
            'pusher'      => [
                'key'     => config('broadcasting.connections.pusher.key'),
                'cluster' => config('broadcasting.connections.pusher.options.cluster'),
            ],
        ]);
    }

    public function start(Request $request)
    {
        $data = $request->validate([
            'name'  => ['required', 'string', 'max:30'],
            'phone' => ['required', 'string', 'max:30'],
        ]);
        $room = $this->room($request);
        $room->update(['guest_name' => $data['name'], 'guest_phone' => $data['phone'], 'status' => 'open']);

        return response()->json([
            'room_token' => $room->token,
            'messages'   => $room->messages()->get()->map->toWire(),
        ]);
    }

    public function send(Request $request)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);
        $room = $this->room($request);

        if (! $room->hasContact()) {
            return response()->json(['message' => '이름과 전화번호를 먼저 입력해 주세요.'], 422);
        }

        $msg = $room->messages()->create(['sender' => 'user', 'body' => $data['body']]);
        $room->forceFill([
            'status' => 'open', 'unread_admin' => $room->unread_admin + 1, 'last_message_at' => now(),
        ])->save();

        broadcast(new ChatMessageSent($msg));

        return response()->json(['message' => $msg->toWire(), 'room_token' => $room->token]);
    }

    private function room(Request $request): ChatRoom
    {
        return ChatRoom::firstOrCreate(
            ['user_id' => $request->user()->id],
            ['token' => Str::lower(Str::random(32))]
        );
    }
}
