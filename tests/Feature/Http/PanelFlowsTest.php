<?php

/*
|--------------------------------------------------------------------------
| Sprint 3-4 · Flujos validados en los paneles (FormRequest + componentes)
|--------------------------------------------------------------------------
*/

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderShop;
use App\Models\Product;
use App\Models\Shipment;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->artisan = artisanWithShop();
    $this->category = Category::factory()->create();
});

function validProductData(array $overrides = []): array
{
    return array_merge([
        'title' => 'Fuente de barro vidriado',
        'category_id' => Category::first()->id,
        'description' => 'Pieza torneada a mano.',
        'base_price' => '38.50',
        'stock' => 4,
        'tax_rate' => '21.00',
        'status' => Product::STATUS_ACTIVE,
        'featured' => '1',
        'available_from' => now()->addWeek()->format('Y-m-d'),
    ], $overrides);
}

describe('CRUD de productos del artesano', function () {
    it('muestra el formulario con los componentes reutilizables', function () {
        $this->actingAs($this->artisan)
            ->get(route('artisan.products.create'))
            ->assertOk()
            ->assertSee('name="category_id"', false)  // x-select
            ->assertSee('name="tax_rate"', false)     // x-select
            ->assertSee('type="date"', false)         // x-date-input
            ->assertSee('type="checkbox"', false)     // x-checkbox
            ->assertSee('name="description"', false); // x-textarea
    });

    it('crea una pieza válida en el taller del artesano', function () {
        $this->actingAs($this->artisan)
            ->post(route('artisan.products.store'), validProductData())
            ->assertRedirect(route('artisan.products.index'))
            ->assertSessionHas('status');

        $product = Product::sole();

        expect($product->shop_id)->toBe($this->artisan->shop->id)
            ->and($product->slug)->toBe('fuente-de-barro-vidriado')
            ->and($product->featured)->toBeTrue()
            ->and((float) $product->tax_rate)->toBe(21.0);
    });

    it('valida los campos con el FormRequest', function () {
        $this->actingAs($this->artisan)
            ->post(route('artisan.products.store'), [])
            ->assertSessionHasErrors(['title', 'category_id', 'base_price', 'stock', 'tax_rate', 'status']);

        $this->actingAs($this->artisan)
            ->post(route('artisan.products.store'), validProductData([
                'base_price' => '0.10',
                'tax_rate' => '7.00',
                'status' => 'vendido',
                'category_id' => 9999,
                'available_from' => now()->subDay()->format('Y-m-d'),
            ]))
            ->assertSessionHasErrors(['base_price', 'tax_rate', 'status', 'category_id', 'available_from']);

        expect(Product::count())->toBe(0);
    });

    it('no deja publicar a un taller sin verificar', function () {
        $unverified = artisanWithShop(['is_verified' => false]);

        $this->actingAs($unverified)->get(route('artisan.products.create'))->assertForbidden();
        $this->actingAs($unverified)->post(route('artisan.products.store'), validProductData())->assertForbidden();
    });

    it('edita y borra (lógicamente) solo sus piezas', function () {
        $mine = catalogProduct($this->artisan->shop, ['title' => 'Pieza propia']);
        $foreign = catalogProduct(artisanWithShop()->shop, ['title' => 'Pieza ajena']);

        $this->actingAs($this->artisan)->get(route('artisan.products.index'))
            ->assertSee('Pieza propia')
            ->assertDontSee('Pieza ajena');

        $this->actingAs($this->artisan)->get(route('artisan.products.edit', $foreign))->assertForbidden();
        $this->actingAs($this->artisan)->put(route('artisan.products.update', $foreign), validProductData())->assertForbidden();
        $this->actingAs($this->artisan)->delete(route('artisan.products.destroy', $foreign))->assertForbidden();

        $this->actingAs($this->artisan)->get(route('artisan.products.edit', $mine))->assertOk()->assertSee('Pieza propia');
        $this->actingAs($this->artisan)
            ->put(route('artisan.products.update', $mine), validProductData(['title' => 'Pieza renombrada', 'featured' => null]))
            ->assertRedirect(route('artisan.products.index'));

        expect($mine->fresh()->title)->toBe('Pieza renombrada')
            ->and($mine->fresh()->featured)->toBeFalse();

        $this->actingAs($this->artisan)->delete(route('artisan.products.destroy', $mine))->assertRedirect();

        expect($mine->fresh()->trashed())->toBeTrue()
            ->and($foreign->fresh()->trashed())->toBeFalse();
    });
});

describe('Subpedidos del artesano', function () {
    beforeEach(function () {
        $this->customer = customerWithAddress();
        $this->order = checkout($this->customer, [['product_id' => catalogProduct($this->artisan->shop)->id, 'quantity' => 1]]);
        $this->suborder = $this->order->orderShops->sole();
    });

    it('avanza el estado de su subpedido y valida el valor', function () {
        $this->order->update(['status' => Order::STATUS_PAID]);

        $this->actingAs($this->artisan)
            ->patch(route('artisan.orders.update-status', $this->suborder), ['status' => 'teletransportado'])
            ->assertSessionHasErrors('status');

        $this->actingAs($this->artisan)
            ->patch(route('artisan.orders.update-status', $this->suborder), ['status' => OrderShop::STATUS_PREPARING])
            ->assertSessionHas('status');

        expect($this->suborder->fresh()->status)->toBe(OrderShop::STATUS_PREPARING);
    });

    it('muestra el error de negocio si el pedido no está pagado', function () {
        $this->actingAs($this->artisan)
            ->from(route('artisan.orders.index'))
            ->patch(route('artisan.orders.update-status', $this->suborder), ['status' => OrderShop::STATUS_PREPARING])
            ->assertRedirect(route('artisan.orders.index'))
            ->assertSessionHas('error');

        expect($this->suborder->fresh()->status)->toBe(OrderShop::STATUS_PENDING);
    });

    it('no deja tocar el subpedido de otro taller', function () {
        $this->actingAs(artisanWithShop())
            ->patch(route('artisan.orders.update-status', $this->suborder), ['status' => OrderShop::STATUS_PREPARING])
            ->assertForbidden();

        $this->actingAs(artisanWithShop())
            ->patch(route('artisan.orders.cancel', $this->suborder))
            ->assertForbidden();
    });
});

describe('Pedidos del cliente', function () {
    beforeEach(function () {
        $this->customer = customerWithAddress();
        $this->order = checkout($this->customer, [['product_id' => catalogProduct($this->artisan->shop)->id, 'quantity' => 1]]);
    });

    it('ve sus pedidos pero no los ajenos', function () {
        $this->actingAs($this->customer)->get(route('customer.orders.index'))->assertOk()->assertSee($this->order->order_number);
        $this->actingAs($this->customer)->get(route('customer.orders.show', $this->order))->assertOk();
        $this->actingAs(customerWithAddress())->get(route('customer.orders.show', $this->order))->assertForbidden();
    });

    it('cancela su pedido pendiente pero no uno en preparación', function () {
        $this->actingAs($this->customer)
            ->patch(route('customer.orders.cancel', $this->order))
            ->assertRedirect(route('customer.orders.show', $this->order));

        expect($this->order->fresh()->status)->toBe(Order::STATUS_CANCELLED);

        $preparing = checkout($this->customer, [['product_id' => catalogProduct($this->artisan->shop)->id, 'quantity' => 1]]);
        $preparing->update(['status' => Order::STATUS_PREPARING]);

        $this->actingAs($this->customer)->patch(route('customer.orders.cancel', $preparing))->assertForbidden();
    });
});

describe('Reparto', function () {
    beforeEach(function () {
        $order = checkout(customerWithAddress(), [['product_id' => catalogProduct($this->artisan->shop)->id, 'quantity' => 1]]);
        $order->update(['status' => Order::STATUS_PAID]);
        $order->orderShops->sole()->update(['status' => OrderShop::STATUS_SHIPPED]);

        $this->shipment = Shipment::create(['order_shop_id' => $order->orderShops->sole()->id, 'status' => 'pending']);
        $this->courier = userWithRole('courier');
    });

    it('lista la hoja de ruta y actualiza el envío con validación', function () {
        $this->actingAs($this->courier)->get(route('courier.shipments.index'))->assertOk();

        $this->actingAs($this->courier)
            ->patch(route('courier.shipments.update', $this->shipment), ['status' => 'in_transit'])
            ->assertSessionHasErrors('tracking_number');

        $this->actingAs($this->courier)
            ->patch(route('courier.shipments.update', $this->shipment), ['status' => 'in_transit', 'carrier' => 'Reparto local', 'tracking_number' => 'ART-001'])
            ->assertSessionHasNoErrors();

        expect($this->shipment->fresh()->status)->toBe('in_transit')
            ->and($this->shipment->fresh()->shipped_at)->not->toBeNull();
    });

    it('no deja actualizar envíos a quien no es repartidor', function () {
        $this->actingAs($this->artisan)
            ->patch(route('courier.shipments.update', $this->shipment), ['status' => 'delivered'])
            ->assertForbidden();
    });
});
