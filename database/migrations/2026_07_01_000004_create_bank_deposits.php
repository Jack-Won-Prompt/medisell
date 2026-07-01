<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 계좌조회 수집 작업
        Schema::create('bank_collect_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_id')->nullable();      // 팝빌 작업아이디
            $table->string('bank_code');
            $table->string('account_num');
            $table->string('s_date', 8);               // 조회 시작 YYYYMMDD
            $table->string('e_date', 8);
            $table->string('state')->default('requested'); // requested/collecting/done/failed
            $table->unsignedInteger('tx_count')->default(0);
            $table->timestamps();
        });

        // 입금 거래내역
        Schema::create('bank_deposits', function (Blueprint $table) {
            $table->id();
            $table->string('tid')->nullable()->unique();  // 거래 고유번호(중복적재 방지)
            $table->string('bank_code');
            $table->string('account_num');
            $table->date('trade_date')->nullable();
            $table->string('trade_time')->nullable();
            $table->unsignedInteger('amount');            // 입금액(acc_in)
            $table->unsignedBigInteger('balance')->nullable();
            $table->string('depositor')->nullable();      // 입금자명(적요)
            $table->foreignId('matched_order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_deposits');
        Schema::dropIfExists('bank_collect_jobs');
    }
};
