<?php

/*
|--------------------------------------------------------------------------
| Sprint 4 · Comandos Artisan (4, de ellos 2 invocados desde código)
|--------------------------------------------------------------------------
|  - artesa:verify-shop             (invocado desde Admin\ShopVerificationController)
|  - artesa:recalculate-order-totals (invocado desde OrderCancellationService)
|  - artesa:cancel-unpaid-orders    (programado cada hora)
|  - artesa:low-stock-report        (programado a diario)
*/

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    config(['artesa.shipping_flat_rate' => 5.00]);
});

describe('artesa:verify-shop', function () {
    it('verifica un taller por slug', function () {
        $shop = artisanWithShop(['is_verified' => false])->shop;

        $this->artisan('artesa:verify-shop', ['shop' => $shop->slug])
            ->expectsOutputToContain('verificado')
            ->assertSuccessful();

        expect($shop->fresh()->is_verified)->toBeTrue();
    });

    it('retira la verificación con --revoke y despublica los productos del taller', function () {
        $shop = artisanWithShop()->shop;
        $product = catalogProduct($shop);

        $this->artisan('artesa:verify-shop', ['shop' => $shop->id, '--revoke' => true])->assertSuccessful();

        expect($shop->fresh()->is_verified)->toBeFalse()
            ->and($product->fresh()->status)->toBe(Product::STATUS_INACTIVE);
    });

    it('falla con un taller inexistente', function () {
        $this->artisan('artesa:verify-shop', ['shop' => 'no-existe'])
            ->expectsOutputToContain('No existe')
            ->assertFailed();
    });

    it('se invoca desde el panel de moderación', function () {
        $shop = artisanWithShop(['is_verified' => false])->shop;

        $this->actingAs(userWithRole('moderator'))
            ->patch(route('admin.shops.verify', $shop))
            ->assertRedirect()
            ->assertSessionHas('status');

        expect($shop->fresh()->is_verified)->toBeTrue();
    });

    it('no deja verificar desde el panel a quien no es moderador', function () {
        $shop = artisanWithShop(['is_verified' => false])->shop;

        $this->actingAs(artisanWithShop())->patch(route('admin.shops.verify', $shop))->assertForbidden();

        expect($shop->fresh()->is_verified)->toBeFalse();
    });
});

describe('artesa:recalculate-order-totals', function () {
    it('recalcula los importes de un pedido a partir de sus líneas', function () {
        $order = checkout(customerWithAddress(), [['product_id' => catalogProduct(artisanWithShop()->shop, ['base_price' => 30])->id, 'quantity' => 2]]);

        $order->forceFill(['subtotal' => 0, 'shipping_cost' => 0, 'total' => 0])->save(); // importes corruptos

        $this->artisan('artesa:recalculate-order-totals', ['order' => $order->order_number])->assertSuccessful();

        expect((float) $order->fresh()->total)->toBe(65.0); // 2 × 30 + 5
    });

    it('recalcula todos los pedidos con --all', function () {
        $customer = customerWithAddress();
        $product = catalogProduct(artisanWithShop()->shop, ['base_price' => 10]);
        checkout($customer, [['product_id' => $product->id, 'quantity' => 1]]);
        checkout($customer, [['product_id' => $product->id, 'quantity' => 1]]);
        Order::query()->update(['total' => 0]);

        $this->artisan('artesa:recalculate-order-totals', ['--all' => true])
            ->expectsOutputToContain('Recalculados 2 pedidos')
            ->assertSuccessful();

        expect(Order::where('total', 15)->count())->toBe(2);
    });

    it('exige un pedido o --all y falla si el pedido no existe', function () {
        $this->artisan('artesa:recalculate-order-totals')->assertExitCode(2);
        $this->artisan('artesa:recalculate-order-totals', ['order' => 'ART-NADA'])->assertFailed();
    });
});

describe('artesa:cancel-unpaid-orders', function () {
    beforeEach(function () {
        $this->product = catalogProduct(artisanWithShop()->shop, ['stock' => 10]);
        $customer = customerWithAddress();

        $this->abandoned = checkout($customer, [['product_id' => $this->product->id, 'quantity' => 3]]);
        $this->abandoned->forceFill(['created_at' => now()->subDays(3)])->save();

        $this->recent = checkout($customer, [['product_id' => $this->product->id, 'quantity' => 1]]);
    });

    it('cancela los pedidos pendientes antiguos y devuelve el stock', function () {
        expect($this->product->fresh()->stock)->toBe(6);

        $this->artisan('artesa:cancel-unpaid-orders', ['--hours' => 48])
            ->expectsOutputToContain('Cancelados 1 pedidos')
            ->assertSuccessful();

        expect($this->abandoned->fresh()->status)->toBe(Order::STATUS_CANCELLED)
            ->and($this->recent->fresh()->status)->toBe(Order::STATUS_PENDING)
            ->and($this->product->fresh()->stock)->toBe(9);
    });

    it('no cancela nada en modo --dry-run', function () {
        $this->artisan('artesa:cancel-unpaid-orders', ['--dry-run' => true])
            ->expectsOutputToContain('Modo prueba')
            ->assertSuccessful();

        expect($this->abandoned->fresh()->status)->toBe(Order::STATUS_PENDING);
    });

    it('rechaza un número de horas no válido', function () {
        $this->artisan('artesa:cancel-unpaid-orders', ['--hours' => 0])->assertExitCode(2);
    });
});

describe('artesa:low-stock-report', function () {
    it('lista productos y variantes con stock bajo', function () {
        $shop = artisanWithShop()->shop;
        catalogProduct($shop, ['title' => 'Cuenco escaso', 'stock' => 2]);
        catalogProduct($shop, ['title' => 'Plato abundante', 'stock' => 50]);
        $withVariants = catalogProduct($shop, ['title' => 'Bolso con variantes']);
        ProductVariant::factory()->create(['product_id' => $withVariants->id, 'name' => 'Coñac', 'stock' => 1]);

        $this->artisan('artesa:low-stock-report', ['--threshold' => 3])
            ->expectsOutputToContain('Cuenco escaso')
            ->expectsOutputToContain('Bolso con variantes')
            ->doesntExpectOutputToContain('Plato abundante')
            ->assertSuccessful();
    });

    it('informa cuando no hay stock bajo', function () {
        catalogProduct(artisanWithShop()->shop, ['stock' => 50]);

        $this->artisan('artesa:low-stock-report')
            ->expectsOutputToContain('No hay piezas')
            ->assertSuccessful();
    });
});
