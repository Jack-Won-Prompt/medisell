<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        // config('site')는 AppServiceProvider에서 DB설정과 병합된 최종값
        return view('admin.settings.edit', ['site' => config('site')]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:50'],
            'name_en'     => ['nullable', 'string', 'max:50'],
            'tagline'     => ['nullable', 'string', 'max:100'],
            'company'     => ['nullable', 'string', 'max:100'],
            'ceo'         => ['nullable', 'string', 'max:50'],
            'biz_no'      => ['nullable', 'string', 'max:50'],
            'mailorder'   => ['nullable', 'string', 'max:100'],
            'med_device'  => ['nullable', 'string', 'max:100'],
            'address'     => ['nullable', 'string', 'max:200'],
            'cs_tel'      => ['nullable', 'string', 'max:50'],
            'cs_hours'    => ['nullable', 'string', 'max:150'],
            'email'       => ['nullable', 'string', 'max:100'],
            'payment_pg'     => ['required', 'in:toss,portone'],
            'deal_mode'      => ['required', 'in:random,discount,best'],
            'free_ship_over' => ['required', 'integer', 'min:0'],
            'shipping_fee'   => ['required', 'integer', 'min:0'],
            'signup_point'   => ['required', 'integer', 'min:0'],
            'point_rate'     => ['required', 'integer', 'min:0', 'max:100'],
            'banks'              => ['array'],
            'banks.*.bank'       => ['nullable', 'string', 'max:50'],
            'banks.*.account'    => ['nullable', 'string', 'max:60'],
            'banks.*.holder'     => ['nullable', 'string', 'max:50'],
            'popular_keywords'   => ['nullable', 'string', 'max:500'],
        ]);

        // 빈 계좌행 제거
        $banks = collect($request->input('banks', []))
            ->filter(fn ($b) => ! empty($b['bank']) && ! empty($b['account']))
            ->map(fn ($b) => [
                'bank'    => $b['bank'],
                'account' => $b['account'],
                'holder'  => $b['holder'] ?? '',
            ])->values()->all();

        // 인기검색어 콤마 분리
        $keywords = collect(explode(',', (string) $request->input('popular_keywords', '')))
            ->map(fn ($k) => trim($k))->filter()->values()->all();

        $site = array_merge(config('site'), array_diff_key($data, array_flip(['banks', 'popular_keywords'])), [
            'banks'            => $banks,
            'popular_keywords' => $keywords,
        ]);

        Setting::put('site', $site);

        return back()->with('ok', '사이트 설정이 저장되었습니다.');
    }
}
