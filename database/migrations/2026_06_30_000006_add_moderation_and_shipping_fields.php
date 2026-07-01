<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->boolean('is_hidden')->default(false)->after('body'); // 관리자 숨김
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('courier')->nullable()->after('va_due_at');      // 택배사
            $table->string('tracking_no')->nullable()->after('courier');    // 송장번호
            $table->timestamp('shipped_at')->nullable()->after('tracking_no');
            $table->timestamp('cancelled_at')->nullable()->after('shipped_at');
            $table->string('cancel_reason')->nullable()->after('cancelled_at');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', fn (Blueprint $t) => $t->dropColumn('is_hidden'));
        Schema::table('orders', fn (Blueprint $t) => $t->dropColumn(['courier', 'tracking_no', 'shipped_at', 'cancelled_at', 'cancel_reason']));
    }
};
