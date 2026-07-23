<?php

namespace App\Console\Commands;

use App\Models\Ad;
use Illuminate\Console\Command;

/**
 * 샘플 사이드 광고 등록 — 메디셀 카탈로그에 없는 외부/제휴 제품을 광고형으로.
 * 제목 기준 idempotent (재실행 안전). 이미지 없이 그라디언트 카드로 표시.
 */
class SeedSampleAds extends Command
{
    protected $signature = 'ads:seed-samples {--fresh : 기존 샘플 삭제 후 재생성}';
    protected $description = '샘플 사이드 광고(외부 제품) 등록';

    /** [제목, 부제, 가격, 뱃지, 배경그라디언트, 위치] */
    private array $samples = [
        ['의료용 백신 냉장고 130L', '2~8℃ 정밀 온도 · 알람', 890000, '특가', 'linear-gradient(150deg,#0f8a8a,#06256b)', 'both'],
        ['자동 제세동기 AED', '병원·다중이용시설 필수', 1650000, '신제품', 'linear-gradient(150deg,#e0322d,#7a1512)', 'both'],
        ['전동 의료용 침대 3모터', '리모컨 높이·각도 조절', 1290000, 'AD', 'linear-gradient(150deg,#1857c4,#06256b)', 'left'],
        ['병원용 대용량 공기청정기', 'H13 헤파 · 99.9% 여과', 430000, '특가', 'linear-gradient(150deg,#12805c,#053d2a)', 'left'],
        ['디지털 혈압계 (상완식)', '병원 인증 · 부정맥 감지', 78000, '신제품', 'linear-gradient(150deg,#2563eb,#0b3d91)', 'right'],
        ['전동 휠체어 경량형', '접이식 · 20km 주행', 990000, 'AD', 'linear-gradient(150deg,#6b4bb8,#2d1a5e)', 'right'],
    ];

    public function handle(): int
    {
        if ($this->option('fresh')) {
            $titles = array_column($this->samples, 0);
            $n = Ad::whereIn('title', $titles)->delete();
            $this->line("기존 샘플 {$n}건 삭제");
        }

        $created = 0; $updated = 0;
        foreach ($this->samples as $i => [$title, $sub, $price, $badge, $bg, $pos]) {
            $ad = Ad::updateOrCreate(
                ['title' => $title],
                [
                    'subtitle'   => $sub,
                    'price'      => $price,
                    'badge'      => $badge,
                    'bg_color'   => $bg,
                    'position'   => $pos,
                    'link'       => null,   // 실제 광고주 URL로 교체 가능
                    'sort_order' => $i + 1,
                    'is_active'  => true,
                ]
            );
            $ad->wasRecentlyCreated ? $created++ : $updated++;
        }

        $this->info("샘플 광고 완료 · 신규 {$created} · 갱신 {$updated} · 활성 ".Ad::active()->count().'건');

        return 0;
    }
}
