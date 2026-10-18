<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderShop;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Shipment;
use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        // Clientes compradores (excluyendo admin, moderador y artesanos)
        $clients = User::whereNotIn('email', [
            'admin@artesa.local',
            'moderador@artesa.local',
            'alfarero@artesa.local',
            'ebanista@artesa.local',
            'marroquinero@artesa.local',
            'joyero@artesa.local',
            'textil@artesa.local',
        ])->get();

        if ($clients->isEmpty()) {
            return;
        }

        $allProducts = Product::with(['shop', 'variants'])->get();
        if ($allProducts->isEmpty()) {
            return;
        }

        $orderStatuses = ['delivered', 'delivered', 'shipped', 'preparing', 'paid', 'pending'];

        for ($i = 1; $i <= 12; $i++) {
            $client = $clients->random();
            $address = $client->defaultAddress ?? Address::factory()->create(['user_id' => $client->id]);

            // En pedidos pares, mezclamos productos de 2 tiendas distintas (pedido multi-tienda)
            // En pedidos impares, productos de 1 sola tienda
            $isMultiShop = ($i % 2 === 0);

            if ($isMultiShop) {
                // Seleccionamos productos de 2 tiendas distintas
                $sampleProducts = $allProducts->groupBy('shop_id')->random(2)->map(fn ($group) => $group->random());
            } else {
                $shopGroup = $allProducts->groupBy('shop_id')->random();
                $sampleProducts = $shopGroup->random(min(2, $shopGroup->count()));
            }

            $orderNumber = sprintf('ART-2026-%04d', $i);
            $status = $orderStatuses[($i - 1) % count($orderStatuses)];

            // Creamos el pedido cabecera
            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $client->id,
                'address_id' => $address->id,
                'status' => $status,
                'subtotal' => 0.00,
                'shipping_cost' => 0.00,
                'total' => 0.00,
                'notes' => fake()->boolean(30) ? 'Por favor, dejar en conserjería si no estoy en casa.' : null,
                'created_at' => now()->subDays(15 - $i),
                'updated_at' => now()->subDays(15 - $i),
            ]);

            $orderSubtotal = 0.00;
            $orderShipping = 0.00;

            // Agrupamos los productos elegidos por tienda
            $productsByShop = $sampleProducts->groupBy('shop_id');

            foreach ($productsByShop as $shopId => $products) {
                $shop = Shop::find($shopId);
                $shopSubtotal = 0.00;
                $shopShipping = 4.95; // Tarifa plana por tienda

                $shopStatus = match ($status) {
                    'delivered' => 'delivered',
                    'shipped' => 'shipped',
                    'preparing' => 'preparing',
                    'paid' => 'pending',
                    default => 'pending',
                };

                $orderShop = OrderShop::create([
                    'order_id' => $order->id,
                    'shop_id' => $shop->id,
                    'status' => $shopStatus,
                    'subtotal' => 0.00,
                    'shipping_cost' => $shopShipping,
                    'total' => 0.00,
                    'created_at' => $order->created_at,
                    'updated_at' => $order->updated_at,
                ]);

                foreach ($products as $product) {
                    $qty = fake()->numberBetween(1, 2);
                    $variant = $product->variants->isNotEmpty() ? $product->variants->random() : null;
                    $unitPrice = (float) $product->base_price + ($variant ? (float) $variant->price_modifier : 0.00);

                    // Congelamos la línea en la tabla pivote
                    $order->products()->attach($product->id, [
                        'order_shop_id' => $orderShop->id,
                        'product_variant_id' => $variant?->id,
                        'quantity' => $qty,
                        'unit_price' => $unitPrice,
                        'applied_tax' => 21.00,
                        'artisan_commission_rate' => $shop->commission_rate,
                        'line_status' => $shopStatus === 'delivered' ? 'ready' : 'pending',
                        'customization_notes' => null,
                        'created_at' => $order->created_at,
                        'updated_at' => $order->updated_at,
                    ]);

                    $shopSubtotal += ($unitPrice * $qty);
                }

                $orderShop->update([
                    'subtotal' => $shopSubtotal,
                    'total' => $shopSubtotal + $shopShipping,
                ]);

                $orderSubtotal += $shopSubtotal;
                $orderShipping += $shopShipping;

                // Generamos envío para cada tienda si el pedido ya fue despachado o entregado
                if (in_array($status, ['shipped', 'delivered'])) {
                    $carrier = fake()->randomElement(['MRW', 'SEUR', 'Correos Express']);
                    Shipment::create([
                        'order_shop_id' => $orderShop->id,
                        'carrier' => $carrier,
                        'tracking_number' => strtoupper(substr($carrier, 0, 3)) . '-' . fake()->numerify('##########'),
                        'status' => $status === 'delivered' ? 'delivered' : 'in_transit',
                        'shipped_at' => $order->created_at->addDay(),
                        'delivered_at' => $status === 'delivered' ? $order->created_at->addDays(3) : null,
                        'created_at' => $order->created_at->addDay(),
                    ]);
                }
            }

            // Actualizamos los importes totales consolidados del pedido
            $order->update([
                'subtotal' => $orderSubtotal,
                'shipping_cost' => $orderShipping,
                'total' => $orderSubtotal + $orderShipping,
            ]);

            // Registro de pago si el pedido no está pendiente
            if ($status !== 'pending') {
                Payment::create([
                    'order_id' => $order->id,
                    'amount' => $orderSubtotal + $orderShipping,
                    'method' => fake()->randomElement(['stripe', 'paypal']),
                    'status' => 'completed',
                    'external_reference' => 'ch_' . fake()->regexify('[A-Za-z0-9]{24}'),
                    'paid_at' => $order->created_at,
                    'created_at' => $order->created_at,
                ]);

                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'status' => 'paid',
                    'created_at' => $order->created_at,
                ]);
            }

            // Historial de auditoría
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $status,
                'created_at' => $order->updated_at,
            ]);
        }
    }
}
