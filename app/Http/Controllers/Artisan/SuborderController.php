<?php

namespace App\Http\Controllers\Artisan;

use App\Http\Controllers\Controller;
use App\Http\Requests\Artisan\UpdateSuborderStatusRequest;
use App\Models\OrderShop;
use App\Services\Orders\FulfillmentService;
use App\Services\Orders\OrderCancellationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Subpedidos del taller: el artesano solo ve y gestiona la parte de su taller.
 */
class SuborderController extends Controller
{
    public function index(Request $request)
    {
        $shop = $request->user()->shop;

        return view('artisan.orders.index', [
            'suborders' => $shop
                ? $shop->orderShops()->with(['order', 'orderProducts'])->latest()->paginate(15)
                : collect(),
            'statuses' => [
                OrderShop::STATUS_PREPARING => __('En preparación'),
                OrderShop::STATUS_READY => __('Listo para envío'),
                OrderShop::STATUS_SHIPPED => __('Enviado'),
            ],
        ]);
    }

    public function updateStatus(UpdateSuborderStatusRequest $request, OrderShop $orderShop, FulfillmentService $fulfillment)
    {
        $fulfillment->updateSuborderStatus($orderShop, $request->validated('status'));

        return back()->with('status', __('Subpedido actualizado.'));
    }

    public function cancel(OrderShop $orderShop, OrderCancellationService $cancellation)
    {
        Gate::authorize('manageSuborder', $orderShop);

        $cancellation->cancelSuborder($orderShop);

        return back()->with('status', __('Has cancelado la parte de tu taller en el pedido :number.', ['number' => $orderShop->order->order_number]));
    }
}
