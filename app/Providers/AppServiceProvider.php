<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\ChatMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Observers\ChatMessageObserver;
use App\Observers\OrderObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // FCM 푸시 트리거 — 주문 상태 변경 / 관리자 상담 답변
        Order::observe(OrderObserver::class);
        ChatMessage::observe(ChatMessageObserver::class);

        // DB 사이트설정으로 config('site') 런타임 오버라이드 (관리자 수정 즉시 반영)
        try {
            if (Schema::hasTable('settings')) {
                $override = Setting::get('site');
                if (is_array($override)) {
                    config(['site' => array_merge(config('site'), $override)]);
                }
            }
        } catch (\Throwable $e) {
            // 마이그레이션 이전 등 — 기본 config 사용
        }

        // 헤더 메가메뉴 / 사이드바 / 푸터 공통 데이터 (요청당 1회 조회)
        View::composer('*', function ($view) {
            static $data = null;
            if ($data === null) {
                $rootCats = Category::with(['children' => fn ($q) => $q->where('is_active', true)])
                    ->whereNull('parent_id')
                    ->where('is_active', true)
                    ->orderBy('sort_order')
                    ->get();

                $cartCount = 0;
                $wishlistIds = [];
                if (auth()->check()) {
                    $cartCount = auth()->user()->cartItems()->count();
                    $wishlistIds = auth()->user()->wishlists()->pluck('product_id')->all();
                }

                // 최근 본 상품 (세션 순서 유지)
                $rvIds = session('recently_viewed', []);
                $recentProducts = collect();
                if ($rvIds) {
                    $recentProducts = Product::active()->whereIn('id', $rvIds)->get()
                        ->sortBy(fn ($p) => array_search($p->id, $rvIds))->values();
                }

                $data = [
                    'navCategories'   => $rootCats,
                    'cartCount'       => $cartCount,
                    'wishlistIds'     => $wishlistIds,
                    'recentProducts'  => $recentProducts,
                    'site'            => config('site'),
                ];
            }
            $view->with($data);
        });
    }
}
