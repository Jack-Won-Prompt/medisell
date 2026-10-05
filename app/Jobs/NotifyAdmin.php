<?php

namespace App\Jobs;

use App\Mail\AdminAlertMail;
use App\Models\ChatMessage;
use App\Models\Inquiry;
use App\Models\Order;
use App\Models\User;
use App\Services\SmsNotifier;
use App\Support\OrderPdf;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * 관리자 알림 — 이메일(site.admin_notify_emails) + 문자(site.admin_notify_phones).
 * event: signup(회원 id) / inquiry(문의 id) / chat(상담 메시지 id) / order(주문 id)
 * 응답을 보낸 뒤 실행되고, 메일·문자 실패는 report() 로만 남겨 고객 동작을 막지 않는다.
 * 문자는 짧게(90바이트 이하 SMS) — 자세한 내용은 메일과 관리자 화면에서.
 */
class NotifyAdmin
{
    use Dispatchable;

    public function __construct(public string $event, public int $id, public string $channel = '웹') {}

    public function handle(SmsNotifier $sms): void
    {
        $alert = match ($this->event) {
            'signup'  => $this->signup(),
            'inquiry' => $this->inquiry(),
            'chat'    => $this->chat(),
            'order'   => $this->order(),
            'paid'    => $this->order(true),
            default   => null,
        };
        if (! $alert) {
            return;
        }
        [$subject, $headline, $rows, $url, $body, $smsText, $orderId, $userId] = $alert + [4 => null, 5 => '', 6 => null, 7 => null];

        $to = array_values(array_filter((array) config('site.admin_notify_emails', [])));
        if ($to) {
            try {
                Mail::to($to)->send(new AdminAlertMail($subject, $headline, $rows, $url, $body));
            } catch (\Throwable $e) {
                report($e);
            }
        }
        try {
            $sms->admin($this->event, $smsText, $orderId, $userId);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function signup(): ?array
    {
        $u = User::find($this->id);
        if (! $u) {
            return null;
        }
        $biz = $u->member_type === 'business';
        $who = $u->company_name ? "{$u->company_name} {$u->name}" : $u->name;
        $rows = ['구분' => ($biz ? '병원 회원 (승인 대기)' : '일반 회원')." · {$this->channel} 가입", '이름' => $u->name, '이메일' => $u->email, '연락처' => $u->phone];
        if ($biz) {
            $rows += ['병원/상호' => $u->company_name, '사업자번호' => $u->biz_no, '종별' => $u->biz_type, '요양기관기호' => $u->care_code,
                '사업자등록증' => $u->biz_cert_path ? '첨부됨 (관리자 화면에서 확인)' : '미첨부'];
        }

        return [
            '[메디셀] 신규 회원가입 · '.($biz ? '병원 회원(승인 대기)' : '일반 회원').' · '.$who,
            '새 회원이 가입했습니다.'.($biz ? ' 병원 회원 — 서류 확인 후 승인이 필요합니다.' : ''),
            $rows,
            route('admin.users.show', $u),
            null,
            '[메디셀] 회원가입 '.Str::limit($who, 16, '').($biz ? ' (병원 승인대기)' : ''),
            null, $u->id,
        ];
    }

    private function inquiry(): ?array
    {
        $q = Inquiry::find($this->id);
        if (! $q) {
            return null;
        }

        return [
            "[메디셀] 새 {$q->typeLabel()} · {$q->name} · ".Str::limit($q->subject, 40),
            "새 {$q->typeLabel()}가 접수되었습니다.",
            ['구분' => $q->typeLabel(), '이름' => $q->name, '연락처' => $q->phone, '이메일' => $q->email, '제목' => $q->subject, '접수' => $q->created_at?->format('Y-m-d H:i')],
            route('admin.inquiries.show', $q),
            Str::limit((string) $q->body, 1500),
            "[메디셀] {$q->typeLabel()} ".Str::limit($q->name, 8, '').': '.Str::limit($q->subject, 18, '…'),
            null, $q->user_id,
        ];
    }

    private function chat(): ?array
    {
        $m = ChatMessage::with('room.user')->find($this->id);
        $room = $m?->room;
        if (! $room) {
            return null;
        }
        $name = $room->user?->name ?: $room->guest_name ?: '비회원';

        return [
            "[메디셀] 실시간 상담 · {$name}",
            '실시간 상담 메시지가 도착했습니다.',
            ['고객' => $name.($room->user_id ? ' (회원)' : ' (비회원)'), '연락처' => $room->guest_phone ?: $room->user?->phone, '시각' => $m->created_at?->format('Y-m-d H:i')],
            route('admin.chat.show', $room),
            Str::limit((string) $m->body, 1000),
            "[메디셀] 상담 ".Str::limit($name, 8, '').': '.Str::limit((string) $m->body, 20, '…'),
            null, $room->user_id,
        ];
    }

    /** 주문 접수(order) 또는 결제 완료(paid) */
    private function order(bool $paid = false): ?array
    {
        $o = Order::with('items', 'user')->find($this->id);
        if (! $o) {
            return null;
        }
        $buyer = $o->buyer_hospital ?: ($o->user?->company_name ?: ($o->user?->name ?? $o->receiver_name));
        $first = $o->items->first()?->product_name ?? '';
        $more = $o->items->count() - 1;

        return [
            ($paid ? '[메디셀] 결제 완료 ' : '[메디셀] 신규 주문 ')."{$o->order_no} · {$buyer} · ".number_format($o->total).'원',
            $paid
                ? '결제(입금)가 확인되었습니다. 상품 준비를 시작해 주세요.'
                : '새 주문이 들어왔습니다.'.($o->payment_method === 'bank' ? ' (무통장 — 입금 대기)' : ' (온라인 결제 진행 중)'),
            ['주문번호' => $o->order_no, '주문자' => $buyer.($o->user ? " · {$o->user->email}" : ''), '결제' => OrderPdf::payLabel($o),
                '금액' => number_format($o->total).'원', '상품' => $first.($more > 0 ? " 외 {$more}건" : ''),
                '배송지' => "{$o->receiver_name} · {$o->receiver_phone} · {$o->address1} {$o->address2}"],
            route('admin.orders.show', $o),
            null,
            ($paid ? '[메디셀] 결제완료 ' : '[메디셀] 주문 ')."{$o->order_no} ".number_format($o->total).'원 '.Str::limit($buyer, 10, ''),
            $o->id, $o->user_id,
        ];
    }
}
