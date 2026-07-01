<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankCollectJob;
use App\Models\BankDeposit;
use App\Models\Order;
use App\Services\Bank\BankDepositService;
use Illuminate\Http\Request;

class BankDepositController extends Controller
{
    public function __construct(private BankDepositService $service) {}

    public function index(Request $request)
    {
        $deposits = BankDeposit::with('matchedOrder')->latest('id')->paginate(30);
        $jobs = BankCollectJob::latest('id')->limit(5)->get();

        // 미매칭 입금건별 후보 대기주문 (입금자명 정규화 일치)
        $pendingOrders = Order::where('status', 'pending')->where('payment_method', 'bank')
            ->whereNotNull('depositor')->get(['id', 'order_no', 'depositor', 'total', 'created_at']);

        return view('admin.bank.index', [
            'deposits' => $deposits,
            'jobs'     => $jobs,
            'pendingOrders' => $pendingOrders,
            'pendingCount'  => $pendingOrders->count(),
            'simulate' => config('popbill.bank.simulate'),
        ]);
    }

    public function collect(Request $request)
    {
        $data = $request->validate([
            's_date' => ['required', 'date'],
            'e_date' => ['required', 'date', 'after_or_equal:s_date'],
        ]);

        try {
            [$job, $count] = $this->service->collect(
                \Illuminate\Support\Carbon::parse($data['s_date'])->format('Ymd'),
                \Illuminate\Support\Carbon::parse($data['e_date'])->format('Ymd'),
            );
            $matched = $this->service->autoMatch();

            return back()->with('ok', "입금내역 {$count}건 수집, {$matched}건 자동확인 처리되었습니다.");
        } catch (\Throwable $e) {
            return back()->with('error', '수집 실패: '.$e->getMessage());
        }
    }

    public function autoMatch()
    {
        $matched = $this->service->autoMatch();

        return back()->with('ok', "{$matched}건 자동 매칭·결제확인되었습니다.");
    }

    /** 수동 매칭 */
    public function match(Request $request, BankDeposit $deposit)
    {
        $data = $request->validate(['order_id' => ['required', 'exists:orders,id']]);
        if ($deposit->isMatched()) {
            return back()->with('error', '이미 매칭된 입금건입니다.');
        }
        $order = Order::findOrFail($data['order_id']);
        $this->service->confirm($deposit, $order);

        return back()->with('ok', "{$order->order_no} 주문이 입금확인 처리되었습니다.");
    }
}
