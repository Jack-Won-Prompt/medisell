<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('pay_provider')->nullable()->after('payment_method'); // toss 등
            $table->string('payment_key')->nullable()->after('pay_provider');     // 토스 paymentKey
            $table->string('pay_status')->nullable()->after('payment_key');       // 토스 결제 상태(DONE/WAITING_FOR_DEPOSIT 등)
            $table->string('pay_method')->nullable()->after('pay_status');         // 카드/가상계좌 등 실제 수단
            // 가상계좌 정보
            $table->string('va_bank')->nullable()->after('depositor');
            $table->string('va_account')->nullable()->after('va_bank');
            $table->string('va_holder')->nullable()->after('va_account');
            $table->timestamp('va_due_at')->nullable()->after('va_holder');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['pay_provider', 'payment_key', 'pay_status', 'pay_method', 'va_bank', 'va_account', 'va_holder', 'va_due_at']);
        });
    }
};
