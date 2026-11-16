<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
use App\Models\Shipment;

class DashboardController extends Controller
{
    public function __invoke()
    {
        return view('courier.dashboard', [
            'pending' => Shipment::where('status', 'pending')->count(),
            'inTransit' => Shipment::where('status', 'in_transit')->count(),
        ]);
    }
}
