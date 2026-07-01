<?php

namespace App\Http\Controllers\Admin;

use App\Events\ChatMessageSent;
use App\Http\Controllers\Controller;
use App\Models\ChatRoom;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /** 상담 콘솔 (대화방 목록) */
    public function index()
    {
        $rooms = ChatRoom::with('user')
            ->withCount('messages')
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->paginate(30);

        return view('admin.chat.index', compact('rooms'));
    }

    /** 특정 방의 메시지 (AJAX) — 열람 시 관리자 미확인 0 */
    public function show(ChatRoom $room)
    {
        $room->update(['unread_admin' => 0]);

        return response()->json([
            'roomId'   => $room->id,
            'token'    => $room->token,
            'name'     => $room->displayName(),
            'phone'    => $room->displayPhone(),
            'messages' => $room->messages()->get()->map->toWire(),
        ]);
    }

    /** 관리자 답장 */
    public function reply(Request $request, ChatRoom $room)
    {
        $data = $request->validate(['body' => ['required', 'string', 'max:1000']]);

        $msg = $room->messages()->create([
            'sender'   => 'admin',
            'admin_id' => $request->user()->id,
            'body'     => $data['body'],
        ]);

        $room->forceFill([
            'unread_user'     => $room->unread_user + 1,
            'last_message_at' => now(),
        ])->save();

        broadcast(new ChatMessageSent($msg));

        return response()->json(['message' => $msg->toWire()]);
    }
}
