<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('admin.dashboard', [
            'pendingShops' => Shop::pendingVerification()->count(),
            'openDisputes' => Dispute::whereIn('status', ['open', 'in_review'])->count(),
            'ordersByStatus' => Order::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'bestSelling' => Product::bestSelling(now()->subDays(30), now())->with('shop')->take(5)->get(),
        ]);
    }
}
