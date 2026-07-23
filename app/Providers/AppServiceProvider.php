<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\ChatMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Models\LoginLog;
use App\Observers\ChatMessageObserver;
use App\Observers\OrderObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
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

        // 로그인 이력 기록 (성공/실패) — 웹·API 모든 경로
        Event::listen(Login::class, function (Login $e) {
            $this->recordLogin(true, $e->user->email ?? null, $e->guard ?? null, $e->user->id ?? null);
        });
        Event::listen(Failed::class, function (Failed $e) {
            $this->recordLogin(false, $e->credentials['email'] ?? null, $e->guard ?? null, $e->user->id ?? null);
        });

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
                // DB 오류(예: 연결 끊김) 시에도 에러 페이지가 렌더되도록 안전 기본값으로 폴백
                $data = [
                    'navCategories'   => collect(),
                    'cartCount'       => 0,
                    'wishlistIds'     => [],
                    'recentProducts'  => collect(),
                    'site'            => config('site'),
                    'sideAds'         => collect(),
                ];
                try {
                    if (Schema::hasTable('ads')) {
                        $data['sideAds'] = \App\Models\Ad::active()->orderBy('sort_order')->orderBy('id')->get();
                    }
                } catch (\Throwable $e) {
                    // ads 테이블 이전 등 — 광고 없이 진행
                }
                try {
                    $data['navCategories'] = Category::with(['children' => fn ($q) => $q->where('is_active', true)])
                        ->whereNull('parent_id')
                        ->where('is_active', true)
                        ->orderBy('sort_order')
                        ->get();

                    if (auth()->check()) {
                        $data['cartCount'] = auth()->user()->cartItems()->count();
                        $data['wishlistIds'] = auth()->user()->wishlists()->pluck('product_id')->all();
                    }

                    // 최근 본 상품 (세션 순서 유지)
                    $rvIds = session('recently_viewed', []);
                    if ($rvIds) {
                        $data['recentProducts'] = Product::active()->whereIn('id', $rvIds)->get()
                            ->sortBy(fn ($p) => array_search($p->id, $rvIds))->values();
                    }
                } catch (\Throwable $e) {
                    // 기본값 유지 — 헤더/사이드바가 비어도 페이지는 표시
                }
            }
            $view->with($data);
        });
    }

    /** 로그인 이력 1건 기록 (테이블 없거나 오류 시 조용히 무시) */
    protected function recordLogin(bool $success, ?string $email, ?string $guard, ?int $userId): void
    {
        try {
            if (! Schema::hasTable('login_logs')) {
                return;
            }
            $req = request();
            LoginLog::create([
                'user_id'    => $userId,
                'email'      => $email,
                'success'    => $success,
                'ip'         => $req?->ip(),
                'user_agent' => mb_substr((string) $req?->userAgent(), 0, 512),
                'guard'      => $guard,
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // 로깅 실패가 로그인 자체를 막지 않도록 무시
        }
    }
}
