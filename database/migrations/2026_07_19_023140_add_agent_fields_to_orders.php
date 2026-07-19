<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasColumn('orders', 'agent_id')) return;
        Schema::table('orders', function (Blueprint $t) {
            $t->foreignId('agent_id')->nullable()->after('user_id')->constrained('users')->nullOnDelete();
            $t->string('buyer_hospital', 100)->nullable()->after('memo');
            $t->string('buyer_name', 50)->nullable()->after('buyer_hospital');
            $t->string('buyer_phone', 30)->nullable()->after('buyer_name');
            $t->unsignedInteger('cashback_amount')->default(0)->after('buyer_phone');
        });
    }
    public function down(): void {
        if (! Schema::hasColumn('orders', 'agent_id')) return;
        Schema::table('orders', function (Blueprint $t) {
            $t->dropConstrainedForeignId('agent_id');
            $t->dropColumn(['buyer_hospital', 'buyer_name', 'buyer_phone', 'cashback_amount']);
        });
    }
};
