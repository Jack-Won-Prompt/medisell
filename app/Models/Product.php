<?php

namespace App\Models;

use App\Models\Concerns\HasImagePaths;
use App\Models\Concerns\HasUniqueSlug;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasImagePaths, HasUniqueSlug;

    protected $fillable = [
        'category_id', 'brand_id', 'name', 'slug', 'code', 'group_key', 'unit', 'maker',
        'summary', 'description', 'spec', 'price', 'cost', 'member_price', 'tax_type', 'stock',
        'thumbnail', 'images', 'is_active', 'is_featured', 'is_best', 'is_new',
        'badge', 'view_count', 'sort_order',
    ];

    protected $casts = [
        // images 는 아래 접근자에서 직접 인코딩/디코딩한다 (array 캐스팅과 중복되지 않게 제외)
        'is_active'   => 'boolean',
        'is_featured' => 'boolean',
        'is_best'     => 'boolean',
        'is_new'      => 'boolean',
    ];

    /** 대표 이미지 — 저장은 상대경로, 출력은 현재 환경 절대 URL */
    protected function thumbnail(): Attribute
    {
        return Attribute::make(
            get: fn ($v) => self::toImageUrl($v),
            set: fn ($v) => self::toRelativeImagePath($v),
        );
    }

    /** 상세설명 HTML 안의 이미지 주소도 현재 환경으로 맞춘다 */
    protected function description(): Attribute
    {
        return Attribute::get(fn ($v) => self::rewriteHtmlImageUrls($v));
    }

    /**
     * 추가 이미지는 항상 문자열 배열로 돌려준다.
     *
     * yk 임포터가 json_encode() 한 값을 array 캐스팅 컬럼에 넣어 이중 인코딩된 행이 있었다.
     * 그 경우 캐스팅 결과가 배열이 아니라 JSON 문자열이라, 갤러리에서
     * collect([...])->merge($문자열) 이 문자열을 원소 하나로 감싸 <img src="[&quot;http:...&quot;]">
     * 같은 깨진 이미지가 그려졌다. 읽기 시점에 방어해 웹·API 모두 한 번에 막는다.
     */
    protected function images(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $v = is_string($value) ? json_decode($value, true) : $value;
                if (is_string($v)) {                 // 이중 인코딩
                    $v = json_decode($v, true);
                }
                if (! is_array($v)) {
                    return [];
                }

                return array_values(array_filter(array_map(
                    fn ($u) => is_string($u) ? self::toImageUrl($u) : null,
                    $v,
                )));
            },
            set: function ($value) {
                $v = is_string($value) ? json_decode($value, true) : $value;
                if (is_string($v)) {
                    $v = json_decode($v, true);
                }
                $list = array_values(array_filter(array_map(
                    fn ($u) => is_string($u) ? self::toRelativeImagePath($u) : null,
                    is_array($v) ? $v : [],
                )));

                return json_encode($list, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            },
        );
    }

    protected function slugPrefix(): string
    {
        return 'item';
    }

    /** 상품명이 한글뿐이면 상품코드를 쓴다 — 임포트 커맨드와 같은 규칙(item-{코드}) */
    protected function slugBase(): string
    {
        $base = (string) Str::slug((string) $this->name);
        if ($base === '' && filled($this->code)) {
            $base = 'item-'.Str::lower(Str::slug((string) $this->code));
        }

        return $base;
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }

    /** 규격/사이즈 변형(같은 group_key) 활성 상품들 — 자기 자신 우선 */
    public function variants()
    {
        if (empty($this->group_key)) {
            return collect([$this]);
        }

        return static::where('group_key', $this->group_key)
            ->where('is_active', true)
            ->orderByRaw('CASE WHEN id = ? THEN 0 ELSE 1 END', [$this->id])
            ->orderBy('price')->orderBy('name')
            ->get();
    }

    public function hospitalPrices()
    {
        return $this->hasMany(HospitalPrice::class);
    }

    /**
     * 회원에 따른 실제 판매가.
     * 우선순위: 병원 전용가(병원별 계약가) → 기본 병원가(member_price) → 정가
     */
    public function priceFor(?User $user): int
    {
        if ($user && $user->isApprovedBusiness()) {
            // 1) 회원 개별 전용가 (가장 우선)
            $map = $user->priceMap();
            if (isset($map[$this->id])) {
                return (int) $map[$this->id];
            }
            // 2) 소속 거래처 전용가 (거래처 그룹 공유)
            $accMap = $user->accountPriceMap();
            if (isset($accMap[$this->id])) {
                return (int) $accMap[$this->id];
            }
            // 3) 거래처 등급별 일괄 할인율
            $rate = $user->accountDiscountRate();
            if ($rate > 0 && $this->price > 0) {
                return (int) round($this->price * (1 - $rate / 100));
            }
            // 4) 공통 회원가
            if ($this->member_price) {
                return $this->member_price;
            }
        }

        return $this->price;
    }

    /** 해당 회원에게 병원 전용가(정가보다 낮은 가격)가 적용되는지 */
    public function hasSpecialPriceFor(?User $user): bool
    {
        return $user && $user->isApprovedBusiness() && $this->priceFor($user) < $this->price;
    }

    /** 정가 대비 할인율(%) — 주어진 판매가 기준 */
    public function discountRateFor(int $sell): int
    {
        if ($this->price > 0 && $sell < $this->price) {
            return (int) round(($this->price - $sell) / $this->price * 100);
        }

        return 0;
    }

    /** 기본 병원가 기준 할인율(%) — 비로그인/일반 표시용 */
    public function discountRate(): int
    {
        if ($this->member_price && $this->price > 0 && $this->member_price < $this->price) {
            return (int) round(($this->price - $this->member_price) / $this->price * 100);
        }

        return 0;
    }
}
