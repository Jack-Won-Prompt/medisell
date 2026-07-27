<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasTable('market_prices')) return;
        Schema::create('market_prices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained()->cascadeOnDelete();
            $t->string('channel', 20);                  // coupang | naver | medical
            $t->string('seller', 100)->nullable();      // 판매처(스토어)명
            $t->string('title', 200)->nullable();       // 경쟁 상품명
            $t->unsignedInteger('price');
            $t->string('delivery', 40)->nullable();
            $t->string('url', 500)->nullable();
            $t->timestamp('fetched_at')->nullable();
            $t->timestamps();
            $t->unique(['product_id', 'channel']);      // 상품·채널당 최저가 1건
        });
    }
    public function down(): void {
        Schema::dropIfExists('market_prices');
    }
};
