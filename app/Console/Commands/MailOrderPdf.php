<?php

namespace App\Console\Commands;

use App\Jobs\SendOrderPdfMail;
use App\Models\Order;
use App\Support\OrderPdf;
use Illuminate\Console\Command;

/** 주문서 PDF — 파일로 저장(미리보기)하거나 메일 재발송 */
class MailOrderPdf extends Command
{
    protected $signature = 'orders:pdf {order_no : 주문번호}
        {--save= : 이 경로에 PDF 저장만 하고 메일은 보내지 않음}
        {--to=* : 받는 주소(기본: config site.order_mail_to)}';
    protected $description = '주문서 PDF 저장 또는 메일 (재)발송';

    public function handle(): int
    {
        $order = Order::where('order_no', $this->argument('order_no'))->first();
        if (! $order) {
            $this->error('주문 없음: '.$this->argument('order_no'));
            return 1;
        }

        if ($path = $this->option('save')) {
            file_put_contents($path, OrderPdf::render($order));
            $this->info("저장: {$path}");
            return 0;
        }

        $to = $this->option('to') ?: (array) config('site.order_mail_to');
        (new SendOrderPdfMail($order->id, $to))->handle();
        $this->info('발송: '.implode(', ', $to).' (실패 시 로그 확인)');

        return 0;
    }
}
