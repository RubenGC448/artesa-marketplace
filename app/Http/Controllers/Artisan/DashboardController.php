<?php

namespace App\Http\Controllers\Artisan;

use App\Http\Controllers\Controller;
use App\Models\OrderShop;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $shop = $request->user()->shop;

        return view('artisan.dashboard', [
            'shop' => $shop,
            'productsCount' => $shop?->products()->count() ?? 0,
            'pendingSuborders' => $shop
                ? $shop->orderShops()->whereIn('status', [OrderShop::STATUS_PENDING, OrderShop::STATUS_PREPARING])->count()
                : 0,
            'lowStock' => $shop
                ? $shop->products()->active()->lowStock(config('artesa.low_stock_threshold'))->get()
                : collect(),
        ]);
    }
}
