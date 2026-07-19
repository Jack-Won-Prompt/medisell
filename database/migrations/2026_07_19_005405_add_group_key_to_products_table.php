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
        if (Schema::hasColumn('products', 'group_key')) {
            return;
        }
        Schema::table('products', function (Blueprint $table) {
            $table->string('group_key', 191)->nullable()->index()->after('code')->comment('규격/사이즈 변형 묶음 키');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('products', 'group_key')) {
            return;
        }
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('group_key');
        });
    }
};
