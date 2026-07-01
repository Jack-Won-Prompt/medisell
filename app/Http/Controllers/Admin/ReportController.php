<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /** 매출로 집계할 주문 상태 */
    private array $paidStatuses = ['paid', 'preparing', 'shipped', 'done'];

    public function sales(Request $request)
    {
        $to = $request->filled('to') ? Carbon::parse($request->get('to'))->endOfDay() : Carbon::today()->endOfDay();
        $from = $request->filled('from') ? Carbon::parse($request->get('from'))->startOfDay() : Carbon::today()->subDays(29)->startOfDay();

        $base = Order::whereBetween('created_at', [$from, $to]);
        $paid = (clone $base)->whereIn('status', $this->paidStatuses);

        // 요약
        $summary = [
            'sales'   => (clone $paid)->sum('total'),
            'orders'  => (clone $paid)->count(),
            'allOrders' => (clone $base)->count(),
            'cancelled' => (clone $base)->where('status', 'cancelled')->count(),
        ];
        $summary['avg'] = $summary['orders'] ? (int) round($summary['sales'] / $summary['orders']) : 0;

        // 일별 매출
        $daily = (clone $paid)
            ->selectRaw('DATE(created_at) d, COUNT(*) cnt, SUM(total) amt')
            ->groupBy('d')->orderBy('d')->get()
            ->keyBy(fn ($r) => $r->d);

        // 날짜축 채우기
        $series = [];
        for ($day = $from->copy(); $day <= $to; $day->addDay()) {
            $key = $day->format('Y-m-d');
            $series[] = [
                'date' => $key,
                'cnt'  => (int) ($daily[$key]->cnt ?? 0),
                'amt'  => (int) ($daily[$key]->amt ?? 0),
            ];
        }
        $maxAmt = collect($series)->max('amt') ?: 1;

        // 인기 상품 TOP 10 (매출 기준)
        $topProducts = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereBetween('orders.created_at', [$from, $to])
            ->whereIn('orders.status', $this->paidStatuses)
            ->selectRaw('order_items.product_name, SUM(order_items.quantity) qty, SUM(order_items.subtotal) amt')
            ->groupBy('order_items.product_name')
            ->orderByDesc('amt')->limit(10)->get();

        // 상태 분포
        $statusDist = (clone $base)->selectRaw('status, COUNT(*) cnt')->groupBy('status')->pluck('cnt', 'status');

        return view('admin.reports.sales', [
            'from' => $from->format('Y-m-d'),
            'to'   => $to->format('Y-m-d'),
            'summary' => $summary,
            'series'  => $series,
            'maxAmt'  => $maxAmt,
            'topProducts' => $topProducts,
            'statusDist'  => $statusDist,
            'statuses'    => Order::STATUSES,
        ]);
    }
}
