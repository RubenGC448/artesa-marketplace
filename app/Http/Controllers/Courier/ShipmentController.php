<?php

namespace App\Http\Controllers\Courier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Courier\UpdateShipmentRequest;
use App\Models\Shipment;
use App\Services\Orders\FulfillmentService;

class ShipmentController extends Controller
{
    public function index()
    {
        return view('courier.shipments.index', [
            'shipments' => Shipment::whereIn('status', ['pending', 'in_transit', 'failed'])
                ->with('orderShop.shop', 'orderShop.order')
                ->oldest()
                ->get(),
            'statuses' => [
                'in_transit' => __('En reparto'),
                'delivered' => __('Entregado'),
                'failed' => __('Incidencia en la entrega'),
            ],
        ]);
    }

    public function update(UpdateShipmentRequest $request, Shipment $shipment, FulfillmentService $fulfillment)
    {
        $fulfillment->updateShipment($shipment, $request->validated());

        return back()->with('status', __('Envío actualizado.'));
    }
}
