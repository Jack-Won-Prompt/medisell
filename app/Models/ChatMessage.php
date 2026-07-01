<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = ['chat_room_id', 'sender', 'admin_id', 'body'];

    public function room()
    {
        return $this->belongsTo(ChatRoom::class, 'chat_room_id');
    }

    /** 위젯/콘솔 표시용 직렬화 */
    public function toWire(): array
    {
        return [
            'id'     => $this->id,
            'sender' => $this->sender,
            'body'   => $this->body,
            'time'   => $this->created_at->format('H:i'),
            'date'   => $this->created_at->format('Y-m-d'),
        ];
    }
}
