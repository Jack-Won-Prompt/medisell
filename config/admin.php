<?php

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Faq;
use App\Models\Notice;
use App\Models\Product;

/*
|--------------------------------------------------------------------------
| 관리자 CRUD 리소스 정의 (설정 기반 제네릭 어드민)
|--------------------------------------------------------------------------
| field type: text | textarea | number | checkbox | date | datetime | select
| 주문/회원/문의는 전용 컨트롤러가 별도 처리합니다.
*/

return [
    'categories' => [
        'label' => '카테고리', 'model' => Category::class, 'icon' => 'box', 'group' => '상품관리',
        'order' => ['sort_order', 'asc'], 'with' => ['parent'], 'tree' => true,
        'columns' => ['name' => '이름', 'parent.name' => '상위', 'slug' => '슬러그', 'sort_order' => '순서', 'is_active' => '활성'],
        'fields' => [
            ['name' => 'parent_id', 'label' => '상위 카테고리(대분류면 비움)', 'type' => 'select',
                'options_from' => ['model' => Category::class, 'key' => 'id', 'label' => 'name', 'order' => 'sort_order']],
            ['name' => 'name', 'label' => '카테고리명', 'type' => 'text', 'required' => true],
            ['name' => 'slug', 'label' => '슬러그(영문 URL)', 'type' => 'text', 'required' => true],
            ['name' => 'tagline', 'label' => '한 줄 설명', 'type' => 'text'],
            ['name' => 'icon', 'label' => '아이콘 키', 'type' => 'text', 'hint' => '예: syringe, glove, bandage, drop, ivbag, catheter, needle, virus, shield, scan, cross, box'],
            ['name' => 'sort_order', 'label' => '정렬 순서', 'type' => 'number'],
            ['name' => 'is_active', 'label' => '노출', 'type' => 'checkbox'],
        ],
        'defaults' => ['is_active' => true],
    ],

    'brands' => [
        'label' => '브랜드', 'model' => Brand::class, 'icon' => 'handshake', 'group' => '상품관리',
        'order' => ['sort_order', 'asc'],
        'columns' => ['name' => '브랜드명', 'slug' => '슬러그', 'sort_order' => '순서', 'is_active' => '활성'],
        'fields' => [
            ['name' => 'name', 'label' => '브랜드명', 'type' => 'text', 'required' => true],
            ['name' => 'slug', 'label' => '슬러그', 'type' => 'text', 'required' => true],
            ['name' => 'logo', 'label' => '브랜드 로고', 'type' => 'image'],
            ['name' => 'description', 'label' => '설명', 'type' => 'textarea', 'rows' => 3],
            ['name' => 'sort_order', 'label' => '정렬 순서', 'type' => 'number'],
            ['name' => 'is_active', 'label' => '노출', 'type' => 'checkbox'],
        ],
        'defaults' => ['is_active' => true],
    ],

    'products' => [
        'label' => '상품', 'model' => Product::class, 'icon' => 'box', 'group' => '상품관리',
        'order' => ['id', 'desc'], 'with' => ['category', 'brand'],
        'columns' => ['thumbnail' => '이미지', 'name' => '상품명', 'category.name' => '카테고리', 'price' => '정가', 'member_price' => '회원가', 'stock' => '재고', 'is_active' => '판매'],
        'fields' => [
            ['name' => 'category_id', 'label' => '카테고리', 'type' => 'select', 'required' => true,
                'options_from' => ['model' => Category::class, 'key' => 'id', 'label' => 'name', 'order' => 'sort_order']],
            ['name' => 'brand_id', 'label' => '브랜드', 'type' => 'select',
                'options_from' => ['model' => Brand::class, 'key' => 'id', 'label' => 'name', 'order' => 'sort_order']],
            ['name' => 'name', 'label' => '상품명', 'type' => 'text', 'required' => true],
            ['name' => 'slug', 'label' => '슬러그(영문 URL)', 'type' => 'text', 'required' => true],
            ['name' => 'code', 'label' => '상품코드', 'type' => 'text'],
            ['name' => 'unit', 'label' => '판매단위', 'type' => 'text', 'hint' => '예: EA, BOX, SET'],
            ['name' => 'maker', 'label' => '제조사', 'type' => 'text'],
            ['name' => 'price', 'label' => '정가(원)', 'type' => 'number', 'required' => true],
            ['name' => 'cost', 'label' => '매입단가(원, 참고용·비노출)', 'type' => 'number', 'hint' => '마진 참고용. 고객 화면에는 표시되지 않습니다.'],
            ['name' => 'member_price', 'label' => '기본 병원가(원, 전용가 미설정 시 적용)', 'type' => 'number'],
            ['name' => 'tax_type', 'label' => '과세구분', 'type' => 'select', 'required' => true,
                'options' => ['taxable' => '과세', 'exempt' => '면세']],
            ['name' => 'stock', 'label' => '재고', 'type' => 'number'],
            ['name' => 'thumbnail', 'label' => '대표 이미지', 'type' => 'image'],
            ['name' => 'summary', 'label' => '짧은 설명', 'type' => 'textarea', 'rows' => 2],
            ['name' => 'description', 'label' => '상세 설명(HTML)', 'type' => 'textarea', 'rows' => 6],
            ['name' => 'spec', 'label' => '규격/사양', 'type' => 'textarea', 'rows' => 4],
            ['name' => 'badge', 'label' => '커스텀 뱃지', 'type' => 'text', 'hint' => '예: 기획, 세일'],
            ['name' => 'is_featured', 'label' => '추천 상품', 'type' => 'checkbox'],
            ['name' => 'is_best', 'label' => '베스트 상품', 'type' => 'checkbox'],
            ['name' => 'is_new', 'label' => '신상품', 'type' => 'checkbox'],
            ['name' => 'is_active', 'label' => '판매중', 'type' => 'checkbox'],
            ['name' => 'sort_order', 'label' => '정렬 순서', 'type' => 'number'],
        ],
        'defaults' => ['is_active' => true, 'unit' => 'EA', 'stock' => 0],
    ],

    'banners' => [
        'label' => '배너', 'model' => Banner::class, 'icon' => 'monitor', 'group' => '전시관리',
        'order' => ['sort_order', 'asc'],
        'columns' => ['title' => '제목', 'position' => '위치', 'sort_order' => '순서', 'is_active' => '활성'],
        'fields' => [
            ['name' => 'title', 'label' => '제목', 'type' => 'text', 'required' => true, 'hint' => '{point} 입력 시 사이트설정의 신규가입 적립금으로 자동 치환'],
            ['name' => 'subtitle', 'label' => '부제', 'type' => 'text'],
            ['name' => 'image', 'label' => '배너 이미지', 'type' => 'image', 'hint' => '메인 슬라이드·서브 배너 모두 배경 이미지로 표시(어둡게 오버레이). 없으면 아래 배경색 사용'],
            ['name' => 'bg_color', 'label' => '배경색(이미지 없을 때)', 'type' => 'text', 'hint' => '예: #0b3d91 또는 linear-gradient(135deg,#0f8a8a,#0b3d91)'],
            ['name' => 'link', 'label' => '링크', 'type' => 'text'],
            ['name' => 'position', 'label' => '위치', 'type' => 'select', 'required' => true,
                'options' => ['main' => '메인 슬라이드', 'sub' => '서브 배너']],
            ['name' => 'sort_order', 'label' => '정렬 순서', 'type' => 'number'],
            ['name' => 'is_active', 'label' => '노출', 'type' => 'checkbox'],
        ],
        'defaults' => ['is_active' => true, 'position' => 'main'],
    ],

    'ads' => [
        'label' => '사이드 광고', 'model' => App\Models\Ad::class, 'icon' => 'monitor', 'group' => '전시관리',
        'order' => ['sort_order', 'asc'],
        'columns' => ['image' => '이미지', 'title' => '제목', 'price' => '광고가', 'position' => '위치', 'sort_order' => '순서', 'is_active' => '활성'],
        'fields' => [
            ['name' => 'title', 'label' => '제목', 'type' => 'text', 'required' => true],
            ['name' => 'subtitle', 'label' => '부제(한 줄 설명)', 'type' => 'text'],
            ['name' => 'image', 'label' => '광고 이미지', 'type' => 'image', 'hint' => '없으면 아래 배경색으로 카드 표시'],
            ['name' => 'bg_color', 'label' => '배경색(이미지 없을 때)', 'type' => 'text', 'hint' => '예: linear-gradient(135deg,#1857c4,#06256b)'],
            ['name' => 'price', 'label' => '광고가(원, 선택)', 'type' => 'number'],
            ['name' => 'badge', 'label' => '뱃지', 'type' => 'text', 'hint' => '예: AD, 특가, 신제품'],
            ['name' => 'link', 'label' => '클릭 이동 URL(외부 가능)', 'type' => 'text'],
            ['name' => 'position', 'label' => '노출 위치', 'type' => 'select', 'required' => true,
                'options' => ['both' => '양쪽', 'left' => '좌측만', 'right' => '우측만']],
            ['name' => 'sort_order', 'label' => '정렬 순서', 'type' => 'number'],
            ['name' => 'is_active', 'label' => '노출', 'type' => 'checkbox'],
        ],
        'defaults' => ['is_active' => true, 'position' => 'both'],
    ],

    'coupons' => [
        'label' => '쿠폰', 'model' => Coupon::class, 'icon' => 'tag', 'group' => '프로모션',
        'order' => ['id', 'desc'],
        'columns' => ['code' => '코드', 'name' => '이름', 'type' => '유형', 'value' => '값', 'used_count' => '사용', 'is_active' => '활성'],
        'fields' => [
            ['name' => 'code', 'label' => '쿠폰 코드', 'type' => 'text', 'required' => true, 'hint' => '예: WELCOME10 (대소문자 무시)'],
            ['name' => 'name', 'label' => '쿠폰 이름', 'type' => 'text', 'required' => true],
            ['name' => 'type', 'label' => '할인 유형', 'type' => 'select', 'required' => true,
                'options' => ['fixed' => '정액(원)', 'percent' => '정률(%)']],
            ['name' => 'value', 'label' => '할인 값(정액=원 / 정률=%)', 'type' => 'number', 'required' => true],
            ['name' => 'min_order_amount', 'label' => '최소 주문금액(원)', 'type' => 'number'],
            ['name' => 'max_discount', 'label' => '정률 할인 상한(원, 선택)', 'type' => 'number'],
            ['name' => 'starts_at', 'label' => '사용 시작일시', 'type' => 'datetime'],
            ['name' => 'ends_at', 'label' => '사용 종료일시', 'type' => 'datetime'],
            ['name' => 'usage_limit', 'label' => '전체 사용 한도(비우면 무제한)', 'type' => 'number'],
            ['name' => 'per_user_limit', 'label' => '1인당 사용 한도(0=무제한)', 'type' => 'number'],
            ['name' => 'is_public', 'label' => '공개형(체크 시 코드입력 누구나 / 해제 시 발행받은 회원만)', 'type' => 'checkbox'],
            ['name' => 'is_active', 'label' => '활성', 'type' => 'checkbox'],
        ],
        'defaults' => ['is_active' => true, 'is_public' => true, 'type' => 'fixed', 'per_user_limit' => 1, 'min_order_amount' => 0],
    ],

    'notices' => [
        'label' => '공지사항', 'model' => Notice::class, 'icon' => 'doc', 'group' => '고객지원',
        'order' => ['id', 'desc'],
        'columns' => ['title' => '제목', 'is_pinned' => '고정', 'views' => '조회', 'published_at' => '게시일'],
        'fields' => [
            ['name' => 'title', 'label' => '제목', 'type' => 'text', 'required' => true],
            ['name' => 'body', 'label' => '내용', 'type' => 'textarea', 'required' => true, 'rows' => 10],
            ['name' => 'is_pinned', 'label' => '상단 고정', 'type' => 'checkbox'],
            ['name' => 'published_at', 'label' => '게시일시', 'type' => 'datetime'],
        ],
        'defaults' => ['published_at' => 'now'],
    ],

    'faqs' => [
        'label' => 'FAQ', 'model' => Faq::class, 'icon' => 'question', 'group' => '고객지원',
        'order' => ['sort_order', 'asc'],
        'columns' => ['category' => '분류', 'question' => '질문', 'sort_order' => '순서'],
        'fields' => [
            ['name' => 'category', 'label' => '분류', 'type' => 'text', 'required' => true],
            ['name' => 'question', 'label' => '질문', 'type' => 'text', 'required' => true],
            ['name' => 'answer', 'label' => '답변', 'type' => 'textarea', 'required' => true, 'rows' => 6],
            ['name' => 'sort_order', 'label' => '정렬 순서', 'type' => 'number'],
        ],
    ],
];
