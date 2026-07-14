<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CatalogController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\CommunityController;
use App\Http\Controllers\Api\HomeController;
use App\Http\Controllers\Api\MypageController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\WishlistController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| 메디셀 모바일 API (v1) — Sanctum 토큰 인증
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ===== 공개(비로그인 허용, 로그인 시 회원가 반영) =====
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::get('/settings', [SettingController::class, 'index']);
    Route::get('/home', [HomeController::class, 'index']);

    // 푸시 토큰 해제(로그아웃 후에도 호출 가능하도록 공개)
    Route::post('/push/unregister', [\App\Http\Controllers\Api\PushController::class, 'unregister']);

    Route::get('/categories', [CatalogController::class, 'categories']);
    Route::get('/products', [CatalogController::class, 'index']);
    Route::get('/products/search', [CatalogController::class, 'search']);
    Route::get('/category/{slug}', [CatalogController::class, 'category']);
    Route::get('/product/{slug}', [CatalogController::class, 'show']);

    Route::get('/community/notices', [CommunityController::class, 'notices']);
    Route::get('/community/notices/{notice}', [CommunityController::class, 'notice']);
    Route::get('/community/reviews', [CommunityController::class, 'reviews']);
    Route::get('/community/faq', [CommunityController::class, 'faq']);
    Route::get('/community/qna', [CommunityController::class, 'qna']);

    // ===== 로그인 필요 =====
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::post('/product/{product}/review', [CatalogController::class, 'storeReview']);

        // 장바구니
        Route::get('/cart', [CartController::class, 'index']);
        Route::post('/cart/{product}', [CartController::class, 'add']);
        Route::put('/cart/{item}', [CartController::class, 'update']);
        Route::delete('/cart/{item}', [CartController::class, 'remove']);

        // 관심상품
        Route::get('/wishlist', [WishlistController::class, 'index']);
        Route::post('/wishlist/{product}', [WishlistController::class, 'toggle']);

        // 주문/결제
        Route::get('/checkout', [OrderController::class, 'checkout']);
        Route::post('/checkout/coupon', [OrderController::class, 'previewCoupon']);
        Route::post('/orders', [OrderController::class, 'store']);
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{order}', [OrderController::class, 'show']);
        Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel']);

        // 마이페이지
        Route::get('/mypage', [MypageController::class, 'summary']);
        Route::get('/mypage/points', [MypageController::class, 'points']);
        Route::get('/mypage/coupons', [MypageController::class, 'coupons']);
        Route::put('/mypage/profile', [MypageController::class, 'updateProfile']);

        // 커뮤니티(작성)
        Route::post('/community/inquiry', [CommunityController::class, 'inquiryStore']);

        // 실시간 상담
        Route::get('/chat/open', [ChatController::class, 'open']);
        Route::post('/chat/start', [ChatController::class, 'start']);
        Route::post('/chat/send', [ChatController::class, 'send']);

        // 푸시 알림 토큰 등록 (로그인 회원과 연결)
        Route::post('/push/register', [\App\Http\Controllers\Api\PushController::class, 'register']);
    });
});
