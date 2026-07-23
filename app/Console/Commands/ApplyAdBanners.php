<?php

namespace App\Console\Commands;

use App\Models\Ad;
use Illuminate\Console\Command;

/**
 * medisell_ads_20 실사 광고 배너(16:9)를 웹용으로 리사이즈해 사이드 광고에 반영.
 * - 원본(medisell_ads_20/*.png)이 있으면 public/product/ads/{key}.jpg 로 리사이즈
 * - 원본이 없어도(운영) 커밋된 jpg를 참조해 광고 레코드만 upsert
 * - 기존 SVG 샘플 광고는 제거
 */
class ApplyAdBanners extends Command
{
    protected $signature = 'ads:apply-banners';
    protected $description = 'medisell_ads_20 실사 광고 배너를 리사이즈·등록';

    /** [원본파일, key, 제목, 부제, 링크route] */
    private function map(): array
    {
        return [
            ['ad_01_syringe',        'b-syringe',      '주사기 대량특가',   '사업자 전용가 별도 적용', 'catalog.index'],
            ['ad_02_iv_catheter',    'b-iv-catheter',  'IV 카테터 특가',    '병원 필수 소모품',        'catalog.index'],
            ['ad_03_dressing',       'b-dressing',     '드레싱 용품 기획전', '상처 케어 전품목',        'catalog.index'],
            ['ad_04_surgical_glove', 'b-glove',        '수술용 장갑 특가',   '멸균 · 라텍스/니트릴',    'catalog.index'],
            ['ad_05_bp_monitor',     'b-bp',           '디지털 혈압계',      '병원 인증 정밀 측정',     'catalog.index'],
            ['ad_06_air_purifier',   'b-purifier',     '병원용 공기청정기',  'H13 헤파 · 대용량',       'catalog.index'],
            ['ad_07_water_dispenser','b-water',        '병원 냉온수기',      '대기실 필수',             'catalog.index'],
            ['ad_08_body_analyzer',  'b-analyzer',     '체성분 분석기',      '체지방 · 근육량 측정',    'catalog.index'],
            ['ad_09_vaccine_fridge', 'b-fridge',       '백신 냉장고',        '2~8℃ 정밀 온도',          'catalog.index'],
            ['ad_10_oxygen',         'b-oxygen',       '의료용 산소발생기',  '고농도 · 저소음',         'catalog.index'],
            ['ad_11_bandage',        'b-bandage',      '붕대 · 밴드 기획전',  '탄력 · 멸균 전품목',      'catalog.index'],
            ['ad_12_suture',         'b-suture',       '봉합사 특가',        '각종 규격 구비',          'catalog.index'],
            ['ad_13_cotton_swab',    'b-cotton',       '탈지면 · 스왑',      '위생 소모품 대량',        'catalog.index'],
            ['ad_14_sterilization',  'b-sterile',      '멸균 · 소독 용품',   '감염관리 전품목',         'catalog.index'],
            ['ad_15_handwash',       'b-handwash',     '손소독 · 핸드워시',  '병원 위생 필수',          'catalog.index'],
            ['ad_16_wrist_support',  'b-wrist',        '손목 보호대',        '정형 · 압박 용품',        'catalog.index'],
            ['ad_17_catheter_special','b-catheter',    '특수 카테터',        '친수성 코팅',             'catalog.index'],
            ['ad_18_signup_event',   'b-signup',       '신규가입 이벤트',    '가입 즉시 적립금',        'guide.event'],
            ['ad_19_bulk_order',     'b-bulk',         '대량구매 견적',      '빠른 견적 상담',          'community.inquiry'],
            ['ad_20_brand_main',     'b-brand',        '메디셀 브랜드관',    '병의원 소모품 한 번에',   'home'],
        ];
    }

    public function handle(): int
    {
        $srcDir = base_path('medisell_ads_20');
        $destDir = public_path('product/ads');
        if (! is_dir($destDir)) {
            @mkdir($destDir, 0775, true);
        }

        // 기존 SVG 샘플 광고 제거
        $removed = Ad::where('image', 'like', '%/product/ads/%.svg')->delete();
        if ($removed) {
            $this->line("기존 SVG 샘플 광고 {$removed}건 제거");
        }

        $resized = 0; $missing = 0; $i = 0;
        foreach ($this->map() as [$src, $key, $title, $sub, $routeName]) {
            $i++;
            $srcPath = $srcDir.'/'.$src.'.png';
            $destPath = $destDir.'/'.$key.'.jpg';

            // 원본 있으면 리사이즈 (720x405, JPEG)
            if (is_file($srcPath)) {
                if ($this->resize($srcPath, $destPath, 720, 405)) {
                    $resized++;
                }
            } elseif (! is_file($destPath)) {
                $missing++;
                $this->warn("이미지 없음: {$key} (원본·리사이즈본 모두 없음)");
            }

            Ad::updateOrCreate(
                ['title' => $title],
                [
                    'subtitle'   => $sub,
                    'image'      => asset('product/ads/'.$key.'.jpg'),
                    'bg_color'   => null,
                    'price'      => null,          // 배너에 문구 포함 → 카드엔 이미지만
                    'badge'      => null,
                    'link'       => \Illuminate\Support\Facades\Route::has($routeName) ? route($routeName) : url('/'),
                    'position'   => 'both',
                    'sort_order' => $i,
                    'is_active'  => true,
                ]
            );
        }

        $this->info("배너 광고 반영 완료 · 리사이즈 {$resized} · 등록 20 · 누락 {$missing} · 활성 ".Ad::active()->count().'건');

        return 0;
    }

    /** GD로 리사이즈(비율 유지, 지정 크기로 커버) + JPEG 저장 */
    private function resize(string $src, string $dest, int $w, int $h): bool
    {
        $info = @getimagesize($src);
        if (! $info) {
            return false;
        }
        $img = @imagecreatefrompng($src);
        if (! $img) {
            return false;
        }
        [$ow, $oh] = $info;
        // 커버 크롭 비율 계산
        $scale = max($w / $ow, $h / $oh);
        $nw = (int) round($ow * $scale);
        $nh = (int) round($oh * $scale);
        $dx = (int) round(($w - $nw) / 2);
        $dy = (int) round(($h - $nh) / 2);

        $canvas = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopyresampled($canvas, $img, $dx, $dy, 0, 0, $nw, $nh, $ow, $oh);
        imagejpeg($canvas, $dest, 84);
        imagedestroy($img);
        imagedestroy($canvas);

        return true;
    }
}
