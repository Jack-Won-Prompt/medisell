<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\TossPayments;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * 관리자 › 토스 결제 이력 — 토스페이먼츠 거래 내역을 실시간으로 불러와 메디셀 주문과 대사한다. (조회 전용)
 * 취소·환불은 기존 주문 취소 흐름(Order::cancel)에서만 한다.
 */
class TossPaymentController extends Controller
{
    public const STATUS_LABELS = [
        'READY'               => '결제 대기',
        'IN_PROGRESS'         => '인증 완료',
        'WAITING_FOR_DEPOSIT' => '입금 대기',
        'DONE'                => '결제 완료',
        'CANCELED'            => '취소',
        'PARTIAL_CANCELED'    => '부분 취소',
        'ABORTED'             => '승인 실패',
        'EXPIRED'             => '만료',
    ];

    public function __construct(private TossPayments $toss) {}

    public function index(Request $request)
    {
        $data = $request->validate([
            'from'   => ['nullable', 'date'],
            'to'     => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:30'],
            'issue'  => ['nullable', 'boolean'],
            'all'    => ['nullable', 'boolean'],   // 메디셀 주문이 없는 거래(테스트·삭제된 주문)도 보기
            'after'  => ['nullable', 'string', 'max:100'],
        ]);
        $from = Carbon::parse($data['from'] ?? now()->subDays(6)->toDateString())->startOfDay();
        $to = Carbon::parse($data['to'] ?? now()->toDateString())->endOfDay();
        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }
        if ($from->diffInDays($to) > 92) {
            $from = $to->copy()->subDays(92)->startOfDay();
        }

        $res = $this->toss->transactions($from->format('Y-m-d\TH:i:s'), $to->format('Y-m-d\TH:i:s'), $data['after'] ?? null, 100);
        $items = collect($res['error'] ? [] : $res['items']);
        $hasMore = $items->count() >= 100;
        $nextAfter = $hasMore ? $items->last()['transactionKey'] ?? null : null;

        // 메디셀 주문과 대사 (orderId = order_no)
        $orders = Order::whereIn('order_no', $items->pluck('orderId')->filter()->unique())->get()->keyBy('order_no');
        $rows = $items->map(function ($t) use ($orders) {
            $order = $orders->get($t['orderId'] ?? '');

            return $t + ['order' => $order, 'issues' => $this->reconcile($t, $order)];
        });

        // 기본은 메디셀에 주문이 있는 거래만 — 테스트·정리된 주문의 토스 거래는 숨긴다
        $hidden = 0;
        if (empty($data['all'])) {
            $hidden = $rows->whereNull('order')->count();
            $rows = $rows->whereNotNull('order');
            $items = $rows->map(fn ($r) => array_diff_key($r, ['order' => 1, 'issues' => 1]));
        }

        if (! empty($data['status'])) {
            $rows = $rows->where('status', $data['status']);
        }
        if (! empty($data['issue'])) {
            $rows = $rows->filter(fn ($r) => $r['issues']);
        }

        $summary = [
            'count'    => $items->count(),
            'done'     => (int) $items->whereIn('status', ['DONE', 'PARTIAL_CANCELED'])->sum('amount'),
            'canceled' => (int) $items->whereIn('status', ['CANCELED'])->sum('amount'),
            'issues'   => $items->map(fn ($t) => $this->reconcile($t, $orders->get($t['orderId'] ?? '')))->filter()->count(),
        ];

        return view('admin.toss.index', [
            'rows'      => $rows->values(),
            'summary'   => $summary,
            'error'     => $res['error'] ? "[{$res['code']}] {$res['message']}" : null,
            'from'      => $from,
            'to'        => $to,
            'filters'   => $data,
            'nextAfter' => $nextAfter,
            'hidden'    => $hidden,
            'labels'    => self::STATUS_LABELS,
            'testMode'  => (bool) config('services.toss.test_mode'),
        ]);
    }

    public function show(string $paymentKey)
    {
        $p = $this->toss->payment($paymentKey);
        $order = empty($p['error']) ? Order::where('order_no', $p['orderId'] ?? '')->first() : null;

        return view('admin.toss.show', [
            'p'        => $p,
            'order'    => $order,
            'issues'   => empty($p['error']) ? $this->reconcile(['status' => $p['status'] ?? null, 'amount' => $p['totalAmount'] ?? 0], $order) : [],
            'labels'   => self::STATUS_LABELS,
            'testMode' => (bool) config('services.toss.test_mode'),
        ]);
    }

    /** 토스 거래 ↔ 메디셀 주문 불일치 목록 (없으면 빈 배열) */
    private function reconcile(array $t, ?Order $order): array
    {
        $status = $t['status'] ?? null;
        if (! $order) {
            return ['메디셀 주문 없음'];
        }
        $issues = [];
        if (in_array($status, ['DONE', 'PARTIAL_CANCELED'], true)) {
            if ((int) ($t['amount'] ?? 0) !== (int) $order->total && $status === 'DONE') {
                $issues[] = '금액 불일치 (주문 '.number_format($order->total).'원)';
            }
            if (! $order->paid_at && $order->status !== 'cancelled') {
                $issues[] = '주문이 결제완료로 처리되지 않음';
            }
        }
        if ($status === 'CANCELED' && $order->status !== 'cancelled') {
            $issues[] = '토스는 취소, 주문은 '.$order->statusLabel();
        }

        return $issues;
    }
}
