<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    /** 공통 CSV 스트림 다운로드 (UTF-8 BOM → 엑셀 한글 호환) */
    private function csv(string $filename, array $header, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($header, $rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // BOM
            fputcsv($out, $header);
            foreach ($rows as $r) {
                fputcsv($out, $r);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function orders(Request $request): StreamedResponse
    {
        $query = Order::with('user')->latest();
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $rows = $query->get()->map(fn ($o) => [
            $o->order_no,
            $o->created_at->format('Y-m-d H:i'),
            $o->receiver_name,
            $o->receiver_phone,
            $o->user?->email,
            $o->payment_method === 'toss' ? '토스('.($o->pay_method ?? '').')' : '무통장',
            $o->subtotal,
            $o->shipping_fee,
            $o->point_used,
            $o->total,
            $o->statusLabel(),
            $o->courier,
            $o->tracking_no,
            optional($o->paid_at)->format('Y-m-d H:i'),
            trim(($o->postcode ? '('.$o->postcode.') ' : '').$o->address1.' '.$o->address2),
        ]);

        return $this->csv('orders_'.now()->format('Ymd_His').'.csv',
            ['주문번호', '주문일시', '받는분', '연락처', '회원이메일', '결제수단', '상품금액', '배송비', '적립금사용', '결제금액', '상태', '택배사', '송장번호', '결제일시', '배송지'],
            $rows);
    }

    public function products(Request $request): StreamedResponse
    {
        $rows = Product::with(['category', 'brand'])->orderBy('id')->get()->map(fn ($p) => [
            $p->id,
            $p->code,
            $p->name,
            $p->category?->name,
            $p->brand?->name,
            $p->unit,
            $p->price,
            $p->member_price,
            $p->stock,
            $p->is_active ? '판매중' : '중지',
        ]);

        return $this->csv('products_'.now()->format('Ymd_His').'.csv',
            ['ID', '상품코드', '상품명', '카테고리', '브랜드', '단위', '정가', '기본병원가', '재고', '상태'],
            $rows);
    }

    public function users(Request $request): StreamedResponse
    {
        $rows = User::withCount('orders')->orderBy('id')->get()->map(fn ($u) => [
            $u->id,
            $u->name,
            $u->email,
            $u->phone,
            $u->member_type === 'business' ? '병원' : '일반',
            $u->company_name,
            $u->biz_no,
            match ($u->biz_status) { 'approved' => '승인', 'pending' => '대기', 'rejected' => '거절', default => '-' },
            $u->grade,
            $u->point,
            $u->orders_count,
            $u->created_at->format('Y-m-d'),
        ]);

        return $this->csv('users_'.now()->format('Ymd_His').'.csv',
            ['ID', '이름', '이메일', '연락처', '구분', '병원/상호', '사업자번호', '승인', '등급', '적립금', '주문수', '가입일'],
            $rows);
    }
}
