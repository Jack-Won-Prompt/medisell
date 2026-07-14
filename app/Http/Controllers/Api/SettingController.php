<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /** 앱 부트스트랩용 사이트 설정 (회사정보·배송비·적립·계좌·인기검색어·PG) */
    public function index(Request $request)
    {
        $site = config('site');

        return response()->json([
            'company' => [
                'name'     => $site['name'] ?? '메디셀',
                'name_en'  => $site['name_en'] ?? 'MEDISELL',
                'tagline'  => $site['tagline'] ?? null,
                'ceo'      => $site['ceo'] ?? null,
                'biz_no'   => $site['biz_no'] ?? null,
                'address'  => $site['address'] ?? null,
                'cs_tel'   => $site['cs_tel'] ?? null,
                'cs_hours' => $site['cs_hours'] ?? null,
                'email'    => $site['email'] ?? null,
            ],
            'policy' => [
                'free_ship_over' => (int) ($site['free_ship_over'] ?? 0),
                'shipping_fee'   => (int) ($site['shipping_fee'] ?? 0),
                'signup_point'   => (int) ($site['signup_point'] ?? 0),
                'point_rate'     => (float) ($site['point_rate'] ?? 0),
            ],
            'banks'   => $site['banks'] ?? [],
            'popular_keywords' => $site['popular_keywords'] ?? [],
            'payment_pg' => $site['payment_pg'] ?? 'toss',
        ]);
    }
}
