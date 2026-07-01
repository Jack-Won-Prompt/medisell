<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // 회원 구분: general(일반) / business(사업자)
            $table->string('member_type')->default('general');
            $table->string('phone')->nullable();
            // 기본 배송지
            $table->string('postcode', 10)->nullable();
            $table->string('address1')->nullable();
            $table->string('address2')->nullable();

            // 사업자(병의원) 정보 + 승인 상태
            $table->string('company_name')->nullable();
            $table->string('biz_no', 20)->nullable();      // 사업자등록번호
            $table->string('biz_type')->nullable();        // 업태/종목 또는 의료기관 종별
            $table->string('biz_status')->default('none');  // none/pending/approved/rejected
            $table->string('grade')->default('basic');     // basic/silver/gold (할인 등급)

            $table->unsignedInteger('point')->default(0);  // 적립금 잔액
            $table->boolean('is_admin')->default(false);

            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
