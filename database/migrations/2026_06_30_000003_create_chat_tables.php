<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1:1 상담 대화방 (회원 또는 비회원)
        Schema::create('chat_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token', 40)->unique();           // 공개 채널/식별용 토큰
            $table->string('guest_name')->nullable();        // 비회원 이름
            $table->string('status')->default('open');       // open/closed
            $table->unsignedInteger('unread_admin')->default(0); // 관리자 미확인 수
            $table->unsignedInteger('unread_user')->default(0);  // 사용자 미확인 수
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        // 메시지
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_room_id')->constrained()->cascadeOnDelete();
            $table->string('sender');                        // user / admin
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_rooms');
    }
};
