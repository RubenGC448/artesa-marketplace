<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        return view('customer.dashboard', [
            'recentOrders' => Order::visibleTo($user)->latest()->take(5)->get(),
            'addressesCount' => $user->addresses()->count(),
        ]);
    }
}
