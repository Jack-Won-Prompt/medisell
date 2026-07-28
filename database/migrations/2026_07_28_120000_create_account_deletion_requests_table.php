<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 계정 삭제 요청 접수함.
 *
 * 구글 플레이는 앱 밖(웹)에서도 로그인 없이 계정 삭제를 요청할 수 있는
 * 공개 URL 을 요구한다. 비로그인 요청도 받아야 하므로 user_id 는 널을 허용하고,
 * 본인 확인은 관리자가 접수 내용(이메일·연락처)으로 처리한다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_deletion_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 50);
            $table->string('email', 100)->index();
            $table->string('phone', 30)->nullable();
            $table->string('reason', 200)->nullable();
            $table->string('status', 20)->default('pending')->index();  // pending | done | rejected
            $table->string('note', 300)->nullable();                    // 처리 메모(관리자)
            $table->timestamp('processed_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 300)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_deletion_requests');
    }
};
