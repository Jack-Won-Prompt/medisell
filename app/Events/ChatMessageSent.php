<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public ChatMessage $message)
    {
    }

    /** 공개 채널: 해당 방 + 관리자 콘솔 */
    public function broadcastOn(): array
    {
        $room = $this->message->room;

        return [
            new Channel('chat-'.$room->token),
            new Channel('chat-admin'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message';
    }

    public function broadcastWith(): array
    {
        $room = $this->message->room;

        return [
            'message'   => $this->message->toWire(),
            'roomToken' => $room->token,
            'roomId'    => $room->id,
            'roomName'  => $room->displayName(),
        ];
    }
}
