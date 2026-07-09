<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Faq;
use App\Models\HospitalPrice;
use App\Models\Inquiry;
use App\Models\Notice;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->users();
        // 카테고리·상품·브랜드·썸네일은 colscare(cols) 원본에서 가져온다
        $this->call(ColsImportSeeder::class);
        $this->hospitalPrices();   // 병원별 전용가 샘플
        $this->banners();
        $this->notices();
        $this->faqs();
        $this->inquiries();
    }

    /** 데모용: 병원 회원(clinic@test.com)에 일부 제품 전용가 매핑 */
    private function hospitalPrices(): void
    {
        $clinic = User::where('email', 'clinic@test.com')->first();
        if (! $clinic) {
            return;
        }
        // 정가의 78~85% 수준의 병원별 계약가 (기본 병원가 90%보다 더 저렴 → 전용가 우선 적용 확인)
        foreach (Product::orderBy('id')->take(12)->get() as $idx => $p) {
            $rate = [0.78, 0.80, 0.82, 0.85][$idx % 4];
            HospitalPrice::create([
                'user_id'    => $clinic->id,
                'product_id' => $p->id,
                'price'      => (int) (floor($p->price * $rate / 10) * 10),
            ]);
        }
    }

    private function users(): void
    {
        User::create([
            'name' => '관리자', 'email' => 'admin@medisell.co.kr',
            'password' => Hash::make('medisell!2026'),
            'is_admin' => true, 'member_type' => 'general',
        ]);

        // 승인된 사업자(병의원) 샘플 — 사업자 회원가 적용 대상
        User::create([
            'name' => '김원장', 'email' => 'clinic@test.com',
            'password' => Hash::make('test1234'),
            'member_type' => 'business', 'biz_status' => 'approved', 'grade' => 'gold',
            'company_name' => '튼튼정형외과의원', 'biz_no' => '111-22-33333',
            'biz_type' => '의원', 'phone' => '02-1234-5678', 'point' => 3000,
            'postcode' => '06000', 'address1' => '서울 강남구 테헤란로 123', 'address2' => '2층',
        ]);

        // 일반 회원 샘플
        User::create([
            'name' => '홍길동', 'email' => 'user@test.com',
            'password' => Hash::make('test1234'),
            'member_type' => 'general', 'phone' => '010-1111-2222', 'point' => 3000,
        ]);
    }

    private function banners(): void
    {
        $main = [
            ['title' => '병의원 의료소모품, 메디셀에서 한 번에', 'subtitle' => '사업자 회원 전용 특별가 + 신규가입 3,000원 적립', 'bg_color' => '#0b3d91'],
            ['title' => '최저가 보상제 시행 중', 'subtitle' => '더 저렴한 곳이 있다면 알려주세요. 차액을 보상합니다.', 'bg_color' => '#127a8a'],
            ['title' => '대량구매 견적 문의 환영', 'subtitle' => '병원·의원 대량 납품 전용 견적을 받아보세요', 'bg_color' => '#1f6f3f'],
        ];
        foreach ($main as $i => $b) {
            Banner::create($b + ['position' => 'main', 'sort_order' => $i, 'link' => '#']);
        }
        $sub = [
            ['title' => '오늘의 특가', 'subtitle' => '한정수량 기획전', 'bg_color' => '#c0392b'],
            ['title' => '신상품 모음', 'subtitle' => '새로 입고된 의료소모품', 'bg_color' => '#2c3e7a'],
            // {point} = 사이트설정 신규가입 적립금으로 화면에서 자동 치환
            ['title' => '가입 즉시 {point}원 적립', 'subtitle' => '신규회원 혜택',
                'bg_color' => 'linear-gradient(135deg,#0f8a8a,#0b3d91)', 'link' => url('/register')],
        ];
        foreach ($sub as $i => $b) {
            Banner::create($b + ['position' => 'sub', 'sort_order' => $i, 'link' => '#']);
        }
    }

    private function notices(): void
    {
        $items = [
            ['설 연휴 배송 안내', '설 연휴 기간 주문은 연휴 종료 후 순차 배송됩니다.', true],
            ['무통장 입금 안내', '입금자명과 주문자명이 다를 경우 1:1 문의로 알려주세요.', true],
            ['사업자 회원 승인 절차 안내', '사업자등록증 확인 후 영업일 기준 1일 내 승인됩니다.', false],
            ['배송비 정책 변경 안내', '5만원 이상 구매 시 무료배송으로 변경되었습니다.', false],
        ];
        foreach ($items as $i => [$t, $b, $pin]) {
            Notice::create([
                'title' => $t, 'body' => $b, 'is_pinned' => $pin,
                'views' => mt_rand(10, 500), 'published_at' => now()->subDays($i),
            ]);
        }
    }

    private function faqs(): void
    {
        $items = [
            ['회원', '사업자 회원은 어떻게 가입하나요?', '회원가입 시 사업자 회원을 선택하고 사업자등록번호를 입력하면, 관리자 확인 후 승인됩니다. 승인 후 사업자 전용가가 적용됩니다.'],
            ['주문/결제', '결제는 어떤 방법이 있나요?', '현재 무통장입금을 지원합니다. 주문 후 안내된 계좌로 입금하시면 확인 후 상품을 준비합니다.'],
            ['배송', '배송비는 얼마인가요?', '기본 배송비는 3,000원이며, 5만원 이상 구매 시 무료배송입니다.'],
            ['배송', '배송은 얼마나 걸리나요?', '입금 확인 후 영업일 기준 1~3일 내 출고됩니다.'],
            ['취소/환불', '주문 취소는 어떻게 하나요?', '상품 준비 전(입금대기/입금확인) 단계에서는 마이페이지 또는 1:1문의로 취소 요청이 가능합니다.'],
        ];
        foreach ($items as $i => [$c, $q, $a]) {
            Faq::create(['category' => $c, 'question' => $q, 'answer' => $a, 'sort_order' => $i]);
        }
    }

    private function inquiries(): void
    {
        Inquiry::create([
            'type' => 'quote', 'name' => '이실장', 'phone' => '02-555-1234',
            'email' => 'buy@hospital.com', 'subject' => '니트릴 장갑 대량 견적 요청',
            'body' => '니트릴 장갑 M 사이즈 월 100박스 정기 납품 견적 부탁드립니다.',
            'status' => 'pending',
        ]);
        Inquiry::create([
            'type' => 'qna', 'name' => '홍길동', 'subject' => '무통장 입금 확인 문의',
            'body' => '입금했는데 아직 입금확인이 안 됩니다.', 'status' => 'answered',
            'answer' => '확인되어 입금확인 처리되었습니다. 감사합니다.', 'answered_at' => now(),
        ]);
    }
}
