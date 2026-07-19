<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgentCashback;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * 관리자 — 구매 대행자 캐쉬백 정산.
 * 적립대기(pending) 건을 확인하고 정산완료(paid) 처리.
 */
class AgentCashbackController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'pending');
        $agentId = $request->integer('agent_id') ?: null;

        $q = AgentCashback::with(['agent', 'order'])->latest();
        if (in_array($status, ['pending', 'paid', 'cancelled'], true)) {
            $q->where('status', $status);
        }
        if ($agentId) {
            $q->where('agent_id', $agentId);
        }

        // 대행자별 요약 (미정산 합계)
        $agents = User::where('is_agent', true)->orderBy('name')->get()
            ->map(function ($a) {
                $a->pending_sum = (int) AgentCashback::where('agent_id', $a->id)->where('status', 'pending')->sum('amount');
                $a->paid_sum = (int) AgentCashback::where('agent_id', $a->id)->where('status', 'paid')->sum('amount');

                return $a;
            });

        return view('admin.cashbacks.index', [
            'cashbacks' => $q->paginate(30)->withQueryString(),
            'agents'    => $agents,
            'status'    => $status,
            'agentId'   => $agentId,
        ]);
    }

    /** 단건 정산완료 */
    public function settle(Request $request, AgentCashback $cashback)
    {
        if ($cashback->status === 'pending') {
            $cashback->update(['status' => 'paid', 'paid_at' => now()]);
        }

        return back()->with('ok', '캐쉬백을 정산완료 처리했습니다.');
    }

    /** 대행자 미정산 일괄 정산 */
    public function settleAgent(Request $request, User $user)
    {
        $n = AgentCashback::where('agent_id', $user->id)->where('status', 'pending')
            ->update(['status' => 'paid', 'paid_at' => now()]);

        return back()->with('ok', "{$user->name} 대행자 미정산 {$n}건을 정산완료 처리했습니다.");
    }
}
