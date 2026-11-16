<?php

namespace App\Services\Orders;

use App\Exceptions\Domain\OrderCannotBeCancelledException;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderShop;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Cancelación total de un pedido o parcial (solo la parte de un taller).
 *
 * Al cancelar se marcan las líneas como canceladas, se repone el stock y se
 * recalculan los totales invocando desde código el comando
 * artesa:recalculate-order-totals (comando reutilizable también desde consola).
 */
class OrderCancellationService
{
    /**
     * Cancela el pedido completo (solo en estados pending o paid).
     *
     * @throws OrderCannotBeCancelledException
     */
    public function cancelOrder(Order $order): Order
    {
        if (! $order->isCancellable()) {
            throw new OrderCannotBeCancelledException(
                "El pedido {$order->order_number} está en estado «{$order->status}» y ya no puede cancelarse."
            );
        }

        DB::transaction(function () use ($order) {
            foreach ($order->orderShops()->with('orderProducts')->get() as $orderShop) {
                if ($orderShop->status !== OrderShop::STATUS_CANCELLED) {
                    $this->cancelLinesAndRestoreStock($orderShop);
                }
            }

            $order->update(['status' => Order::STATUS_CANCELLED]);

            // Los pagos completados quedan marcados para reembolso, nunca se borran
            $order->payments()->where('status', 'completed')->update(['status' => 'refunded']);

            $this->logStatus($order, Order::STATUS_CANCELLED);
        });

        $this->recalculateTotals($order);

        return $order->fresh(['orderShops.orderProducts']);
    }

    /**
     * Cancelación parcial: solo el subpedido de un taller.
     * Si era el último subpedido activo, se cancela el pedido entero.
     *
     * @throws OrderCannotBeCancelledException
     */
    public function cancelSuborder(OrderShop $orderShop): OrderShop
    {
        $order = $orderShop->order()->firstOrFail();

        if (in_array($order->status, [Order::STATUS_CANCELLED, Order::STATUS_DELIVERED], true)
            || ! in_array($orderShop->status, OrderShop::CANCELLABLE_STATUSES, true)) {
            throw new OrderCannotBeCancelledException(
                "La parte del taller en el pedido {$order->order_number} ya no puede cancelarse (estado «{$orderShop->status}»)."
            );
        }

        DB::transaction(function () use ($order, $orderShop) {
            $this->cancelLinesAndRestoreStock($orderShop->load('orderProducts'));

            $stillActive = $order->orderShops()
                ->where('status', '!=', OrderShop::STATUS_CANCELLED)
                ->exists();

            if (! $stillActive) {
                $order->update(['status' => Order::STATUS_CANCELLED]);
                $this->logStatus($order, Order::STATUS_CANCELLED);
            } else {
                $this->logStatus($order, 'partially_cancelled');
            }
        });

        $this->recalculateTotals($order);

        return $orderShop->fresh();
    }

    private function cancelLinesAndRestoreStock(OrderShop $orderShop): void
    {
        foreach ($orderShop->orderProducts as $line) {
            if ($line->line_status === OrderProduct::STATUS_CANCELLED) {
                continue;
            }

            if ($line->product_variant_id) {
                ProductVariant::whereKey($line->product_variant_id)->increment('stock', $line->quantity);
            } else {
                Product::withTrashed()->whereKey($line->product_id)->increment('stock', $line->quantity);
            }

            $line->update(['line_status' => OrderProduct::STATUS_CANCELLED]);
        }

        $orderShop->update(['status' => OrderShop::STATUS_CANCELLED]);
    }

    private function logStatus(Order $order, string $status): void
    {
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $status,
            'created_at' => now(),
        ]);
    }

    /**
     * Comando invocado desde código (requisito: comandos llamados desde la aplicación).
     */
    private function recalculateTotals(Order $order): void
    {
        Artisan::call('artesa:recalculate-order-totals', ['order' => $order->id]);
    }
}
