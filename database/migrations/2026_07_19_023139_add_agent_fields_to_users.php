<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasColumn('users', 'is_agent')) return;
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('is_agent')->default(false)->after('is_admin')->comment('구매 대행자 여부');
            $t->decimal('cashback_rate', 5, 2)->default(0)->after('is_agent')->comment('캐쉬백 비율(%)');
        });
    }
    public function down(): void {
        if (! Schema::hasColumn('users', 'is_agent')) return;
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['is_agent', 'cashback_rate']));
    }
};
