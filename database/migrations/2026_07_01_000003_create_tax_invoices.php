<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('tax_type')->default('taxable')->after('member_price'); // taxable(과세)/exempt(면세)
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('biz_ceo')->nullable()->after('biz_type'); // 병원 대표자명(세금계산서용)
        });

        Schema::create('tax_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('mgt_key')->unique();                 // 문서관리번호(발행자 기준 유일)
            $table->string('invoice_kind')->default('tax');      // tax(세금계산서)/plain(계산서·면세)

            $table->unsignedInteger('supply_amount')->default(0); // 공급가액
            $table->unsignedInteger('tax_amount')->default(0);    // 세액
            $table->unsignedInteger('total_amount')->default(0);  // 합계

            // 공급받는자(병원) 스냅샷
            $table->string('receiver_corp_num')->nullable();
            $table->string('receiver_corp_name')->nullable();
            $table->string('receiver_ceo')->nullable();
            $table->string('receiver_email')->nullable();

            $table->string('status')->default('issued');   // issued/cancelled/failed/simulated
            $table->string('popbill_state')->nullable();    // 팝빌 상태
            $table->string('nts_confirm_num')->nullable();  // 국세청 승인번호
            $table->text('error_message')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_invoices');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('biz_ceo'));
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn('tax_type'));
    }
};
