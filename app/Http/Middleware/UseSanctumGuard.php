<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * 앱 API 의 기본 인증 가드를 sanctum 으로.
 *
 * 공개 라우트(상품 목록·상세·홈 등)는 auth:sanctum 없이 열려 있어 $request->user() 가 web 가드를 보고
 * 늘 null 이었다 — 승인 병원 회원이 앱에서 정가를 보다가 주문(auth:sanctum)에서는 병원가로 청구되는
 * 불일치가 생겼다. 토큰이 있으면 그 회원, 없으면 지금처럼 비회원(null)으로 처리된다.
 */
class UseSanctumGuard
{
    public function handle(Request $request, Closure $next)
    {
        auth()->shouldUse('sanctum');

        return $next($request);
    }
}
