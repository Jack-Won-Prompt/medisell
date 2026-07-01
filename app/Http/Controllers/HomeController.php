<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Notice;
use App\Models\Product;

class HomeController extends Controller
{
    public function index()
    {
        return view('home', [
            'mainBanners' => Banner::where('is_active', true)->where('position', 'main')->orderBy('sort_order')->get(),
            'subBanners'  => Banner::where('is_active', true)->where('position', 'sub')->orderBy('sort_order')->get(),
            'bestProducts'    => Product::active()->where('is_best', true)->latest('view_count')->take(10)->get(),
            'featuredProducts' => Product::active()->where('is_featured', true)->latest()->take(8)->get(),
            'newProducts'     => Product::active()->where('is_new', true)->latest()->take(8)->get(),
            'notices'     => Notice::orderByDesc('is_pinned')->latest('published_at')->take(5)->get(),
            'brands'      => Brand::where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }
}
