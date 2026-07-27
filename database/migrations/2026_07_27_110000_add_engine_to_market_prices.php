<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if (Schema::hasColumn('market_prices', 'engine')) return;
        Schema::table('market_prices', function (Blueprint $t) {
            // 수집 엔진: simulate(모의) | serp(구글쇼핑) | partners(쿠팡 파트너스)
            // 저장 시점 기준으로 남겨, 키를 나중에 넣어도 옛 모의데이터가 실데이터로 오인되지 않게 한다.
            $t->string('engine', 20)->default('simulate')->after('channel');
        });
    }
    public function down(): void {
        if (! Schema::hasColumn('market_prices', 'engine')) return;
        Schema::table('market_prices', function (Blueprint $t) {
            $t->dropColumn('engine');
        });
    }
};
