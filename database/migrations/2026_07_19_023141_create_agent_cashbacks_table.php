<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::create('agent_cashbacks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('agent_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $t->unsignedInteger('amount');
            $t->decimal('rate', 5, 2);
            $t->string('status', 20)->default('pending'); // pending | paid | cancelled
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
            $t->index(['agent_id', 'status']);
        });
    }
    public function down(): void { Schema::dropIfExists('agent_cashbacks'); }
};
