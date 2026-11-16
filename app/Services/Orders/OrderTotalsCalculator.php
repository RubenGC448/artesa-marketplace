<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderShop;

/**
 * Calcula los importes derivados de un pedido a partir de sus líneas.
 *
 * Reglas (documentadas para la defensa):
 *  - Línea: subtotal = unit_price (congelado, IVA incluido) × quantity.
 *    Las líneas canceladas no suman.
 *  - Subpedido (OrderShop): subtotal = Σ líneas activas; total = subtotal + portes.
 *    Un subpedido cancelado queda a 0 (no se cobran productos ni portes).
 *  - Pedido (Order): subtotal = Σ subtotales de subpedidos; portes = Σ portes;
 *    total = subtotal + portes.
 *  - IVA y comisión no cambian los totales: se guardan congelados en cada línea
 *    para desglosarlos en facturas y liquidaciones.
 */
class OrderTotalsCalculator
{
    public function recalculate(Order $order): Order
    {
        $order->load('orderShops.orderProducts');

        $subtotal = 0.0;
        $shipping = 0.0;

        foreach ($order->orderShops as $orderShop) {
            $this->recalculateSuborder($orderShop);

            $subtotal += (float) $orderShop->subtotal;
            $shipping += (float) $orderShop->shipping_cost;
        }

        $order->forceFill([
            'subtotal' => round($subtotal, 2),
            'shipping_cost' => round($shipping, 2),
            'total' => round($subtotal + $shipping, 2),
        ])->save();

        return $order;
    }

    public function recalculateSuborder(OrderShop $orderShop): OrderShop
    {
        if ($orderShop->status === OrderShop::STATUS_CANCELLED) {
            $orderShop->forceFill(['subtotal' => 0, 'shipping_cost' => 0, 'total' => 0])->save();

            return $orderShop;
        }

        $subtotal = $orderShop->orderProducts
            ->reject(fn (OrderProduct $line) => $line->line_status === OrderProduct::STATUS_CANCELLED)
            ->sum(fn (OrderProduct $line) => $line->subtotal);

        $orderShop->forceFill([
            'subtotal' => round($subtotal, 2),
            'total' => round($subtotal + (float) $orderShop->shipping_cost, 2),
        ])->save();

        return $orderShop;
    }
}
