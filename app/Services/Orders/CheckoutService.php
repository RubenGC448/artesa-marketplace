<?php

namespace App\Services\Orders;

use App\Exceptions\Domain\CheckoutException;
use App\Exceptions\Domain\InsufficientStockException;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderShop;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Convierte una lista de artículos en un pedido completo.
 *
 * Todo ocurre en una transacción:
 *  1. Valida dirección, productos publicados, talleres verificados, variantes y stock.
 *  2. Crea el pedido con la dirección de envío congelada.
 *  3. Crea un subpedido (OrderShop) por cada taller con sus portes.
 *  4. Crea una línea por producto/variante con precio, IVA, comisión y nombres congelados.
 *  5. Descuenta el stock (de la variante o del producto) y registra el historial.
 *  6. Calcula los totales con OrderTotalsCalculator.
 */
class CheckoutService
{
    public function __construct(private OrderTotalsCalculator $calculator)
    {
    }

    /**
     * @param  array<int, array{product_id:int, variant_id?:int|null, quantity:int, notes?:string|null}>  $items
     *
     * @throws CheckoutException
     * @throws InsufficientStockException
     */
    public function placeOrder(User $customer, Address $address, array $items, ?string $notes = null): Order
    {
        if ($items === []) {
            throw new CheckoutException('El carrito está vacío.');
        }

        if ((int) $address->user_id !== (int) $customer->id) {
            throw new CheckoutException('La dirección de envío no pertenece al comprador.');
        }

        return DB::transaction(function () use ($customer, $address, $items, $notes) {
            $lines = $this->resolveLines($items);

            $order = Order::create(array_merge([
                'order_number' => $this->generateOrderNumber(),
                'customer_id' => $customer->id,
                'status' => Order::STATUS_PENDING,
                'subtotal' => 0,
                'shipping_cost' => 0,
                'total' => 0,
                'notes' => $notes,
            ], Order::shippingSnapshotFrom($address)));

            foreach ($lines->groupBy(fn (array $line) => $line['product']->shop_id) as $shopId => $shopLines) {
                $orderShop = OrderShop::create([
                    'order_id' => $order->id,
                    'shop_id' => $shopId,
                    'status' => OrderShop::STATUS_PENDING,
                    'subtotal' => 0,
                    'shipping_cost' => config('artesa.shipping_flat_rate'),
                    'total' => 0,
                ]);

                foreach ($shopLines as $line) {
                    $this->createLine($order, $orderShop, $line);
                }
            }

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => Order::STATUS_PENDING,
                'created_at' => now(),
            ]);

            return $this->calculator
                ->recalculate($order)
                ->fresh(['orderShops.orderProducts']);
        });
    }

    /**
     * Valida cada artículo, bloquea las filas de stock y agrupa repetidos.
     *
     * @return Collection<int, array{product:Product, variant:?ProductVariant, quantity:int, notes:?string}>
     */
    private function resolveLines(array $items): Collection
    {
        $merged = [];

        foreach ($items as $item) {
            $quantity = (int) ($item['quantity'] ?? 0);
            $variantId = $item['variant_id'] ?? null;

            if ($quantity < 1) {
                throw new CheckoutException('La cantidad de cada artículo debe ser al menos 1.');
            }

            $key = $item['product_id'].'-'.($variantId ?? 'base');
            $merged[$key] ??= ['product_id' => $item['product_id'], 'variant_id' => $variantId, 'quantity' => 0, 'notes' => $item['notes'] ?? null];
            $merged[$key]['quantity'] += $quantity;
        }

        return collect($merged)->values()->map(function (array $item) {
            $product = Product::with('shop')->lockForUpdate()->find($item['product_id']);

            if (! $product || ! $product->isPublished() || ! $product->shop?->is_verified) {
                throw new CheckoutException("El producto #{$item['product_id']} no está disponible para la venta.");
            }

            $hasVariants = $product->variants()->exists();
            $variant = null;

            if ($hasVariants) {
                $variant = $item['variant_id']
                    ? ProductVariant::lockForUpdate()->find($item['variant_id'])
                    : null;

                if (! $variant || (int) $variant->product_id !== (int) $product->id) {
                    throw new CheckoutException("Debes elegir una variante válida de «{$product->title}».");
                }

                if ($variant->stock < $item['quantity']) {
                    throw InsufficientStockException::for("{$product->title} ({$variant->name})", $item['quantity'], $variant->stock);
                }
            } else {
                if ($item['variant_id']) {
                    throw new CheckoutException("«{$product->title}» no tiene variantes.");
                }

                if ($product->stock < $item['quantity']) {
                    throw InsufficientStockException::for($product->title, $item['quantity'], $product->stock);
                }
            }

            return [
                'product' => $product,
                'variant' => $variant,
                'quantity' => $item['quantity'],
                'notes' => $item['notes'],
            ];
        });
    }

    private function createLine(Order $order, OrderShop $orderShop, array $line): OrderProduct
    {
        /** @var Product $product */
        $product = $line['product'];
        /** @var ProductVariant|null $variant */
        $variant = $line['variant'];

        $unitPrice = (float) $product->base_price + (float) ($variant?->price_modifier ?? 0);

        $orderLine = OrderProduct::create([
            'order_id' => $order->id,
            'order_shop_id' => $orderShop->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant?->id,
            'product_title' => $product->title,
            'variant_name' => $variant?->name,
            'quantity' => $line['quantity'],
            'unit_price' => round($unitPrice, 2),
            'applied_tax' => $product->tax_rate,
            'artisan_commission_rate' => $product->shop->commission_rate,
            'line_status' => OrderProduct::STATUS_PENDING,
            'customization_notes' => $line['notes'],
        ]);

        if ($variant) {
            $variant->decrement('stock', $line['quantity']);
        } else {
            $product->decrement('stock', $line['quantity']);
        }

        return $orderLine;
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = 'ART-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
        } while (Order::withTrashed()->where('order_number', $number)->exists());

        return $number;
    }
}
