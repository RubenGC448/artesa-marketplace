<?php

namespace App\Services\Orders;

use App\Exceptions\Domain\ArtesaDomainException;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderShop;
use App\Models\OrderStatusHistory;
use App\Models\Shipment;
use Illuminate\Support\Facades\DB;

/**
 * Flujo de preparación y reparto de un pedido con varios talleres.
 *
 *  - Cada taller avanza SU subpedido: preparing → ready → shipped.
 *  - El repartidor actualiza el envío: in_transit → delivered (o failed).
 *  - El estado global del pedido se deriva siempre de sus subpedidos activos.
 */
class FulfillmentService
{
    /** Orden de avance de un subpedido (no se puede retroceder). */
    private const SUBORDER_FLOW = [
        OrderShop::STATUS_PENDING => 0,
        OrderShop::STATUS_PREPARING => 1,
        OrderShop::STATUS_READY => 2,
        OrderShop::STATUS_SHIPPED => 3,
        OrderShop::STATUS_DELIVERED => 4,
    ];

    /**
     * El artesano avanza el estado de la parte de su taller.
     *
     * @throws ArtesaDomainException
     */
    public function updateSuborderStatus(OrderShop $orderShop, string $status): OrderShop
    {
        $order = $orderShop->order()->firstOrFail(); // siempre el estado actual del pedido

        if ($order->status === Order::STATUS_PENDING) {
            throw new ArtesaDomainException('El pedido está pendiente de pago: aún no puede prepararse.');
        }

        if (! isset(self::SUBORDER_FLOW[$orderShop->status]) || ! in_array($status, OrderShop::ARTISAN_STATUSES, true)) {
            throw new ArtesaDomainException("No se puede cambiar un subpedido en estado «{$orderShop->status}».");
        }

        if (self::SUBORDER_FLOW[$status] <= self::SUBORDER_FLOW[$orderShop->status]) {
            throw new ArtesaDomainException("El subpedido ya está en «{$orderShop->status}»: no se puede volver a «{$status}».");
        }

        DB::transaction(function () use ($orderShop, $status) {
            $orderShop->update(['status' => $status]);

            $lineStatus = $status === OrderShop::STATUS_PREPARING
                ? OrderProduct::STATUS_PREPARING
                : OrderProduct::STATUS_READY;

            $orderShop->orderProducts()
                ->where('line_status', '!=', OrderProduct::STATUS_CANCELLED)
                ->get()
                ->each->update(['line_status' => $lineStatus]);

            // Al enviarlo se abre el envío para que el repartidor lo recoja
            if ($status === OrderShop::STATUS_SHIPPED && ! $orderShop->shipments()->exists()) {
                Shipment::create([
                    'order_shop_id' => $orderShop->id,
                    'carrier' => 'Reparto local Artesa',
                    'status' => 'pending',
                ]);
            }

            $this->syncOrderStatus($orderShop->order()->firstOrFail());
        });

        return $orderShop->fresh();
    }

    /**
     * El repartidor actualiza un envío.
     */
    public function updateShipment(Shipment $shipment, array $data): Shipment
    {
        DB::transaction(function () use ($shipment, $data) {
            $status = $data['status'];

            $shipment->fill([
                'status' => $status,
                'carrier' => $data['carrier'] ?? $shipment->carrier,
                'tracking_number' => $data['tracking_number'] ?? $shipment->tracking_number,
                'shipped_at' => $status === 'in_transit' ? ($shipment->shipped_at ?? now()) : $shipment->shipped_at,
                'delivered_at' => $status === 'delivered' ? now() : $shipment->delivered_at,
            ])->save();

            $orderShop = $shipment->orderShop()->firstOrFail();

            if ($status === 'in_transit' && $orderShop->status !== OrderShop::STATUS_SHIPPED) {
                $orderShop->update(['status' => OrderShop::STATUS_SHIPPED]);
            }

            if ($status === 'delivered') {
                $orderShop->update(['status' => OrderShop::STATUS_DELIVERED]);
            }

            $this->syncOrderStatus($orderShop->order()->firstOrFail());
        });

        return $shipment->fresh();
    }

    /**
     * Deriva el estado global del pedido a partir de sus subpedidos activos.
     */
    public function syncOrderStatus(Order $order): Order
    {
        $active = $order->orderShops()->where('status', '!=', OrderShop::STATUS_CANCELLED)->pluck('status');

        if ($active->isEmpty() || in_array($order->status, [Order::STATUS_PENDING, Order::STATUS_CANCELLED], true)) {
            return $order;
        }

        $newStatus = match (true) {
            $active->every(fn ($s) => $s === OrderShop::STATUS_DELIVERED) => Order::STATUS_DELIVERED,
            $active->every(fn ($s) => in_array($s, [OrderShop::STATUS_SHIPPED, OrderShop::STATUS_DELIVERED], true)) => Order::STATUS_SHIPPED,
            $active->contains(fn ($s) => $s !== OrderShop::STATUS_PENDING) => Order::STATUS_PREPARING,
            default => $order->status,
        };

        if ($newStatus !== $order->status) {
            $order->update(['status' => $newStatus]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $newStatus,
                'created_at' => now(),
            ]);
        }

        return $order;
    }
}
