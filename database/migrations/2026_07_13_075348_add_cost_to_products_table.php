<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 운영 DB 덤프에 cost가 이미 있을 수 있으므로 멱등 처리
        if (Schema::hasColumn('products', 'cost')) {
            return;
        }
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedInteger('cost')->nullable()->after('price')->comment('매입단가(참고용)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('products', 'cost')) {
            return;
        }
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('cost');
        });
    }
};
