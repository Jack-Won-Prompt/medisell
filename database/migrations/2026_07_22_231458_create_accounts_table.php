<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('accounts', function (Blueprint $t) {
            $t->id();
            $t->string('name', 100);                        // 거래처명 (병원/기업)
            $t->string('code', 50)->nullable()->unique();   // 거래처 코드(선택)
            $t->decimal('discount_rate', 5, 2)->default(0); // 등급별 일괄 할인율(%)
            $t->boolean('is_active')->default(true);
            $t->string('memo', 255)->nullable();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('accounts'); }
};
