<?php

namespace App\Http\Controllers;

use App\Models\Notice;
use App\Models\Product;
use Illuminate\Support\Str;

/** 검색엔진(네이버 서치어드바이저 RSS 제출)용 RSS 2.0 — 최근 등록·수정 상품 + 공지, 최신 50건 */
class RssController extends Controller
{
    private const LIMIT = 50;

    public function index()
    {
        // sitemap 과 같이 요청 호스트(www 등)가 아니라 APP_URL 기준 주소로 통일
        $base = rtrim(config('app.url'), '/');
        $u = fn (string $name, $params = []) => $base.route($name, $params, false);

        $items = collect();

        foreach (Product::active()->with('category')->latest('updated_at')->take(self::LIMIT)->get() as $p) {
            $desc = collect([
                $p->spec ? "규격 {$p->spec}" : null,
                $p->maker ? "제조사 {$p->maker}" : null,
                "판매단위 {$p->unit}",
                $p->category?->name,
            ])->filter()->implode(' · ');
            $items->push([
                'title'    => $p->name,
                'link'     => $u('catalog.show', $p->slug),
                'desc'     => $desc,
                'category' => $p->category?->name ?? '상품',
                'date'     => $p->updated_at ?? $p->created_at,
                'image'    => $p->thumbnail,
            ]);
        }

        foreach (Notice::whereNotNull('published_at')->where('published_at', '<=', now())->latest('published_at')->take(20)->get() as $n) {
            $items->push([
                'title'    => '[공지] '.$n->title,
                'link'     => $u('community.notice', $n),
                'desc'     => Str::limit(trim(strip_tags((string) $n->body)), 300),
                'category' => '공지사항',
                'date'     => $n->published_at,
                'image'    => null,
            ]);
        }

        $items = $items->sortByDesc(fn ($i) => $i['date']?->timestamp ?? 0)->take(self::LIMIT)->values();

        return response()
            ->view('rss', [
                'items'    => $items,
                'home'     => $u('home'),
                'self'     => $u('rss'),
                'site'     => config('site'),
                'built'    => $items->first()['date'] ?? now(),
            ])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
