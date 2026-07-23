<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasColumn('users', 'account_id')) return;
        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('account_id')->nullable()->after('grade')->constrained('accounts')->nullOnDelete();
        });
    }
    public function down(): void {
        if (! Schema::hasColumn('users', 'account_id')) return;
        Schema::table('users', fn (Blueprint $t) => $t->dropConstrainedForeignId('account_id'));
    }
};
