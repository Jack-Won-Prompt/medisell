<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\BankDepositController as AdminBankDepositController;
use App\Http\Controllers\Admin\ChatController as AdminChatController;
use App\Http\Controllers\Admin\CoupangController as AdminCoupangProductController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\ExportController as AdminExportController;
use App\Http\Controllers\Admin\InquiryController as AdminInquiryController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ResourceController as AdminResourceController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\TaxInvoiceController as AdminTaxInvoiceController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MypageController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\WishlistController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// ===== 상품 카탈로그 =====
Route::get('/products', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/search', [CatalogController::class, 'search'])->name('catalog.search');
Route::get('/category/{slug}', [CatalogController::class, 'category'])->name('catalog.category');
Route::get('/product/{slug}', [CatalogController::class, 'show'])->name('catalog.show');
Route::post('/product/{product}/review', [CatalogController::class, 'storeReview'])
    ->middleware('auth')->name('catalog.review');

// ===== 장바구니 / 관심상품 (로그인 필요) =====
Route::middleware('auth')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/{product}', [CartController::class, 'add'])->name('cart.add');
    Route::put('/cart/{item}', [CartController::class, 'update'])->name('cart.update');
    Route::delete('/cart/{item}', [CartController::class, 'remove'])->name('cart.remove');

    Route::post('/wishlist/{product}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');

    // ===== 주문/결제 =====
    Route::get('/checkout', [OrderController::class, 'checkout'])->name('order.checkout');
    Route::post('/checkout', [OrderController::class, 'store'])->name('order.store');
    Route::post('/checkout/coupon', [OrderController::class, 'applyCoupon'])->name('order.coupon.apply');
    Route::delete('/checkout/coupon', [OrderController::class, 'removeCoupon'])->name('order.coupon.remove');
    Route::get('/order/complete/{order}', [OrderController::class, 'complete'])->name('order.complete');

    // 결제 페이지 (PG 공통)
    Route::get('/order/pay/{order}', [PaymentController::class, 'pay'])->name('order.pay');
    // 토스
    Route::get('/payment/toss/success', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('/payment/toss/fail', [PaymentController::class, 'fail'])->name('payment.fail');
    // 포트원(아임포트)
    Route::post('/payment/portone/verify', [PaymentController::class, 'portoneVerify'])->name('payment.portone.verify');
    Route::post('/payment/portone/simulate/{order}', [PaymentController::class, 'portoneSimulate'])->name('payment.portone.simulate');

    // ===== 마이페이지 =====
    Route::prefix('mypage')->name('mypage.')->controller(MypageController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/orders', 'orders')->name('orders');
        Route::get('/orders/{order}', 'order')->name('order');
        Route::post('/orders/{order}/cancel', 'cancelOrder')->name('order.cancel');
        Route::get('/points', 'points')->name('points');
        Route::get('/coupons', 'coupons')->name('coupons');
        Route::get('/profile', 'profile')->name('profile');
        Route::put('/profile', 'updateProfile')->name('profile.update');
    });
    Route::get('/mypage/wishlist', [WishlistController::class, 'index'])->name('mypage.wishlist');
});

// ===== 커뮤니티 / 고객지원 =====
Route::prefix('community')->name('community.')->controller(CommunityController::class)->group(function () {
    Route::get('/notices', 'notices')->name('notices');
    Route::get('/notices/{notice}', 'notice')->name('notice');
    Route::get('/reviews', 'reviews')->name('reviews');
    Route::get('/faq', 'faq')->name('faq');
    Route::get('/qna', 'qna')->name('qna');               // 1:1 / 견적 문의 목록
    Route::get('/inquiry', 'inquiryForm')->name('inquiry');
    Route::post('/inquiry', 'inquiryStore')->name('inquiry.store');
});

// ===== 안내 페이지 (메인 탭바 연결) =====
Route::view('/event/signup', 'guide.event')->name('guide.event');       // 신규회원 이벤트
Route::view('/guide/delivery', 'guide.delivery')->name('guide.delivery'); // 당일출고 안내
Route::view('/guide/payment', 'guide.payment')->name('guide.payment');   // 간편결제 안내

// ===== 관리자 =====
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // 상품 이미지 자동검색(의료몰+네이버) + 확인 후 다운로드
    Route::get('/products/{product}/image-search', [\App\Http\Controllers\Admin\ProductImageController::class, 'search'])->name('products.imagesearch');
    Route::post('/products/{product}/image-fetch', [\App\Http\Controllers\Admin\ProductImageController::class, 'fetch'])->name('products.imagefetch');
    Route::post('/products/{product}/image-url', [\App\Http\Controllers\Admin\ProductImageController::class, 'importUrl'])->name('products.imageurl');

    // 주문 관리
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::put('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.status');
    Route::put('/orders/{order}/shipping', [AdminOrderController::class, 'updateShipping'])->name('orders.shipping');
    // 전자세금계산서
    Route::post('/orders/{order}/tax-invoice', [AdminTaxInvoiceController::class, 'issue'])->name('orders.taxinvoice');
    Route::delete('/tax-invoices/{taxInvoice}', [AdminTaxInvoiceController::class, 'cancel'])->name('taxinvoice.cancel');
    Route::get('/tax-invoices/{taxInvoice}/popup', [AdminTaxInvoiceController::class, 'popup'])->name('taxinvoice.popup');

    // 회원 관리 (병원 승인 + 병원별 전용가 매핑)
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::put('/users/{user}/approve', [AdminUserController::class, 'approve'])->name('users.approve');
    Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.reset');
    Route::put('/users/{user}/admin', [AdminUserController::class, 'toggleAdmin'])->name('users.admin');
    Route::post('/users/{user}/prices', [AdminUserController::class, 'storePrice'])->name('users.prices.store');
    Route::delete('/users/{user}/prices/{price}', [AdminUserController::class, 'destroyPrice'])->name('users.prices.destroy');
    Route::post('/users/{user}/points', [AdminUserController::class, 'adjustPoint'])->name('users.points');
    Route::get('/users/{user}/prices/export', [AdminUserController::class, 'exportPrices'])->name('users.prices.export');
    Route::post('/users/{user}/prices/import', [AdminUserController::class, 'importPrices'])->name('users.prices.import');

    // CSV 내보내기
    Route::get('/export/orders', [AdminExportController::class, 'orders'])->name('export.orders');
    Route::get('/export/products', [AdminExportController::class, 'products'])->name('export.products');
    Route::get('/export/users', [AdminExportController::class, 'users'])->name('export.users');

    // 매출 리포트
    Route::get('/reports/sales', [AdminReportController::class, 'sales'])->name('reports.sales');

    // 푸시 알림 발송 (FCM)
    Route::get('/push', [\App\Http\Controllers\Admin\PushController::class, 'index'])->name('push.index');
    Route::post('/push', [\App\Http\Controllers\Admin\PushController::class, 'send'])->name('push.send');

    // 쿠팡 경쟁가 조회
    Route::get('/coupang', [AdminCoupangProductController::class, 'index'])->name('coupang.index');

    // 쿠폰 발행 (사용자에게)
    Route::get('/coupons/{coupon}/issue', [AdminCouponController::class, 'issueForm'])->name('coupons.issue');
    Route::post('/coupons/{coupon}/issue', [AdminCouponController::class, 'issue'])->name('coupons.issue.store');
    Route::delete('/coupons/{coupon}/issue/{userCoupon}', [AdminCouponController::class, 'revoke'])->name('coupons.revoke');

    // 후기 관리
    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::put('/reviews/{review}/toggle', [AdminReviewController::class, 'toggle'])->name('reviews.toggle');
    Route::delete('/reviews/{review}', [AdminReviewController::class, 'destroy'])->name('reviews.destroy');

    // 무통장 입금확인 (계좌조회)
    Route::get('/bank-deposits', [AdminBankDepositController::class, 'index'])->name('bank.index');
    Route::post('/bank-deposits/collect', [AdminBankDepositController::class, 'collect'])->name('bank.collect');
    Route::post('/bank-deposits/auto-match', [AdminBankDepositController::class, 'autoMatch'])->name('bank.automatch');
    Route::post('/bank-deposits/{deposit}/match', [AdminBankDepositController::class, 'match'])->name('bank.match');

    // 사이트 설정
    Route::get('/settings', [AdminSettingController::class, 'edit'])->name('settings.edit');
    Route::put('/settings', [AdminSettingController::class, 'update'])->name('settings.update');

    // 실시간 상담 콘솔
    Route::get('/chat', [AdminChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/{room}', [AdminChatController::class, 'show'])->name('chat.show');
    Route::post('/chat/{room}/reply', [AdminChatController::class, 'reply'])->name('chat.reply');

    // 문의 관리 (답변)
    Route::get('/inquiries', [AdminInquiryController::class, 'index'])->name('inquiries.index');
    Route::get('/inquiries/{inquiry}', [AdminInquiryController::class, 'show'])->name('inquiries.show');
    Route::put('/inquiries/{inquiry}/answer', [AdminInquiryController::class, 'answer'])->name('inquiries.answer');
    Route::delete('/inquiries/{inquiry}', [AdminInquiryController::class, 'destroy'])->name('inquiries.destroy');

    // 설정 기반 제네릭 CRUD (categories, brands, products, banners, notices, faqs)
    Route::get('/{resource}', [AdminResourceController::class, 'index'])->name('index');
    Route::get('/{resource}/create', [AdminResourceController::class, 'create'])->name('create');
    Route::post('/{resource}', [AdminResourceController::class, 'store'])->name('store');
    Route::get('/{resource}/{id}/edit', [AdminResourceController::class, 'edit'])->name('edit');
    Route::put('/{resource}/{id}', [AdminResourceController::class, 'update'])->name('update');
    Route::delete('/{resource}/{id}', [AdminResourceController::class, 'destroy'])->name('destroy');
});

// ===== 1:1 실시간 상담 (회원/비회원 공용) =====
Route::get('/chat/open', [ChatController::class, 'open'])->name('chat.open');
Route::post('/chat/start', [ChatController::class, 'start'])->name('chat.start');
Route::post('/chat/send', [ChatController::class, 'send'])->name('chat.send');

// ===== 결제 웹훅 (공개, CSRF 제외) =====
Route::post('/payment/toss/webhook', [PaymentController::class, 'webhook'])->name('payment.webhook');
Route::post('/payment/portone/webhook', [PaymentController::class, 'portoneWebhook'])->name('payment.portone.webhook');

// ===== 모바일 앱 인앱 웹뷰 결제 (세션 없이 signed URL 진입) =====
Route::get('/pay/app/{order}', [\App\Http\Controllers\MobilePaymentController::class, 'pay'])
    ->middleware('signed')->name('pay.app');
Route::get('/pay/app-toss/success', [\App\Http\Controllers\MobilePaymentController::class, 'tossSuccess'])->name('pay.app.toss.success');
Route::get('/pay/app-toss/fail', [\App\Http\Controllers\MobilePaymentController::class, 'tossFail'])->name('pay.app.toss.fail');
Route::post('/pay/app-portone/verify', [\App\Http\Controllers\MobilePaymentController::class, 'portoneVerify'])->name('pay.app.portone.verify');
Route::post('/pay/app-portone/simulate/{order}', [\App\Http\Controllers\MobilePaymentController::class, 'portoneSimulate'])->name('pay.app.portone.simulate');
Route::get('/pay/app-result', [\App\Http\Controllers\MobilePaymentController::class, 'result'])->name('pay.app.result');

// ===== 인증 =====
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.attempt');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
