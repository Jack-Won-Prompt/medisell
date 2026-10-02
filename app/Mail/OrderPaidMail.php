<?php

namespace App\Mail;

use App\Models\Order;
use App\Support\OrderPdf;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** 결제 완료 주문서 — 주문서 PDF 첨부 (수신처: config('site.order_mail_to')) */
class OrderPaidMail extends Mailable
{
    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        $buyer = $this->order->buyer_hospital ?: ($this->order->user?->company_name ?: ($this->order->user?->name ?? '비회원'));

        return new Envelope(
            subject: "[메디셀] 결제완료 주문서 {$this->order->order_no} · {$buyer} · ".number_format($this->order->total).'원',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.order-paid', with: [
            'payLabel' => OrderPdf::payLabel($this->order),
        ]);
    }

    public function attachments(): array
    {
        return [
            Attachment::fromData(fn () => OrderPdf::render($this->order), OrderPdf::filename($this->order))
                ->withMime('application/pdf'),
        ];
    }
}
