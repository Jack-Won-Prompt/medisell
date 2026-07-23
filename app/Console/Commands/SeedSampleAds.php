<?php

namespace App\Console\Commands;

use App\Models\Ad;
use Illuminate\Console\Command;

/**
 * 샘플 사이드 광고 등록 — 메디셀 카탈로그에 없는 외부/제휴 제품을 광고형으로.
 * 제품 광고용 SVG 이미지를 public/product/ads/ 에 생성하고 흰 카드에 표시.
 * 제목 기준 idempotent (재실행 안전). 각 환경에서 개별 실행(asset URL 자동).
 */
class SeedSampleAds extends Command
{
    protected $signature = 'ads:seed-samples {--fresh : 기존 샘플 삭제 후 재생성}';
    protected $description = '샘플 사이드 광고(외부 제품, 이미지 포함) 등록';

    /** key => [제목, 부제, 가격, 뱃지, 위치, accent, bg, icon(inner svg)] */
    private array $samples = [];

    public function __construct()
    {
        parent::__construct();
        $this->samples = [
            'fridge' => ['의료용 백신 냉장고 130L', '2~8℃ 정밀 온도 · 알람', 890000, '특가', 'both', '#0f8a8a', '#e7f5f4',
                '<rect x="118" y="40" width="84" height="142" rx="12" fill="#fff"/><line x1="118" y1="98" x2="202" y2="98"/><line x1="134" y1="60" x2="134" y2="82"/><line x1="134" y1="116" x2="134" y2="150"/>'],
            'aed' => ['자동 제세동기 AED', '병원·다중이용시설 필수', 1650000, '신제품', 'both', '#e0322d', '#fdecea',
                '<rect x="110" y="50" width="100" height="120" rx="14" fill="#fff"/><path d="M128 116 h18 l7 -18 l11 34 l8 -16 h20"/><circle cx="160" cy="150" r="4" fill="#e0322d"/>'],
            'bed' => ['전동 의료용 침대 3모터', '리모컨 높이·각도 조절', 1290000, 'AD', 'left', '#1857c4', '#e9f0fd',
                '<path d="M94 152 v-42 h132 v42"/><rect x="94" y="118" width="60" height="34" rx="9" fill="#fff"/><line x1="78" y1="152" x2="242" y2="152"/><line x1="88" y1="152" x2="88" y2="174"/><line x1="232" y1="152" x2="232" y2="174"/>'],
            'purifier' => ['병원용 대용량 공기청정기', 'H13 헤파 · 99.9% 여과', 430000, '특가', 'left', '#12805c', '#e7f3ee',
                '<rect x="126" y="44" width="68" height="134" rx="16" fill="#fff"/><circle cx="160" cy="122" r="20"/><path d="M148 66 q12 9 24 0"/><path d="M148 84 q12 9 24 0"/>'],
            'bp' => ['디지털 혈압계 (상완식)', '병원 인증 · 부정맥 감지', 78000, '신제품', 'right', '#2563eb', '#e9f0fd',
                '<rect x="108" y="70" width="72" height="82" rx="10" fill="#fff"/><rect x="122" y="86" width="44" height="26" rx="5"/><path d="M180 100 h28 a18 18 0 0 1 18 18 v8 a18 18 0 0 1 -18 18 h-12"/>'],
            'wheelchair' => ['전동 휠체어 경량형', '접이식 · 20km 주행', 990000, 'AD', 'right', '#6b4bb8', '#efeaf9',
                '<circle cx="138" cy="150" r="32"/><circle cx="138" cy="150" r="6" fill="#6b4bb8"/><path d="M114 126 h48 l-8 -42 h-26"/><path d="M128 84 h36"/><circle cx="198" cy="158" r="12"/>'],
        ];
    }

    public function handle(): int
    {
        $dir = public_path('product/ads');
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        if ($this->option('fresh')) {
            $n = Ad::whereIn('title', array_map(fn ($s) => $s[0], $this->samples))->delete();
            $this->line("기존 샘플 {$n}건 삭제");
        }

        $created = 0; $updated = 0; $i = 0;
        foreach ($this->samples as $key => [$title, $sub, $price, $badge, $pos, $accent, $bg, $icon]) {
            $i++;
            // 제품 광고 SVG 생성
            $svg = $this->svg($bg, $accent, $icon, $badge);
            file_put_contents($dir.'/'.$key.'.svg', $svg);
            $imageUrl = asset('product/ads/'.$key.'.svg');

            $ad = Ad::updateOrCreate(
                ['title' => $title],
                [
                    'subtitle'   => $sub,
                    'price'      => $price,
                    'badge'      => $badge,
                    'image'      => $imageUrl,
                    'bg_color'   => null,
                    'position'   => $pos,
                    'link'       => null,   // 실제 광고주 URL로 교체 가능
                    'sort_order' => $i,
                    'is_active'  => true,
                ]
            );
            $ad->wasRecentlyCreated ? $created++ : $updated++;
        }

        $this->info("샘플 광고 완료 · 신규 {$created} · 갱신 {$updated} · 이미지 ".count($this->samples)."장 · 활성 ".Ad::active()->count().'건');

        return 0;
    }

    /** 제품 광고용 SVG (연한 배경 + 제품 라인 일러스트) */
    private function svg(string $bg, string $accent, string $icon, string $badge): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 220" width="320" height="220">'
            .'<rect width="320" height="220" fill="'.$bg.'"/>'
            .'<g stroke="'.$accent.'" stroke-width="6" fill="none" stroke-linecap="round" stroke-linejoin="round">'.$icon.'</g>'
            .'</svg>';
    }
}
