<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PopbillTest;
use App\Services\Popbill\PopbillTestService;
use Illuminate\Http\Request;

/** 관리자 › 팝빌 테스트 — 브라우저에서 문자·현금영수증·세금계산서 실발송/실발행·취소 */
class PopbillTestController extends Controller
{
    public function __construct(private PopbillTestService $service) {}

    public function index()
    {
        return view('admin.popbill.index', [
            'status' => $this->service->status(),
            'tests'  => PopbillTest::latest()->limit(50)->get(),
            'max'    => PopbillTestService::MAX_AMOUNT,
        ]);
    }

    public function sms(Request $request)
    {
        $data = $request->validate([
            'to'   => ['required', 'string', 'regex:/^[\d\-\s]{10,14}$/'],
            'text' => ['required', 'string', 'max:1000'],
        ]);
        $t = $this->service->sendSms($request->user()->id, $data['to'], $data['text']);

        return back()->with($t->status === 'failed' ? 'error' : 'ok',
            $t->status === 'failed' ? '문자 실패: '.$t->error_message : "문자를 보냈습니다. (접수번호 {$t->confirm_num})");
    }

    public function cashbill(Request $request)
    {
        $data = $request->validate([
            'amount'   => ['required', 'integer', 'min:100', 'max:'.PopbillTestService::MAX_AMOUNT],
            'usage'    => ['required', 'in:소득공제용,지출증빙용'],
            'identity' => ['required', 'string', 'regex:/^[\d\-\s]{10,15}$/'],
        ]);
        $t = $this->service->issueCashbill($request->user()->id, (int) $data['amount'], $data['usage'], $data['identity']);

        return back()->with($t->status === 'failed' ? 'error' : 'ok',
            $t->status === 'failed' ? '현금영수증 실패: '.$t->error_message : "현금영수증을 발행했습니다. (승인번호 {$t->confirm_num}) — 테스트 후 [취소]를 눌러 주세요.");
    }

    public function taxinvoice(Request $request)
    {
        $data = $request->validate([
            'amount'    => ['required', 'integer', 'min:100', 'max:'.PopbillTestService::MAX_AMOUNT],
            'corp_num'  => ['nullable', 'string', 'regex:/^[\d\-]{10,12}$/'],
            'corp_name' => ['nullable', 'string', 'max:100'],
            'ceo_name'  => ['nullable', 'string', 'max:50'],
            'email'     => ['nullable', 'email', 'max:100'],
        ]);
        $t = $this->service->issueTaxinvoice($request->user()->id, (int) $data['amount'], $data);

        return back()->with($t->status === 'failed' ? 'error' : 'ok',
            $t->status === 'failed' ? '세금계산서 실패: '.$t->error_message : "세금계산서를 발행했습니다. (국세청 승인번호 {$t->confirm_num}) — 오늘 안에 [취소]를 누르면 국세청에 남지 않습니다.");
    }

    public function cancel(PopbillTest $test)
    {
        try {
            $t = $this->service->cancel($test);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with($t->status === 'cancelled' ? 'ok' : 'error',
            $t->status === 'cancelled' ? "{$t->kindLabel()}를 취소했습니다. ({$t->popbill_state})" : ($t->error_message ?? '취소하지 못했습니다.'));
    }
}
