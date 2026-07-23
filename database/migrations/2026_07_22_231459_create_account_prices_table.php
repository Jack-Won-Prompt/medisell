<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('account_prices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $t->unsignedInteger('price');
            $t->timestamps();
            $t->unique(['account_id', 'product_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('account_prices'); }
};
