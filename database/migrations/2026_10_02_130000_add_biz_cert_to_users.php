<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 병원 회원 승인용 서류.
 *
 * biz_cert_path — 사업자등록증(이미지·PDF). 비공개 디스크(storage/app/private)에 두고 관리자 화면에서만 연다.
 * care_code     — 요양기관기호(선택). 심평원 병원찾기로 실제 운영 기관인지 확인할 때 쓴다.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('biz_cert_path')->nullable()->after('biz_type');
            $table->string('care_code', 20)->nullable()->after('biz_cert_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['biz_cert_path', 'care_code']);
        });
    }
};
