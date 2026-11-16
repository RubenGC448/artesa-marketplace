<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Services\Orders\OrderCancellationService;
use Illuminate\Console\Command;

/**
 * Cancela los pedidos que siguen pendientes de pago pasado un plazo
 * y repone su stock. Programado cada hora en routes/console.php.
 */
class CancelUnpaidOrders extends Command
{
    protected $signature = 'artesa:cancel-unpaid-orders
                            {--hours= : Horas de margen (por defecto config artesa.unpaid_order_hours)}
                            {--dry-run : Solo listar, sin cancelar}';

    protected $description = 'Cancela pedidos pendientes de pago y devuelve el stock';

    public function handle(OrderCancellationService $service): int
    {
        $hours = (int) ($this->option('hours') ?? config('artesa.unpaid_order_hours'));

        if ($hours < 1) {
            $this->error('El número de horas debe ser al menos 1.');

            return self::INVALID;
        }

        $orders = Order::unpaidOlderThan(now()->subHours($hours))->get();

        if ($orders->isEmpty()) {
            $this->info("No hay pedidos pendientes de pago con más de {$hours} h.");

            return self::SUCCESS;
        }

        $this->table(
            ['Pedido', 'Cliente', 'Total', 'Creado'],
            $orders->map(fn (Order $o) => [$o->order_number, $o->customer_id, $o->total, $o->created_at->format('d/m/Y H:i')])
        );

        if ($this->option('dry-run')) {
            $this->warn("Modo prueba: {$orders->count()} pedidos se cancelarían.");

            return self::SUCCESS;
        }

        foreach ($orders as $order) {
            $service->cancelOrder($order);
        }

        $this->info("Cancelados {$orders->count()} pedidos y repuesto su stock.");

        return self::SUCCESS;
    }
}
