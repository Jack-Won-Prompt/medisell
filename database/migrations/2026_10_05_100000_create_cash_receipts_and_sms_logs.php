<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 팝빌 현금영수증 · 문자.
 *
 * orders.cash_receipt_type/identity — 무통장 주문 때 고객이 고른 현금영수증 신청(입금 확인 시 자동 발행)
 * cash_receipts — 현금영수증 발행·취소 이력 (세금계산서 tax_invoices 와 같은 방식)
 * sms_logs   — 고객 안내 문자 발송 이력 (시뮬레이트·테스트수신 포함)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('cash_receipt_type', 20)->nullable()->after('depositor');       // income(소득공제) / expense(지출증빙)
            $table->string('cash_receipt_identity', 30)->nullable()->after('cash_receipt_type'); // 휴대폰번호 또는 사업자번호(숫자만)
        });

        Schema::create('cash_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mgt_key', 24)->unique();          // 승인거래 문서번호
            $table->string('cancel_mgt_key', 24)->nullable(); // 취소거래 문서번호
            $table->string('trade_usage', 10);                // 소득공제용 / 지출증빙용
            $table->string('identity_num', 30);
            $table->unsignedInteger('supply_amount');
            $table->unsignedInteger('tax_amount');
            $table->unsignedInteger('total_amount');
            // issued(발행) / cancelled(취소) / failed(실패) / simulated(시뮬레이트)
            $table->string('status', 20);
            $table->string('confirm_num', 30)->nullable();    // 국세청 승인번호
            $table->string('trade_date', 8)->nullable();      // 거래일자 yyyyMMdd (취소 때 필요)
            $table->string('popbill_state')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 30);           // paid / shipped / bank_guide / biz_approved / biz_rejected
            $table->string('receiver', 20);       // 실제 받는 번호
            $table->string('msg_type', 5);        // SMS / LMS
            $table->text('content');
            // sent(발송) / simulated(시뮬레이트) / redirected(테스트번호로 발송) / failed(실패)
            $table->string('status', 20);
            $table->string('receipt_num', 40)->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->index(['kind', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('cash_receipts');
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['cash_receipt_type', 'cash_receipt_identity']);
        });
    }
};
