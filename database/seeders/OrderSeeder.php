<?php

namespace Database\Seeders;

use App\Exceptions\Domain\ArtesaDomainException;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderShop;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\User;
use App\Services\Orders\CheckoutService;
use App\Services\Orders\OrderCancellationService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Pedidos de prueba creados con el mismo servicio que usa la aplicación
 * (CheckoutService), de modo que precios, IVA, comisión, dirección y stock
 * quedan congelados y coherentes igual que en una compra real.
 */
class OrderSeeder extends Seeder
{
    public function __construct(
        private CheckoutService $checkout,
        private OrderCancellationService $cancellation,
    ) {
    }

    public function run(): void
    {
        $customers = User::role('customer')->with('addresses')->get();
        $products = Product::inCatalog()->with('variants')->get();

        if ($customers->isEmpty() || $products->isEmpty()) {
            return;
        }

        $this->seedDemoOrder($customers->first(), $products);
        $this->seedRandomOrders($customers, $products);
    }

    /**
     * Pedido de demostración pedido en la corrección del Sprint 2:
     *  - dos talleres,
     *  - dos variantes del mismo producto en el mismo subpedido,
     *  - cancelación parcial (se cancela la parte del segundo taller).
     */
    private function seedDemoOrder(User $customer, Collection $products): void
    {
        $withTwoVariants = $products->first(
            fn (Product $p) => $p->variants->where('stock', '>', 0)->count() >= 2
        );

        if (! $withTwoVariants) {
            return;
        }

        $otherShopProduct = $products->first(
            fn (Product $p) => $p->shop_id !== $withTwoVariants->shop_id && $p->available_stock > 0
        );

        if (! $otherShopProduct) {
            return;
        }

        [$variantA, $variantB] = $withTwoVariants->variants->where('stock', '>', 0)->take(2)->values()->all();

        $order = $this->checkout->placeOrder($customer, $this->addressOf($customer), [
            ['product_id' => $withTwoVariants->id, 'variant_id' => $variantA->id, 'quantity' => 1],
            ['product_id' => $withTwoVariants->id, 'variant_id' => $variantB->id, 'quantity' => 1],
            $this->itemFor($otherShopProduct),
        ], 'Pedido de demostración: dos talleres, dos variantes y cancelación parcial.');

        $order->update(['order_number' => 'ART-2026-DEMO']);
        $this->markAsPaid($order);

        // Cancelación parcial: el segundo taller no puede servir su parte
        $secondSuborder = $order->orderShops()->where('shop_id', $otherShopProduct->shop_id)->first();
        $this->cancellation->cancelSuborder($secondSuborder);
    }

    private function seedRandomOrders(Collection $customers, Collection $products): void
    {
        $statuses = ['delivered', 'delivered', 'shipped', 'preparing', 'paid', 'pending'];
        $byShop = $products->groupBy('shop_id');

        for ($i = 1; $i <= 12; $i++) {
            $customer = $customers->random();
            $shopsInOrder = $i % 2 === 0 && $byShop->count() > 1 ? 2 : 1;

            $items = $byShop->random($shopsInOrder)
                ->map(fn (Collection $shopProducts) => $shopProducts->filter(fn (Product $p) => $p->available_stock > 0))
                ->filter(fn (Collection $shopProducts) => $shopProducts->isNotEmpty())
                ->map(fn (Collection $shopProducts) => $this->itemFor($shopProducts->random()))
                ->values()
                ->all();

            if ($items === []) {
                continue;
            }

            try {
                $order = $this->checkout->placeOrder(
                    $customer,
                    $this->addressOf($customer),
                    $items,
                    fake()->boolean(30) ? 'Por favor, dejar en conserjería si no estoy en casa.' : null,
                );
            } catch (ArtesaDomainException) {
                continue; // sin stock suficiente: se omite este pedido de ejemplo
            }

            $createdAt = now()->subDays(15 - $i);
            $order->forceFill(['order_number' => sprintf('ART-2026-%04d', $i), 'created_at' => $createdAt, 'updated_at' => $createdAt])->save();

            $this->applyStatus($order, $statuses[($i - 1) % count($statuses)]);
        }
    }

    private function applyStatus(Order $order, string $status): void
    {
        if ($status === Order::STATUS_PENDING) {
            return; // recién creado: sin pago
        }

        $this->markAsPaid($order);

        if ($status === Order::STATUS_PAID) {
            return;
        }

        [$suborderStatus, $lineStatus] = match ($status) {
            'preparing' => [OrderShop::STATUS_PREPARING, OrderProduct::STATUS_PREPARING],
            'shipped' => [OrderShop::STATUS_SHIPPED, OrderProduct::STATUS_READY],
            default => [OrderShop::STATUS_DELIVERED, OrderProduct::STATUS_READY],
        };

        foreach ($order->orderShops as $orderShop) {
            $orderShop->update(['status' => $suborderStatus]);
            $orderShop->orderProducts->each->update(['line_status' => $lineStatus]);

            if (in_array($status, ['shipped', 'delivered'], true)) {
                $carrier = fake()->randomElement(['MRW', 'SEUR', 'Correos Express']);

                Shipment::create([
                    'order_shop_id' => $orderShop->id,
                    'carrier' => $carrier,
                    'tracking_number' => strtoupper(substr($carrier, 0, 3)).'-'.fake()->numerify('##########'),
                    'status' => $status === 'delivered' ? 'delivered' : 'in_transit',
                    'shipped_at' => $order->created_at->copy()->addDay(),
                    'delivered_at' => $status === 'delivered' ? $order->created_at->copy()->addDays(3) : null,
                ]);
            }
        }

        $order->update(['status' => $status]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $status,
            'created_at' => $order->created_at->copy()->addDays(2),
        ]);
    }

    private function markAsPaid(Order $order): void
    {
        Payment::create([
            'order_id' => $order->id,
            'amount' => $order->total,
            'method' => fake()->randomElement(['stripe', 'paypal']),
            'status' => 'completed',
            'external_reference' => 'ch_'.fake()->regexify('[A-Za-z0-9]{24}'),
            'paid_at' => $order->created_at,
        ]);

        $order->update(['status' => Order::STATUS_PAID]);

        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => Order::STATUS_PAID,
            'created_at' => $order->created_at,
        ]);
    }

    private function itemFor(Product $product): array
    {
        $variant = $product->variants->where('stock', '>', 0)->shuffle()->first();

        return [
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'quantity' => 1,
        ];
    }

    private function addressOf(User $customer): Address
    {
        return $customer->addresses->firstWhere('is_default', true)
            ?? $customer->addresses->first()
            ?? Address::factory()->default()->create(['user_id' => $customer->id]);
    }
}
