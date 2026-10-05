<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SmsLog;
use App\Models\User;
use App\Services\Popbill\PopbillMessageService;

/**
 * 고객 안내 문자 — 문구를 만들고 팝빌로 보내고 sms_logs 에 남긴다.
 * 같은 주문·같은 종류는 한 번만 보낸다(시뮬레이트·실패 이력은 다시 보낼 수 있게 제외).
 */
class SmsNotifier
{
    public function __construct(private PopbillMessageService $popbill) {}

    /** 결제(입금) 완료 */
    public function orderPaid(Order $order): ?SmsLog
    {
        return $this->forOrder($order, 'paid',
            "[메디셀] 결제가 확인되었습니다.\n주문번호 {$order->order_no}\n결제금액 ".number_format($order->total)."원\n곧 상품을 준비하겠습니다.");
    }

    /** 배송 시작(송장) */
    public function orderShipped(Order $order): ?SmsLog
    {
        $tracking = trim(($order->courier ?? '').' '.($order->tracking_no ?? ''));

        return $this->forOrder($order, 'shipped',
            "[메디셀] 주문하신 상품이 발송되었습니다.\n주문번호 {$order->order_no}".($tracking !== '' ? "\n송장 {$tracking}" : ''));
    }

    /** 무통장 주문 직후 입금 안내 */
    public function bankGuide(Order $order): ?SmsLog
    {
        if ($order->payment_method !== 'bank') {
            return null;
        }
        $acc = $order->bankAccount();
        $account = $acc ? "{$acc['bank']} {$acc['account']} ({$acc['holder']})" : ($order->bank ?? '');

        return $this->forOrder($order, 'bank_guide',
            "[메디셀] 무통장입금 안내\n주문번호 {$order->order_no}\n입금계좌 {$account}\n입금금액 ".number_format($order->total)."원"
            .($order->depositor ? "\n입금자명 {$order->depositor}" : '')
            ."\n입금이 확인되면 상품을 준비합니다.");
    }

    /** 병원 회원 승인/반려 */
    public function bizResult(User $user): ?SmsLog
    {
        if (! in_array($user->biz_status, ['approved', 'rejected'], true)) {
            return null;
        }
        $name = $user->company_name ?: $user->name;
        [$kind, $text] = $user->biz_status === 'approved'
            ? ['biz_approved', "[메디셀] {$name} 병원 회원 승인이 완료되었습니다. 지금부터 병원 전용가로 구매하실 수 있습니다."]
            : ['biz_rejected', "[메디셀] {$name} 병원 회원 승인이 반려되었습니다. 문의 ".config('site.cs_tel')];

        return $this->send($kind, $this->phoneOf($user->phone), $text, null, $user->id);
    }

    /** 관리자 알림 문자 — config('site.admin_notify_phones') 전원에게 (kind: signup/inquiry/chat/order) */
    public function admin(string $kind, string $text, ?int $orderId = null, ?int $userId = null): void
    {
        foreach ((array) config('site.admin_notify_phones', []) as $phone) {
            $this->send('admin_'.$kind, $this->phoneOf($phone), $text, $orderId, $userId);
        }
    }

    private function forOrder(Order $order, string $kind, string $text): ?SmsLog
    {
        $order->loadMissing('user');
        $already = SmsLog::where('order_id', $order->id)->where('kind', $kind)
            ->whereIn('status', ['sent', 'redirected'])->exists();
        if ($already) {
            return null;
        }

        return $this->send($kind, $order->notifyPhone(), $text, $order->id, $order->user_id);
    }

    private function send(string $kind, ?string $to, string $text, ?int $orderId, ?int $userId): ?SmsLog
    {
        if (! $to) {
            return null;   // 휴대폰 번호 없음 — 보낼 곳이 없다
        }
        $log = ['order_id' => $orderId, 'user_id' => $userId, 'kind' => $kind, 'content' => $text,
            'msg_type' => PopbillMessageService::msgType($text)];

        try {
            $r = $this->popbill->send($to, $text);

            return SmsLog::create($log + [
                'receiver' => $r['receiver'], 'msg_type' => $r['msg_type'],
                'status' => $r['status'], 'receipt_num' => $r['receipt_num'],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return SmsLog::create($log + ['receiver' => $to, 'status' => 'failed', 'error_message' => $e->getMessage()]);
        }
    }

    private function phoneOf(?string $phone): ?string
    {
        $d = preg_replace('/\D/', '', (string) $phone);

        return preg_match('/^01\d{8,9}$/', $d) ? $d : null;
    }
}
