<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('email')->nullable();          // 시도한 이메일(실패 포함)
            $t->boolean('success')->default(true);    // 성공/실패
            $t->string('ip', 45)->nullable();
            $t->string('user_agent', 512)->nullable();
            $t->string('guard', 20)->nullable();      // web | api 등
            $t->timestamp('created_at')->nullable()->index();
            $t->index(['user_id', 'created_at']);
            $t->index(['success', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};
