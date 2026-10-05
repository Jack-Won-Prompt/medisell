<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Notice;
use App\Models\Product;

/** 검색엔진(네이버 서치어드바이저 등) 제출용 sitemap.xml — 판매중 상품·카테고리·공지·약관 */
class SitemapController extends Controller
{
    public function index()
    {
        // medisell.co.kr 과 www 가 둘 다 열리므로 요청 호스트가 아니라 APP_URL 기준 주소로 통일
        $base = rtrim(config('app.url'), '/');
        $u = fn (string $name, $params = []) => $base.route($name, $params, false);

        $urls = [
            ['loc' => $u('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
            ['loc' => $u('catalog.index'), 'priority' => '0.8', 'changefreq' => 'daily'],
        ];

        foreach (Category::where('is_active', true)->orderBy('sort_order')->get(['slug', 'updated_at']) as $c) {
            $urls[] = ['loc' => $u('catalog.category', $c->slug), 'lastmod' => $c->updated_at, 'priority' => '0.7', 'changefreq' => 'weekly'];
        }

        foreach (Product::active()->orderBy('id')->get(['slug', 'updated_at']) as $p) {
            $urls[] = ['loc' => $u('catalog.show', $p->slug), 'lastmod' => $p->updated_at, 'priority' => '0.6', 'changefreq' => 'weekly'];
        }

        $urls[] = ['loc' => $u('community.notices'), 'priority' => '0.4', 'changefreq' => 'weekly'];
        foreach (Notice::whereNotNull('published_at')->where('published_at', '<=', now())->get(['id', 'updated_at']) as $n) {
            $urls[] = ['loc' => $u('community.notice', $n), 'lastmod' => $n->updated_at, 'priority' => '0.3', 'changefreq' => 'monthly'];
        }

        $urls[] = ['loc' => $u('legal.terms'), 'priority' => '0.2', 'changefreq' => 'yearly'];
        $urls[] = ['loc' => $u('legal.privacy'), 'priority' => '0.2', 'changefreq' => 'yearly'];

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
