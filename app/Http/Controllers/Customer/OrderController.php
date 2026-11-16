<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Orders\OrderCancellationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        return view('customer.orders.index', [
            'orders' => Order::visibleTo($request->user())->with('orderShops.shop')->latest()->paginate(10),
        ]);
    }

    public function show(Order $order)
    {
        Gate::authorize('view', $order);

        $order->load(['orderShops.shop', 'orderShops.orderProducts', 'orderShops.latestShipment', 'statusHistory']);

        return view('customer.orders.show', compact('order'));
    }

    public function cancel(Order $order, OrderCancellationService $cancellation)
    {
        Gate::authorize('cancel', $order);

        $cancellation->cancelOrder($order);

        return redirect()->route('customer.orders.show', $order)
            ->with('status', __('Pedido :number cancelado. El stock se ha devuelto a los talleres.', ['number' => $order->order_number]));
    }
}
