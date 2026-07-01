<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 카테고리 (self-parent 트리: 대분류 → 중분류)
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();      // 한 줄 설명
            $table->string('icon')->nullable();         // 아이콘 키
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 브랜드 / 총판·대리점
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 상품
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code')->nullable();          // 상품코드/SKU
            $table->string('unit')->default('EA');       // 판매단위 (EA/BOX 등)
            $table->string('maker')->nullable();         // 제조사
            $table->text('summary')->nullable();         // 짧은 설명
            $table->longText('description')->nullable(); // 상세 설명
            $table->text('spec')->nullable();            // 규격/사양

            $table->unsignedInteger('price')->default(0);        // 정가(일반회원가)
            $table->unsignedInteger('member_price')->nullable(); // 사업자 승인회원가
            $table->unsignedInteger('stock')->default(0);

            $table->string('thumbnail')->nullable();
            $table->json('images')->nullable();          // 상세 이미지 경로 배열

            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false); // 추천
            $table->boolean('is_best')->default(false);     // 베스트
            $table->boolean('is_new')->default(false);      // 신상품
            $table->string('badge')->nullable();            // 기획/세일 등 커스텀 뱃지
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // 배너 (메인 슬라이드 / 서브)
        Schema::create('banners', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('image')->nullable();
            $table->string('link')->nullable();
            $table->string('bg_color')->nullable();      // 이미지 없을 때 배경
            $table->string('position')->default('main'); // main/sub
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 장바구니 항목 (회원당)
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();
            $table->unique(['user_id', 'product_id']);
        });

        // 주문
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            // pending(입금대기) / paid(입금확인) / preparing(상품준비) / shipped(배송중) / done(완료) / cancelled(취소)
            $table->string('status')->default('pending');
            $table->string('payment_method')->default('bank'); // bank(무통장)

            $table->string('receiver_name');
            $table->string('receiver_phone');
            $table->string('postcode', 10)->nullable();
            $table->string('address1');
            $table->string('address2')->nullable();
            $table->string('memo')->nullable();

            $table->unsignedInteger('subtotal')->default(0);
            $table->unsignedInteger('shipping_fee')->default(0);
            $table->unsignedInteger('discount')->default(0);
            $table->unsignedInteger('point_used')->default(0);
            $table->unsignedInteger('total')->default(0);

            $table->string('bank')->nullable();          // 입금 은행
            $table->string('depositor')->nullable();     // 입금자명
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        // 주문 상품 (스냅샷)
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('unit')->nullable();
            $table->unsignedInteger('price');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('subtotal');
            $table->timestamps();
        });

        // 적립금 내역
        Schema::create('point_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('amount');                   // +적립 / -사용
            $table->unsignedInteger('balance');          // 변동 후 잔액
            $table->string('reason');
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // 상품후기
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name')->nullable();
            $table->unsignedTinyInteger('rating')->default(5);
            $table->string('title');
            $table->text('body');
            $table->timestamps();
        });

        // 문의 (견적문의 quote / 1:1문의 qna / 상품요청 request)
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('qna');      // quote/qna/request
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('subject');
            $table->text('body');
            $table->string('status')->default('pending'); // pending/answered
            $table->text('answer')->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->boolean('is_secret')->default(false);
            $table->timestamps();
        });

        // 공지사항
        Schema::create('notices', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->longText('body');
            $table->boolean('is_pinned')->default(false);
            $table->unsignedInteger('views')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        // FAQ
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('category')->default('일반');
            $table->string('question');
            $table->text('answer');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'faqs', 'notices', 'inquiries', 'reviews', 'point_logs',
            'order_items', 'orders', 'cart_items', 'banners',
            'products', 'brands', 'categories',
        ] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
