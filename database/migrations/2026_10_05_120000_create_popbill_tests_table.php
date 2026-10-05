<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 관리자 › 팝빌 테스트 화면에서 보낸 문자·발행한 현금영수증·세금계산서 기록.
 * 주문과 무관한 시험 문서라 tax_invoices / cash_receipts 와 섞지 않는다(매출·주문 이력 오염 방지).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('popbill_tests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind', 20);                   // sms / cashbill / taxinvoice
            $table->boolean('is_test_server');            // 팝빌 테스트 서버였는지 (POPBILL_IS_TEST)
            $table->string('receiver', 40)->nullable();   // 문자 번호 / 식별번호 / 공급받는자 사업자번호
            $table->string('receiver_name')->nullable();
            $table->unsignedInteger('amount')->default(0);
            $table->string('usage', 10)->nullable();      // 현금영수증 소득공제용/지출증빙용
            $table->text('content')->nullable();          // 문자 내용
            $table->string('mgt_key', 24)->nullable();
            $table->string('cancel_mgt_key', 24)->nullable();
            $table->string('confirm_num', 40)->nullable(); // 문자 접수번호 / 승인번호
            $table->string('trade_date', 8)->nullable();
            // sent / issued / cancelled / failed
            $table->string('status', 20);
            $table->string('popbill_state')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('popbill_tests');
    }
};
