<?php

namespace App\Observers;

use App\Models\ChatMessage;
use App\Services\FcmService;

class ChatMessageObserver
{
    public function __construct(private FcmService $fcm) {}

    /** 관리자가 답변하면 해당 회원에게 푸시 */
    public function created(ChatMessage $message): void
    {
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
