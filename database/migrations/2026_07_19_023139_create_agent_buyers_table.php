<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('agent_buyers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $t->string('hospital_name', 100);   // 병원명
            $t->string('buyer_name', 50);       // 구매자 이름
            $t->string('buyer_phone', 30);      // 구매자 전화번호
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index('agent_id');
        });
    }
    public function down(): void { Schema::dropIfExists('agent_buyers'); }
};
