<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coupons', function (Blueprint $table) {
            // true=공개(코드입력 누구나) / false=발행형(발행받은 회원만)
            $table->boolean('is_public')->default(true)->after('is_active');
        });

        // 회원에게 발행된 쿠폰
        Schema::create('user_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('used_at')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->unique(['coupon_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_coupons');
        Schema::table('coupons', fn (Blueprint $t) => $t->dropColumn('is_public'));
    }
};
