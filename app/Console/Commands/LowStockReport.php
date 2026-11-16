<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Console\Command;

/**
 * Lista las piezas y variantes con stock bajo, agrupadas por taller.
 */
class LowStockReport extends Command
{
    protected $signature = 'artesa:low-stock-report
                            {--threshold= : Umbral de unidades (por defecto config artesa.low_stock_threshold)}
                            {--shop= : Limitar a un taller (ID)}';

    protected $description = 'Informe de productos y variantes con stock bajo';

    public function handle(): int
    {
        $threshold = (int) ($this->option('threshold') ?? config('artesa.low_stock_threshold'));
        $shopId = $this->option('shop');

        if ($threshold < 0) {
            $this->error('El umbral no puede ser negativo.');

            return self::INVALID;
        }

        $products = Product::active()
            ->lowStock($threshold)
            ->when($shopId, fn ($q) => $q->where('shop_id', $shopId))
            ->with('shop')
            ->get()
            ->map(fn (Product $p) => [$p->shop->name, $p->title, '—', $p->stock]);

        $variants = ProductVariant::lowStock($threshold)
            ->whereHas('product', function ($q) use ($shopId) {
                $q->active()->when($shopId, fn ($q) => $q->where('shop_id', $shopId));
            })
            ->with('product.shop')
            ->get()
            ->map(fn (ProductVariant $v) => [$v->product->shop->name, $v->product->title, $v->name, $v->stock]);

        $rows = $products->concat($variants)->sortBy(fn ($row) => $row[0].$row[1])->values();

        if ($rows->isEmpty()) {
            $this->info("No hay piezas con {$threshold} unidades o menos.");

            return self::SUCCESS;
        }

        $this->table(['Taller', 'Producto', 'Variante', 'Stock'], $rows->all());
        $this->warn("{$rows->count()} referencias con stock bajo (≤ {$threshold}).");

        return self::SUCCESS;
    }
}
