<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 콜로플라스트 병·의원가를 블루팜과 같은 규칙으로: 정가 × 0.98, 원 단위 올림.
 * (기존: 임포트 시 정가 × 0.9 를 10원 단위 내림)
 * price 는 정수라 price * 0.98 은 MySQL DECIMAL 로 정확히 계산된다.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('products')
            ->where('maker', '콜로플라스트')
            ->where('price', '>', 0)
            ->update(['member_price' => DB::raw('CEIL(price * 0.98)')]);
    }

    public function down(): void
    {
        // 이전 값(정가×0.9 내림, 일부 수동 수정 포함)은 복원하지 않는다
    }
};
