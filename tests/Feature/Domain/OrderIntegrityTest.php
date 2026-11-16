<?php

/*
|--------------------------------------------------------------------------
| Correcciones del profesor (Sprint 2): integridad de los pedidos
|--------------------------------------------------------------------------
|  - Dos variantes del mismo producto en un pedido sin pisarse.
|  - Coherencia línea ↔ subpedido ↔ taller ↔ variante (OrderLineGuard).
|  - Dirección de envío congelada.
|  - Sin cascadas que borren ventas.
|  - Totales derivados y cancelación parcial.
|  - Reconstrucción desde cero con el pedido de demostración.
*/

use App\Exceptions\Domain\InvalidOrderLineException;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\ProductVariant;
use App\Services\Orders\OrderCancellationService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    config(['artesa.shipping_flat_rate' => 5.00]);

    $this->customer = customerWithAddress();
    $this->shopA = artisanWithShop()->shop;
    $this->shopB = artisanWithShop()->shop;

    $this->vase = catalogProduct($this->shopA, ['title' => 'Jarrón ánfora', 'base_price' => 60]);
    $this->medium = ProductVariant::factory()->create(['product_id' => $this->vase->id, 'name' => 'Mediano', 'price_modifier' => 0, 'stock' => 5]);
    $this->large = ProductVariant::factory()->create(['product_id' => $this->vase->id, 'name' => 'Grande', 'price_modifier' => 15, 'stock' => 3]);

    $this->belt = catalogProduct($this->shopB, ['title' => 'Cinturón de cuero', 'base_price' => 40, 'stock' => 10]);
});

it('admite dos variantes del mismo producto como líneas distintas del mismo subpedido', function () {
    $order = checkout($this->customer, [
        ['product_id' => $this->vase->id, 'variant_id' => $this->medium->id, 'quantity' => 1],
        ['product_id' => $this->vase->id, 'variant_id' => $this->large->id, 'quantity' => 2],
    ]);

    $lines = $order->orderShops->sole()->orderProducts;
    $large = $lines->firstWhere('product_variant_id', $this->large->id);

    expect($lines)->toHaveCount(2)
        ->and($lines->pluck('product_id')->unique())->toHaveCount(1)
        ->and((float) $large->unit_price)->toBe(75.0)   // 60 + 15 congelado
        ->and($large->variant_name)->toBe('Grande')
        ->and((float) $order->subtotal)->toBe(210.0)    // 60 + 2 × 75
        ->and((float) $order->total)->toBe(215.0)       // + 5 € de portes
        ->and($this->medium->fresh()->stock)->toBe(4)
        ->and($this->large->fresh()->stock)->toBe(1);
});

it('modifica desde código un atributo de la pivote de una línea sin tocar la otra variante', function () {
    $order = checkout($this->customer, [
        ['product_id' => $this->vase->id, 'variant_id' => $this->medium->id, 'quantity' => 1],
        ['product_id' => $this->vase->id, 'variant_id' => $this->large->id, 'quantity' => 1],
    ]);

    $order->orderProducts()
        ->where('product_variant_id', $this->large->id)
        ->first()
        ->update(['line_status' => OrderProduct::STATUS_READY, 'customization_notes' => 'Grabar iniciales']);

    $viaPivot = $order->products()->wherePivot('product_variant_id', $this->large->id)->first()->pivot;
    $other = $order->orderProducts()->where('product_variant_id', $this->medium->id)->first();

    expect($viaPivot->line_status)->toBe('ready')
        ->and($viaPivot->customization_notes)->toBe('Grabar iniciales')
        ->and($other->line_status)->toBe('pending');
});

it('impide vincular al subpedido de un taller un producto o una variante de otro taller', function () {
    $order = checkout($this->customer, [['product_id' => $this->vase->id, 'variant_id' => $this->medium->id, 'quantity' => 1]]);
    $suborderA = $order->orderShops->sole();

    // Producto del taller B dentro del subpedido del taller A
    expect(fn () => OrderProduct::create([
        'order_id' => $order->id,
        'order_shop_id' => $suborderA->id,
        'product_id' => $this->belt->id,
        'quantity' => 1,
        'unit_price' => 40,
    ]))->toThrow(InvalidOrderLineException::class);

    // Variante que pertenece a una pieza del taller B
    $beltVariant = ProductVariant::factory()->create(['product_id' => $this->belt->id]);

    expect(fn () => OrderProduct::create([
        'order_id' => $order->id,
        'order_shop_id' => $suborderA->id,
        'product_id' => $this->vase->id,
        'product_variant_id' => $beltVariant->id,
        'quantity' => 1,
        'unit_price' => 60,
    ]))->toThrow(InvalidOrderLineException::class);

    // También al modificar una línea ya guardada
    $line = $suborderA->orderProducts->sole();

    expect(fn () => $line->update(['product_variant_id' => $beltVariant->id]))
        ->toThrow(InvalidOrderLineException::class);

    expect(OrderProduct::count())->toBe(1)
        ->and($line->fresh()->product_variant_id)->toBe($this->medium->id);
});

it('impide asignar una línea al subpedido de otro pedido', function () {
    $first = checkout($this->customer, [['product_id' => $this->vase->id, 'variant_id' => $this->medium->id, 'quantity' => 1]]);
    $second = checkout($this->customer, [['product_id' => $this->vase->id, 'variant_id' => $this->large->id, 'quantity' => 1]]);

    expect(fn () => OrderProduct::create([
        'order_id' => $first->id,
        'order_shop_id' => $second->orderShops->sole()->id,
        'product_id' => $this->vase->id,
        'product_variant_id' => $this->medium->id,
        'quantity' => 1,
        'unit_price' => 60,
    ]))->toThrow(InvalidOrderLineException::class);
});

it('congela la dirección de envío: editar o borrar la original no altera la venta', function () {
    $address = $this->customer->addresses->first();
    $originalStreet = $address->street;
    $originalCity = $address->city;

    $order = checkout($this->customer, [['product_id' => $this->belt->id, 'quantity' => 1]]);

    $address->update(['street' => 'Calle Nueva 99', 'city' => 'Ciudad Inventada']);

    expect($order->fresh()->shipping_street)->toBe($originalStreet)
        ->and($order->fresh()->shipping_city)->toBe($originalCity);

    $address->delete();

    expect($order->fresh()->address_id)->toBeNull()
        ->and($order->fresh()->shipping_street)->toBe($originalStreet);
});

it('no deja borrar físicamente productos ni clientes con ventas', function () {
    checkout($this->customer, [['product_id' => $this->belt->id, 'quantity' => 1]]);

    expect(fn () => $this->belt->forceDelete())->toThrow(QueryException::class);
    expect(fn () => $this->customer->forceDelete())->toThrow(QueryException::class);

    // El borrado lógico sí se permite y la venta conserva su línea.
    // (Instancia nueva: tras un forceDelete fallido el modelo queda en modo «forzado».)
    $this->belt->fresh()->delete();

    expect(OrderProduct::where('product_id', $this->belt->id)->exists())->toBeTrue();
});

it('congela precio, IVA y comisión aunque cambien después', function () {
    $order = checkout($this->customer, [['product_id' => $this->belt->id, 'quantity' => 1]]);

    $this->belt->update(['base_price' => 99, 'tax_rate' => 10]);
    $this->shopB->update(['commission_rate' => 25]);

    $line = $order->orderProducts()->sole();

    expect((float) $line->unit_price)->toBe(40.0)
        ->and((float) $line->applied_tax)->toBe(21.0)
        ->and((float) $line->artisan_commission_rate)->toBe(10.0)
        ->and($line->product_title)->toBe('Cinturón de cuero');
});

it('recalcula los totales tras cancelar la parte de un taller', function () {
    $order = checkout($this->customer, [
        ['product_id' => $this->vase->id, 'variant_id' => $this->medium->id, 'quantity' => 1], // 60
        ['product_id' => $this->belt->id, 'quantity' => 1],                                   // 40
    ]);

    expect((float) $order->total)->toBe(110.0); // 100 + 2 subpedidos × 5 €

    $suborderB = $order->orderShops->firstWhere('shop_id', $this->shopB->id);
    app(OrderCancellationService::class)->cancelSuborder($suborderB);

    $order->refresh();

    expect($order->status)->toBe(Order::STATUS_PENDING)
        ->and((float) $order->subtotal)->toBe(60.0)
        ->and((float) $order->shipping_cost)->toBe(5.0)
        ->and((float) $order->total)->toBe(65.0)
        ->and($suborderB->fresh()->status)->toBe('cancelled')
        ->and($this->belt->fresh()->stock)->toBe(10); // stock devuelto
});

it('reconstruye la base de datos desde cero con el pedido de demostración', function () {
    $this->seed(); // DatabaseSeeder completo

    $demo = Order::where('order_number', 'ART-2026-DEMO')->with('orderShops.orderProducts')->firstOrFail();
    $cancelled = $demo->orderShops->firstWhere('status', 'cancelled');
    $active = $demo->orderShops->firstWhere('status', '!=', 'cancelled');

    expect($demo->orderShops)->toHaveCount(2)
        ->and($cancelled)->not->toBeNull()
        ->and($active->orderProducts)->toHaveCount(2)
        ->and($active->orderProducts->pluck('product_id')->unique())->toHaveCount(1)
        ->and($active->orderProducts->pluck('product_variant_id')->unique())->toHaveCount(2)
        ->and((float) $demo->total)->toBe((float) $active->total)
        ->and($demo->shipping_street)->not->toBeNull();

    // Todas las líneas sembradas son coherentes
    OrderProduct::with(['orderShop', 'product'])->get()->each(function (OrderProduct $line) {
        expect($line->orderShop->order_id)->toEqual($line->order_id)
            ->and($line->product->shop_id)->toEqual($line->orderShop->shop_id);
    });
});
