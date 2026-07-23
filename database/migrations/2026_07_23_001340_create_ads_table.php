<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('ads', function (Blueprint $t) {
            $t->id();
            $t->string('title', 100);
            $t->string('subtitle', 150)->nullable();
            $t->string('image')->nullable();            // 광고 이미지(없으면 그라디언트 카드)
            $t->string('bg_color', 100)->nullable();     // 이미지 없을 때 배경(그라디언트 가능)
            $t->unsignedInteger('price')->nullable();    // 광고가(선택)
            $t->string('badge', 30)->nullable();         // 예: AD, 특가, 신제품
            $t->string('link')->nullable();              // 클릭 이동 URL(외부 가능)
            $t->string('position', 10)->default('both'); // left | right | both
            $t->unsignedInteger('sort_order')->default(0);
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->index(['is_active', 'position']);
        });
    }
    public function down(): void { Schema::dropIfExists('ads'); }
};
