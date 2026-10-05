<?php

namespace App\Observers;

use App\Models\ChatMessage;
use App\Services\FcmService;

class ChatMessageObserver
{
    public function __construct(private FcmService $fcm) {}

    /** 고객 메시지 → 관리자 메일+문자 / 관리자 답변 → 해당 회원에게 푸시 */
    public function created(ChatMessage $message): void
    {
        if ($message->sender === 'user') {
            // 대화 중 메시지마다 보내면 폭주 — 상담방마다 10분에 한 번만 (첫 메시지는 항상)
            if (\Illuminate\Support\Facades\Cache::add('admin-chat-alert:'.$message->chat_room_id, 1, now()->addMinutes(10))) {
                \App\Jobs\NotifyAdmin::dispatchAfterResponse('chat', $message->id);
            }

            return;
        }
        if ($message->sender !== 'admin') {
            return;
        }
        $room = $message->room;
        if (! $room || ! $room->user_id) {
            return;
        }

        $preview = mb_strlen($message->body) > 40 ? mb_substr($message->body, 0, 40).'…' : $message->body;

        $this->fcm->sendToUser($room->user_id, '상담 답변이 도착했습니다', $preview, [
            'type'       => 'chat',
            'room_token' => $room->token,
        ]);
    }
}
