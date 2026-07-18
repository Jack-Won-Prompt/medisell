<?php

namespace App\Support;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Banner;
use App\Models\Notice;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * 모바일 API용 JSON 직렬화 헬퍼.
 * 저장된 이미지 URL은 http://localhost/medisell/... 형태의 절대경로이므로
 * 요청 호스트(에뮬레이터 10.0.2.2 / 실기기 LAN IP)에 맞춰 host 부분을 재작성한다.
 */
class ApiSerializer
{
    /**
     * 저장된 절대 이미지 URL을 현재 요청 서버에 맞게 재작성한다.
     * - host 는 요청 host(에뮬레이터 10.0.2.2 / 운영 도메인 등)로 교체
     * - 서브폴더는 요청 경로에서 자동 감지 (로컬=/medisell, 운영 루트=빈값)
     *   → 시드 데이터의 고정 `/medisell` prefix 를 현재 배포 prefix 로 치환
     */
    public static function image(?string $stored, Request $request): ?string
    {
        if (! $stored) {
            return null;
        }

        $root = rtrim($request->getSchemeAndHttpHost(), '/');
        $prefix = self::basePrefix($request); // '' (운영 루트) 또는 '/medisell' (로컬)

        // 절대 URL → host 제거
        $path = preg_match('#^https?://#', $stored)
            ? preg_replace('#^https?://[^/]+#', '', $stored)   // /medisell/product/x.png
            : '/'.ltrim($stored, '/');

        // 시드 시점의 고정 서브폴더(/medisell) 제거 후 현재 prefix 로 재구성
        $path = preg_replace('#^/medisell(?=/)#', '', $path);  // /product/x.png

        return $root.$prefix.$path;
    }

    /** 현재 API 요청이 서비스되는 서브폴더 prefix (/api/... 앞 부분) */
    protected static function basePrefix(Request $request): string
    {
        $uri = $request->getRequestUri();               // /medisell/api/v1/home  또는  /api/v1/home
        $prefix = preg_replace('#/api/.*$#', '', $uri);  // /medisell            또는  ''

        return rtrim($prefix ?? '', '/');
    }

    public static function images(?array $stored, Request $request): array
    {
        return collect($stored ?? [])
            ->filter()
            ->map(fn ($u) => self::image($u, $request))
            ->values()->all();
    }

    /** 상품 카드(목록)용 요약 */
    public static function productCard(Product $p, Request $request): array
    {
        $user  = $request->user();
        $price = $p->priceFor($user);

        return [
            'id'          => $p->id,
            'name'        => $p->name,
            'slug'        => $p->slug,
            'code'        => $p->code,
            'unit'        => $p->unit,
            'maker'       => $p->maker,
            'summary'     => $p->summary,
            'thumbnail'   => self::image($p->thumbnail, $request),
            'price'       => $price,                       // 회원유형별 실판매가
            'list_price'  => (int) $p->price,              // 정가
            'discount_rate' => $p->discountRateFor($price),
            'has_special' => $p->hasSpecialPriceFor($user),
            'stock'       => (int) $p->stock,
            'is_best'     => (bool) $p->is_best,
            'is_new'      => (bool) $p->is_new,
            'is_featured' => (bool) $p->is_featured,
            'badge'       => $p->badge,
            'brand'       => $p->relationLoaded('brand') && $p->brand
                ? ['id' => $p->brand->id, 'name' => $p->brand->name, 'slug' => $p->brand->slug]
                : null,
        ];
    }

    /** 상품 상세 */
    public static function productDetail(Product $p, Request $request): array
    {
        $user    = $request->user();
        $price   = $p->priceFor($user);
        $gallery = collect([$p->thumbnail])
            ->merge($p->images ?? [])
            ->filter()->unique()
            ->map(fn ($u) => self::image($u, $request))
            ->values()->all();

        // description 내 이미지(절대 URL) 를 현재 서버에 맞게 재작성
        $description = $p->description
            ? preg_replace_callback('#https?://[^"\'\s)]+\.(?:png|jpe?g|gif|webp)#i', function ($m) use ($request) {
                return self::image($m[0], $request) ?? $m[0];
            }, $p->description)
            : null;

        return array_merge(self::productCard($p, $request), [
            'description' => $description,
            'spec'        => $p->spec,
            'tax_type'    => $p->tax_type,
            'view_count'  => (int) $p->view_count,
            'gallery'     => $gallery,
            'category'    => $p->relationLoaded('category') && $p->category
                ? self::categoryBrief($p->category)
                : null,
            'reviews'     => $p->relationLoaded('reviews')
                ? $p->reviews->map(fn ($r) => self::review($r))->all()
                : [],
            'review_count' => $p->relationLoaded('reviews') ? $p->reviews->count() : 0,
            'rating_avg'   => $p->relationLoaded('reviews') && $p->reviews->count()
                ? round($p->reviews->avg('rating'), 1) : 0,
        ]);
    }

    public static function categoryBrief(Category $c): array
    {
        return [
            'id'   => $c->id,
            'name' => $c->name,
            'slug' => $c->slug,
            'tagline' => $c->tagline,
            'icon' => $c->icon,
            'parent_id' => $c->parent_id,
        ];
    }

    public static function categoryTree(Category $c): array
    {
        return array_merge(self::categoryBrief($c), [
            'children' => $c->relationLoaded('children')
                ? $c->children->map(fn ($ch) => self::categoryTree($ch))->all()
                : [],
        ]);
    }

    public static function brand(Brand $b, Request $request): array
    {
        return [
            'id'   => $b->id,
            'name' => $b->name,
            'slug' => $b->slug,
            'logo' => self::image($b->logo, $request),
            'description' => $b->description,
        ];
    }

    public static function banner(Banner $b, Request $request): array
    {
        return [
            'id'       => $b->id,
            'title'    => $b->title,
            'subtitle' => $b->subtitle,
            'image'    => self::image($b->image, $request),
            'link'     => $b->link,
            'bg_color' => $b->bg_color,
            'position' => $b->position,
        ];
    }

    public static function review(Review $r): array
    {
        return [
            'id'     => $r->id,
            'author' => $r->author_name,
            'rating' => (int) $r->rating,
            'title'  => $r->title,
            'body'   => $r->body,
            'date'   => $r->created_at?->format('Y-m-d'),
        ];
    }

    public static function noticeBrief(Notice $n): array
    {
        return [
            'id'       => $n->id,
            'title'    => $n->title,
            'is_pinned' => (bool) $n->is_pinned,
            'views'    => (int) $n->views,
            'date'     => ($n->published_at ?? $n->created_at)?->format('Y-m-d'),
        ];
    }

    public static function user(User $u): array
    {
        return [
            'id'           => $u->id,
            'name'         => $u->name,
            'email'        => $u->email,
            'member_type'  => $u->member_type,
            'is_business'  => $u->member_type === 'business',
            'is_approved_business' => $u->isApprovedBusiness(),
            'biz_status'   => $u->biz_status,
            'grade'        => $u->grade,
            'phone'        => $u->phone,
            'postcode'     => $u->postcode,
            'address1'     => $u->address1,
            'address2'     => $u->address2,
            'company_name' => $u->company_name,
            'biz_no'       => $u->biz_no,
            'biz_type'     => $u->biz_type,
            'point'        => (int) $u->point,
            'is_admin'     => (bool) $u->is_admin,
        ];
    }

    /** 주문 목록/상세 */
    public static function order(Order $o, Request $request, bool $withItems = false): array
    {
        $data = [
            'id'             => $o->id,
            'order_no'       => $o->order_no,
            'status'         => $o->status,
            'status_label'   => $o->statusLabel(),
            'payment_method' => $o->payment_method,
            'pay_status'     => $o->pay_status,
            'receiver_name'  => $o->receiver_name,
            'receiver_phone' => $o->receiver_phone,
            'postcode'       => $o->postcode,
            'address1'       => $o->address1,
            'address2'       => $o->address2,
            'memo'           => $o->memo,
            'subtotal'       => (int) $o->subtotal,
            'shipping_fee'   => (int) $o->shipping_fee,
            'discount'       => (int) $o->discount,
            'coupon_code'    => $o->coupon_code,
            'point_used'     => (int) $o->point_used,
            'total'          => (int) $o->total,
            'bank'           => $o->bank,
            'depositor'      => $o->depositor,
            'va_bank'        => $o->va_bank,
            'va_account'     => $o->va_account,
            'va_holder'      => $o->va_holder,
            'va_due_at'      => $o->va_due_at?->toIso8601String(),
            'courier'        => $o->courier,
            'tracking_no'    => $o->tracking_no,
            'paid_at'        => $o->paid_at?->toIso8601String(),
            'created_at'     => $o->created_at?->toIso8601String(),
            'item_count'     => $o->items_count ?? ($o->relationLoaded('items') ? $o->items->count() : null),
            'can_cancel'     => in_array($o->status, ['pending', 'paid']),
        ];

        if ($withItems && $o->relationLoaded('items')) {
            $data['items'] = $o->items->map(function ($i) use ($request) {
                return [
                    'id'           => $i->id,
                    'product_id'   => $i->product_id,
                    'product_name' => $i->product_name,
                    'unit'         => $i->unit,
                    'price'        => (int) $i->price,
                    'quantity'     => (int) $i->quantity,
                    'subtotal'     => (int) $i->subtotal,
                    'thumbnail'    => $i->relationLoaded('product') && $i->product
                        ? self::image($i->product->thumbnail, $request) : null,
                ];
            })->all();
        }

        return $data;
    }

    public static function address($a): array
    {
        return [
            'id'             => $a->id,
            'label'          => $a->label,
            'receiver_name'  => $a->receiver_name,
            'receiver_phone' => $a->receiver_phone,
            'postcode'       => $a->postcode,
            'address1'       => $a->address1,
            'address2'       => $a->address2,
            'is_default'     => (bool) $a->is_default,
        ];
    }

    public static function coupon($coupon, int $subtotal = 0): array
    {
        return [
            'id'    => $coupon->id,
            'code'  => $coupon->code,
            'name'  => $coupon->name,
            'type'  => $coupon->type,
            'type_label' => $coupon->typeLabel(),
            'value' => (int) $coupon->value,
            'min_order_amount' => (int) $coupon->min_order_amount,
            'max_discount' => $coupon->max_discount ? (int) $coupon->max_discount : null,
            'ends_at' => $coupon->ends_at?->format('Y-m-d'),
            'discount' => $subtotal > 0 ? $coupon->discountFor($subtotal) : null,
        ];
    }
}
