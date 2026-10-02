<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * 고객센터 전화번호 변경 → 070-7537-3329.
 *
 * 화면에 나오는 번호는 config/site.php 가 아니라 settings 테이블의 'site' 값(관리자 사이트설정)이
 * 덮어쓴다. config 만 바꾸면 이미 저장된 사이트설정이 있는 서버에서는 옛 번호가 계속 보이므로
 * 저장된 값도 함께 바꾼다. 사이트설정 행이 없으면 config 기본값이 쓰이므로 할 일이 없다.
 */
return new class extends Migration
{
    private const TEL = '070-7537-3329';

    public function up(): void
    {
        $row = DB::table('settings')->where('key', 'site')->first();
        if (! $row) {
            return;
        }
        $site = json_decode($row->value, true) ?: [];
        $site['cs_tel'] = self::TEL;
        DB::table('settings')->where('id', $row->id)->update([
            'value'      => json_encode($site),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // 이전 번호는 서버마다 달랐으므로 되돌리지 않는다 (관리자 사이트설정에서 수정)
    }
};
