<?php

namespace App\Support;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

/**
 * 주문서 PDF.
 * 한글은 dompdf 기본 글꼴에 없으므로 resources/fonts 의 나눔고딕(OFL)을 @font-face 로 쓴다.
 * 글꼴 메트릭 캐시는 storage/fonts 에 생긴다(웹서버 계정이 쓸 수 있어야 함).
 */
class OrderPdf
{
    public static function render(Order $order): string
    {
        $order->loadMissing(['items.product', 'user']);

        $fontCache = storage_path('fonts');
        if (! is_dir($fontCache)) {
            @mkdir($fontCache, 0775, true);
        }

        return Pdf::setOptions([
            'fontDir'         => $fontCache,
            'fontCache'       => $fontCache,
            'chroot'          => [base_path('resources/fonts'), public_path()],
            'isRemoteEnabled' => false,
            'defaultFont'     => 'NanumGothic',
        ])->setPaper('a4')->loadView('pdf.order', [
            'order'    => $order,
            'site'     => config('site'),
            'fontPath' => str_replace('\\', '/', base_path('resources/fonts')),
            'payLabel' => self::payLabel($order),
        ])->output();
    }

    public static function filename(Order $order): string
    {
        return "주문서_{$order->order_no}.pdf";
    }

    public static function payLabel(Order $order): string
    {
        return match ($order->payment_method) {
            'toss', 'portone' => '온라인결제('.($order->pay_method ?: '카드/가상계좌').')',
            default           => '무통장입금'.($order->depositor ? " · 입금자 {$order->depositor}" : ''),
        };
    }
}
