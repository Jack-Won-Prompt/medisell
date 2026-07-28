<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 공지사항 푸시 발송 제어 컬럼.
 *
 * push_send  — 관리자가 이 공지를 푸시로 보낼지 건별로 고른다(기본 꺼짐).
 *              무조건 자동 발송하면 오탈자 수정 재저장에도 알림이 나간다.
 * pushed_at  — 이미 보낸 공지를 다시 보내지 않기 위한 발송 시각.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->boolean('push_send')->default(false)->after('is_pinned');
            $table->timestamp('pushed_at')->nullable()->after('push_send');
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table) {
            $table->dropColumn(['push_send', 'pushed_at']);
        });
    }
};
