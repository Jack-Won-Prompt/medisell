<?php

namespace App\Console\Commands;

use App\Models\Ad;
use Illuminate\Console\Command;

/**
 * 샘플 사이드 광고 등록 — 메디셀 카탈로그에 없는 외부/제휴 제품 20종을 광고형으로.
 * 제품별 SVG 일러스트를 public/product/ads/ 에 생성하고 흰 카드에 표시.
 * 제목 기준 idempotent. 노출은 partials.ad-rails 에서 페이지마다 랜덤·중복 없이.
 */
class SeedSampleAds extends Command
{
    protected $signature = 'ads:seed-samples {--fresh : 기존 샘플 삭제 후 재생성}';
    protected $description = '샘플 사이드 광고(외부 제품 20종, 이미지 포함) 등록';

    /** 그라디언트 팔레트 [밝은색, 어두운색] — 광고 크리에이티브 배경용 */
    private array $palette = [
        ['#14b8b8', '#0a5c5c'], ['#f04438', '#a01008'], ['#3b82f6', '#1e40af'], ['#16a34a', '#0a5c30'],
        ['#4f6ef7', '#1e3a8a'], ['#8b5cf6', '#4c1d95'], ['#f59e0b', '#b45309'], ['#ec4899', '#9d174d'],
        ['#06b6d4', '#0e7490'], ['#64748b', '#334155'],
    ];

    /** key => [제목, 부제, 가격, 뱃지, icon(inner svg)] */
    private function samples(): array
    {
        return [
            'fridge'      => ['의료용 백신 냉장고 130L', '2~8℃ 정밀 온도 · 알람', 890000, '특가', '<rect x="118" y="40" width="84" height="142" rx="12" fill="#fff"/><line x1="118" y1="98" x2="202" y2="98"/><line x1="134" y1="60" x2="134" y2="82"/><line x1="134" y1="116" x2="134" y2="150"/>'],
            'aed'         => ['자동 제세동기 AED', '병원·다중이용시설 필수', 1650000, '신제품', '<rect x="110" y="50" width="100" height="120" rx="14" fill="#fff"/><path d="M128 116 h18 l7 -18 l11 34 l8 -16 h20"/>'],
            'bed'         => ['전동 의료용 침대 3모터', '리모컨 높이·각도 조절', 1290000, 'AD', '<path d="M94 152 v-42 h132 v42"/><rect x="94" y="118" width="60" height="34" rx="9" fill="#fff"/><line x1="78" y1="152" x2="242" y2="152"/><line x1="88" y1="152" x2="88" y2="174"/><line x1="232" y1="152" x2="232" y2="174"/>'],
            'purifier'    => ['병원용 대용량 공기청정기', 'H13 헤파 · 99.9% 여과', 430000, '특가', '<rect x="126" y="44" width="68" height="134" rx="16" fill="#fff"/><circle cx="160" cy="122" r="20"/><path d="M148 66 q12 9 24 0"/><path d="M148 84 q12 9 24 0"/>'],
            'bp'          => ['디지털 혈압계 (상완식)', '병원 인증 · 부정맥 감지', 78000, '신제품', '<rect x="108" y="70" width="72" height="82" rx="10" fill="#fff"/><rect x="122" y="86" width="44" height="26" rx="5"/><path d="M180 100 h28 a18 18 0 0 1 18 18 v8 a18 18 0 0 1 -18 18 h-12"/>'],
            'wheelchair'  => ['전동 휠체어 경량형', '접이식 · 20km 주행', 990000, 'AD', '<circle cx="138" cy="150" r="32"/><circle cx="138" cy="150" r="7"/><path d="M114 126 h48 l-8 -42 h-26"/><path d="M128 84 h36"/><circle cx="198" cy="158" r="12"/>'],
            'oxygen'      => ['의료용 산소발생기 5L', '고농도 93% · 저소음', 620000, '인기', '<rect x="130" y="44" width="60" height="136" rx="16" fill="#fff"/><circle cx="160" cy="80" r="14"/><line x1="160" y1="80" x2="168" y2="72"/><path d="M150 120 h20 M150 138 h20"/>'],
            'nebulizer'   => ['휴대용 네블라이저 흡입기', '저소음 · 충전식', 55000, '베스트', '<rect x="122" y="118" width="76" height="52" rx="10" fill="#fff"/><path d="M150 118 v-16 a16 16 0 0 1 32 0 v16"/><circle cx="166" cy="96" r="9" fill="#fff"/><line x1="198" y1="150" x2="222" y2="140"/>'],
            'thermometer' => ['비접촉 적외선 체온계', '1초 측정 · 병원용', 39000, '특가', '<rect x="118" y="86" width="60" height="40" rx="8" fill="#fff"/><path d="M138 126 v26 h26"/><rect x="178" y="92" width="16" height="28" rx="4"/><circle cx="128" cy="106" r="5"/>'],
            'uv'          => ['병원용 UV 살균 소독기', '99.9% 살균 · 대용량', 180000, 'AD', '<rect x="120" y="58" width="80" height="104" rx="12" fill="#fff"/><line x1="160" y1="78" x2="160" y2="142"/><path d="M144 90 q16 12 0 24 q-16 12 0 24"/><path d="M176 90 q-16 12 0 24 q16 12 0 24"/>'],
            'scale'       => ['병원용 체성분 분석기', '체지방·근육량 측정', 720000, '신제품', '<rect x="112" y="152" width="96" height="14" rx="4" fill="#fff"/><rect x="150" y="72" width="20" height="80" fill="#fff"/><rect x="128" y="50" width="64" height="26" rx="5" fill="#fff"/>'],
            'cart'        => ['환자 이송용 스트레처 카트', '유압 승강 · 안전벨트', 1450000, 'AD', '<rect x="108" y="96" width="104" height="16" rx="4" fill="#fff"/><line x1="118" y1="112" x2="118" y2="150"/><line x1="202" y1="112" x2="202" y2="150"/><circle cx="126" cy="160" r="10"/><circle cx="194" cy="160" r="10"/><path d="M212 96 v-24 h12"/>'],
            'light'       => ['LED 진료용 무영등', '색온도 조절 · 그림자 최소', 540000, '인기', '<ellipse cx="160" cy="76" rx="46" ry="24" fill="#fff"/><circle cx="146" cy="76" r="7"/><circle cx="174" cy="76" r="7"/><path d="M160 100 v34 M160 134 h-30 v22"/>'],
            'waste'       => ['의료폐기물 전용 용기 20L', '밀폐·인증 규격', 12000, '특가', '<path d="M126 80 h68 l-8 94 h-52 z" fill="#fff"/><line x1="118" y1="80" x2="202" y2="80"/><path d="M144 66 h32 v14 h-32 z"/><path d="M150 108 l20 30 M170 108 l-20 30"/>'],
            'water'       => ['병원용 직수형 정수기', '냉·온·정수 · 필터', 350000, '베스트', '<rect x="128" y="44" width="64" height="136" rx="14" fill="#fff"/><path d="M160 152 q11 -15 0 -30 q-11 15 0 30" fill="#fff"/><rect x="192" y="88" width="20" height="10" rx="3" fill="#fff"/>'],
            'heatpad'     => ['물리치료 온열 찜질패드', '자동 온도 · 넓은 면적', 46000, '인기', '<rect x="112" y="82" width="96" height="62" rx="18" fill="#fff"/><path d="M130 112 q11 -12 22 0 q11 12 22 0 q8 -9 14 0"/>'],
            'oximeter'    => ['맥박 산소포화도 측정기', 'SpO2·맥박 · 초정밀', 34000, '베스트', '<path d="M120 96 h56 a16 16 0 0 1 0 46 h-40 a20 20 0 0 1 -16 -23 z" fill="#fff"/><path d="M132 120 h10 l6 -12 l8 24 l6 -12 h14"/>'],
            'dispenser'   => ['병원 대기실 냉온수기', '핫·콜드 · 대용량', 290000, 'AD', '<rect x="124" y="70" width="72" height="110" rx="12" fill="#fff"/><path d="M150 70 v-16 h20 v16" fill="#fff"/><rect x="140" y="104" width="12" height="12" rx="2"/><rect x="168" y="104" width="12" height="12" rx="2"/>'],
            'suction'     => ['이동형 의료용 석션기', '강력 흡인 · 저소음', 380000, '신제품', '<rect x="128" y="96" width="52" height="80" rx="10" fill="#fff"/><line x1="128" y1="126" x2="180" y2="126"/><path d="M180 110 q28 -6 30 22"/><rect x="140" y="72" width="28" height="24" rx="5" fill="#fff"/>'],
            'ultrasonic'  => ['의료기구 초음파 세척기', '정밀 세척 · 타이머', 260000, '특가', '<rect x="112" y="96" width="96" height="74" rx="10" fill="#fff"/><path d="M132 134 q14 -14 0 -30 M150 140 q20 -22 0 -44 M168 134 q14 -14 0 -30"/>'],
        ];
    }

    public function handle(): int
    {
        $dir = public_path('product/ads');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $samples = $this->samples();
        if ($this->option('fresh')) {
            $n = Ad::whereIn('title', array_map(fn ($s) => $s[0], $samples))->delete();
            $this->line("기존 샘플 {$n}건 삭제");
        }

        $created = 0; $updated = 0; $i = 0;
        foreach ($samples as $key => [$title, $sub, $price, $badge, $icon]) {
            [$c1, $c2] = $this->palette[$i % count($this->palette)];
            file_put_contents($dir.'/'.$key.'.svg', $this->svg($c1, $c2, $icon, $badge));
            $i++;

            $ad = Ad::updateOrCreate(
                ['title' => $title],
                [
                    'subtitle'   => $sub,
                    'price'      => $price,
                    'badge'      => $badge,
                    'image'      => asset('product/ads/'.$key.'.svg'),
                    'bg_color'   => null,
                    'position'   => 'both',
                    'link'       => null,   // 실제 광고주 URL로 교체 가능
                    'sort_order' => $i,
                    'is_active'  => true,
                ]
            );
            $ad->wasRecentlyCreated ? $created++ : $updated++;
        }

        $this->info("샘플 광고 완료 · 신규 {$created} · 갱신 {$updated} · 이미지 ".count($samples)."장 · 활성 ".Ad::active()->count().'건');

        return 0;
    }

    /** 광고 크리에이티브 SVG — 대각 그라디언트 배경 + 스포트라이트 + 흰색 제품 실루엣 */
    private function svg(string $c1, string $c2, string $icon, string $badge): string
    {
        $gid = 'g'.substr(md5($c1.$c2), 0, 6);
        // 흰 배경 채움 → 반투명(그라디언트 위 글래스 느낌, 흰 라인 디테일 유지)
        $icon = str_replace('fill="#fff"', 'fill="rgba(255,255,255,0.16)"', $icon);

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 220" width="320" height="220">'
            .'<defs>'
            .'<linearGradient id="'.$gid.'" x1="0" y1="0" x2="1" y2="1">'
            .'<stop offset="0" stop-color="'.$c1.'"/><stop offset="1" stop-color="'.$c2.'"/></linearGradient>'
            .'<radialGradient id="s'.$gid.'" cx="0.5" cy="0.42" r="0.6">'
            .'<stop offset="0" stop-color="#ffffff" stop-opacity="0.22"/><stop offset="1" stop-color="#ffffff" stop-opacity="0"/></radialGradient>'
            .'</defs>'
            .'<rect width="320" height="220" fill="url(#'.$gid.')"/>'
            .'<rect width="320" height="220" fill="url(#s'.$gid.')"/>'
            .'<ellipse cx="160" cy="188" rx="86" ry="12" fill="#000000" opacity="0.10"/>'
            .'<g stroke="#ffffff" stroke-width="6.5" fill="none" stroke-linecap="round" stroke-linejoin="round">'.$icon.'</g>'
            .'</svg>';
    }
}
