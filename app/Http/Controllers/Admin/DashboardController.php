<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $todaySales = Order::whereDate('created_at', today())
            ->whereIn('status', ['paid', 'preparing', 'shipped', 'done'])
            ->sum('total');

        return view('admin.dashboard', [
            'stats' => [
                ['label' => '신규 주문(입금대기)', 'value' => Order::where('status', 'pending')->count(), 'icon' => 'cart', 'route' => 'admin.orders.index'],
                ['label' => '병원 승인대기', 'value' => User::where('biz_status', 'pending')->count(), 'icon' => 'user', 'route' => 'admin.users.index'],
                ['label' => '미답변 문의', 'value' => Inquiry::where('status', 'pending')->count(), 'icon' => 'question', 'route' => 'admin.inquiries.index'],
                ['label' => '등록 상품', 'value' => Product::count(), 'icon' => 'box', 'route' => null],
            ],
            'todaySales'    => $todaySales,
            'recentOrders'  => Order::with('user')->latest()->take(8)->get(),
            'recentInquiries' => Inquiry::latest()->take(6)->get(),
        ]);
    }
}
