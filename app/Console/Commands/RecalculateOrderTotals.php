<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Orders\OrderTotalsCalculator;
use Illuminate\Console\Command;

/**
 * Recalcula subtotales, portes y totales de un pedido (o de todos) a partir
 * de sus líneas. Se invoca desde código en OrderCancellationService.
 */
class RecalculateOrderTotals extends Command
{
    protected $signature = 'artesa:recalculate-order-totals
                            {order? : ID o número del pedido}
                            {--all : Recalcular todos los pedidos}';

    protected $description = 'Recalcula los importes de subpedidos y pedidos a partir de sus líneas';

    public function handle(OrderTotalsCalculator $calculator): int
    {
        $orderArgument = $this->argument('order');

        if (! $orderArgument && ! $this->option('all')) {
            $this->error('Indica un pedido o usa la opción --all.');

            return self::INVALID;
        }

        if ($this->option('all')) {
            $count = 0;

            Order::query()->chunkById(100, function ($orders) use ($calculator, &$count) {
                foreach ($orders as $order) {
                    $calculator->recalculate($order);
                    $count++;
                }
            });

            $this->info("Recalculados {$count} pedidos.");

            return self::SUCCESS;
        }

        $order = Order::where('id', $orderArgument)
            ->orWhere('order_number', $orderArgument)
            ->first();

        if (! $order) {
            $this->error("No existe el pedido «{$orderArgument}».");

            return self::FAILURE;
        }

        $calculator->recalculate($order);

        $this->info("Pedido {$order->order_number}: subtotal {$order->subtotal} €, portes {$order->shipping_cost} €, total {$order->total} €.");

        return self::SUCCESS;
    }
}
