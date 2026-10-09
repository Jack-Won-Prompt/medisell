<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * 관리자 › 가격 일괄 관리 — 상품 목록에서 정가·병원 회원 할인가(member_price)·재고를 바로 고친다.
 * (korsafety 의 협력사가 일괄 편집 방식) 바뀐 칸만 저장하고, CSV 로 내려받아 고친 뒤 올릴 수도 있다.
 */
class ProductPriceController extends Controller
{
    private const SORTS = [
        'name'         => ['name', 'asc'],
        'price_desc'   => ['price', 'desc'],
        'price_asc'    => ['price', 'asc'],
        'member_desc'  => ['member_price', 'desc'],
        'member_asc'   => ['member_price', 'asc'],
        'stock_asc'    => ['stock', 'asc'],
        'updated'      => ['updated_at', 'desc'],
    ];

    public function index(Request $request)
    {
        $f = $this->filters($request);
        $perPage = in_array((int) $request->get('per_page'), [20, 50, 100, 200], true) ? (int) $request->get('per_page') : 50;
        [$col, $dir] = self::SORTS[$f['sort']] ?? self::SORTS['name'];

        $products = $this->query($f)->with('category')->orderBy($col, $dir)->orderBy('id')
            ->paginate($perPage)->withQueryString();

        return view('admin.product-prices.index', [
            'products'   => $products,
            'f'          => $f,
            'perPage'    => $perPage,
            'categories' => Category::whereNull('parent_id')->with('children')->orderBy('sort_order')->get(),
            'stats'      => [
                'total'   => Product::count(),
                'withMem' => Product::where('member_price', '>', 0)->count(),
                'noPrice' => Product::where('price', '<=', 0)->count(),
            ],
        ]);
    }

    /** 바뀐 칸만 저장 — rows[상품ID][price|member_price|stock|is_active] */
    public function save(Request $request)
    {
        $data = $request->validate([
            'rows'                 => ['required', 'array', 'max:500'],
            'rows.*.price'         => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'rows.*.member_price'  => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'rows.*.stock'         => ['nullable', 'integer', 'min:0', 'max:10000000'],
            'rows.*.is_active'     => ['nullable', 'boolean'],
        ], [
            'rows.*.*.integer' => '가격·재고는 숫자로 입력해 주세요.',
            'rows.*.*.min'     => '가격·재고는 0 이상이어야 합니다.',
        ]);

        $changed = 0;
        $products = Product::whereIn('id', array_keys($data['rows']))->get()->keyBy('id');
        foreach ($data['rows'] as $id => $row) {
            $p = $products->get((int) $id);
            if (! $p) {
                continue;
            }
            $p->price = (int) ($row['price'] ?? $p->price);
            // 병원 회원 할인가: 비우면 해제(null = 정가로 판매)
            $p->member_price = isset($row['member_price']) && $row['member_price'] !== '' ? (int) $row['member_price'] : null;
            if (array_key_exists('stock', $row) && $row['stock'] !== null) {
                $p->stock = (int) $row['stock'];
            }
            $p->is_active = ! empty($row['is_active']);
            if ($p->isDirty()) {
                $p->save();
                $changed++;
            }
        }

        return back()->with('ok', $changed ? "{$changed}개 상품의 가격·재고를 저장했습니다." : '바뀐 내용이 없습니다.');
    }

    /** 현재 필터 그대로 CSV 내려받기 (엑셀에서 열 수 있게 BOM) */
    public function export(Request $request)
    {
        $f = $this->filters($request);
        $rows = $this->query($f)->with('category')->orderBy('name')->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['상품ID', '상품코드', '상품명', '규격', '단위', '카테고리', '정가', '병원 회원 할인가', '재고', '판매(1/0)']);
            foreach ($rows as $p) {
                fputcsv($out, [$p->id, $p->code, $p->name, $p->spec, $p->unit, $p->category?->name, $p->price, $p->member_price, $p->stock, $p->is_active ? 1 : 0]);
            }
            fclose($out);
        }, '상품가격_'.now()->format('Ymd_Hi').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * CSV 올리기 — 상품ID 로 찾아 정가·병원 회원 할인가·재고·판매만 고친다(새 상품은 만들지 않음).
     * 엑셀이 저장한 CP949(EUC-KR) 파일도 읽는다. 할인가 칸을 비우면 할인가 해제.
     */
    public function import(Request $request)
    {
        $request->validate(['file' => ['required', 'file', 'mimes:csv,txt', 'max:5120']]);
        $raw = file_get_contents($request->file('file')->getRealPath());
        if (! mb_check_encoding($raw, 'UTF-8')) {
            $raw = mb_convert_encoding($raw, 'UTF-8', 'CP949');
        }
        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);

        $updated = 0;
        $skipped = 0;
        $fh = fopen('php://temp', 'r+');
        fwrite($fh, $raw);
        rewind($fh);
        while (($r = fgetcsv($fh)) !== false) {
            if (! isset($r[0]) || ! ctype_digit(trim($r[0]))) {
                continue;   // 머리글·빈 줄
            }
            $p = Product::find((int) $r[0]);
            if (! $p) {
                $skipped++;
                continue;
            }
            $num = fn ($v) => ($v = preg_replace('/[^\d]/', '', (string) $v)) === '' ? null : (int) $v;
            if (($v = $num($r[6] ?? '')) !== null) {
                $p->price = $v;
            }
            $p->member_price = $num($r[7] ?? '');
            if (($v = $num($r[8] ?? '')) !== null) {
                $p->stock = $v;
            }
            if (isset($r[9]) && trim($r[9]) !== '') {
                $p->is_active = trim($r[9]) === '1';
            }
            if ($p->isDirty()) {
                $p->save();
                $updated++;
            }
        }
        fclose($fh);

        return back()->with('ok', "CSV 반영: {$updated}개 상품 수정".($skipped ? ", 없는 상품ID {$skipped}건 건너뜀" : '').'.');
    }

    private function filters(Request $request): array
    {
        return [
            'q'      => trim((string) $request->get('q', '')),
            'cat'    => $request->integer('cat') ?: null,
            'state'  => in_array($request->get('state'), ['on', 'off'], true) ? $request->get('state') : '',
            'member' => in_array($request->get('member'), ['set', 'unset'], true) ? $request->get('member') : '',
            'sort'   => array_key_exists((string) $request->get('sort'), self::SORTS) ? (string) $request->get('sort') : 'name',
        ];
    }

    private function query(array $f)
    {
        return Product::query()
            ->when($f['q'] !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%{$f['q']}%")
                ->orWhere('code', 'like', "%{$f['q']}%")->orWhere('maker', 'like', "%{$f['q']}%")))
            ->when($f['cat'], function ($w) use ($f) {
                $c = Category::find($f['cat']);
                $w->whereIn('category_id', $c ? $c->descendantIds() : [$f['cat']]);
            })
            ->when($f['state'] === 'on', fn ($w) => $w->where('is_active', true))
            ->when($f['state'] === 'off', fn ($w) => $w->where('is_active', false))
            ->when($f['member'] === 'set', fn ($w) => $w->where('member_price', '>', 0))
            ->when($f['member'] === 'unset', fn ($w) => $w->where(fn ($x) => $x->whereNull('member_price')->orWhere('member_price', 0)));
    }
}
