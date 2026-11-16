<?php

/*
|--------------------------------------------------------------------------
| Sprint 4 · Servicios de aplicación y casos de error
|--------------------------------------------------------------------------
| CheckoutService, OrderCancellationService, FulfillmentService,
| ShopVerificationService.
*/

use App\Exceptions\Domain\ArtesaDomainException;
use App\Exceptions\Domain\CheckoutException;
use App\Exceptions\Domain\InsufficientStockException;
use App\Exceptions\Domain\OrderCannotBeCancelledException;
use App\Models\Order;
use App\Models\OrderShop;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Shipment;
use App\Services\Orders\CheckoutService;
use App\Services\Orders\FulfillmentService;
use App\Services\Orders\OrderCancellationService;
use App\Services\Shops\ShopVerificationService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
    config(['artesa.shipping_flat_rate' => 5.00]);

    $this->customer = customerWithAddress();
    $this->shopA = artisanWithShop()->shop;
    $this->shopB = artisanWithShop()->shop;
    $this->bowl = catalogProduct($this->shopA, ['title' => 'Cuenco', 'base_price' => 20, 'stock' => 5, 'tax_rate' => 10]);
    $this->scarf = catalogProduct($this->shopB, ['title' => 'Bufanda', 'base_price' => 45, 'stock' => 4]);
});

describe('CheckoutService', function () {
    it('crea pedido, subpedidos por taller y líneas congeladas, y descuenta stock', function () {
        $order = checkout($this->customer, [
            ['product_id' => $this->bowl->id, 'quantity' => 1],
            ['product_id' => $this->bowl->id, 'quantity' => 1],  // repetido: se agrupa
            ['product_id' => $this->scarf->id, 'quantity' => 1],
        ]);

        $bowlLine = $order->orderProducts()->where('product_id', $this->bowl->id)->sole();

        expect($order->status)->toBe(Order::STATUS_PENDING)
            ->and($order->order_number)->toStartWith('ART-')
            ->and($order->orderShops)->toHaveCount(2)
            ->and($bowlLine->quantity)->toBe(2)
            ->and((float) $bowlLine->applied_tax)->toBe(10.0)
            ->and((float) $order->subtotal)->toBe(85.0)   // 2 × 20 + 45
            ->and((float) $order->total)->toBe(95.0)      // + 2 × 5 €
            ->and($order->shipping_name)->toBe($this->customer->addresses->first()->name)
            ->and($this->bowl->fresh()->stock)->toBe(3)
            ->and($order->statusHistory()->pluck('status')->all())->toBe(['pending']);
    });

    it('rechaza un carrito vacío o una dirección ajena', function () {
        $service = app(CheckoutService::class);

        expect(fn () => $service->placeOrder($this->customer, $this->customer->addresses->first(), []))
            ->toThrow(CheckoutException::class, 'vacío');

        $foreignAddress = customerWithAddress()->addresses->first();

        expect(fn () => $service->placeOrder($this->customer, $foreignAddress, [['product_id' => $this->bowl->id, 'quantity' => 1]]))
            ->toThrow(CheckoutException::class, 'no pertenece');
    });

    it('no deja nada a medias si falta stock (transacción)', function () {
        expect(fn () => checkout($this->customer, [
            ['product_id' => $this->bowl->id, 'quantity' => 1],
            ['product_id' => $this->scarf->id, 'quantity' => 99],
        ]))->toThrow(InsufficientStockException::class);

        expect(Order::count())->toBe(0)
            ->and(OrderShop::count())->toBe(0)
            ->and($this->bowl->fresh()->stock)->toBe(5);
    });

    it('rechaza borradores, talleres sin verificar y cantidades no válidas', function () {
        $draft = catalogProduct($this->shopA, ['status' => Product::STATUS_DRAFT]);
        $fromUnverified = catalogProduct(artisanWithShop(['is_verified' => false])->shop);

        expect(fn () => checkout($this->customer, [['product_id' => $draft->id, 'quantity' => 1]]))->toThrow(CheckoutException::class)
            ->and(fn () => checkout($this->customer, [['product_id' => $fromUnverified->id, 'quantity' => 1]]))->toThrow(CheckoutException::class)
            ->and(fn () => checkout($this->customer, [['product_id' => $this->bowl->id, 'quantity' => 0]]))->toThrow(CheckoutException::class);
    });

    it('exige una variante válida cuando el producto tiene variantes', function () {
        $variant = ProductVariant::factory()->create(['product_id' => $this->bowl->id, 'stock' => 2]);
        $foreign = ProductVariant::factory()->create(['product_id' => $this->scarf->id]);

        expect(fn () => checkout($this->customer, [['product_id' => $this->bowl->id, 'quantity' => 1]]))->toThrow(CheckoutException::class)
            ->and(fn () => checkout($this->customer, [['product_id' => $this->bowl->id, 'variant_id' => $foreign->id, 'quantity' => 1]]))->toThrow(CheckoutException::class)
            ->and(fn () => checkout($this->customer, [['product_id' => $this->bowl->id, 'variant_id' => $variant->id, 'quantity' => 3]]))->toThrow(InsufficientStockException::class);

        checkout($this->customer, [['product_id' => $this->bowl->id, 'variant_id' => $variant->id, 'quantity' => 2]]);

        expect($variant->fresh()->stock)->toBe(0)
            ->and($this->bowl->fresh()->stock)->toBe(5); // el stock del producto no se toca
    });
});

describe('OrderCancellationService', function () {
    it('cancela el pedido entero, devuelve stock y marca el pago para reembolso', function () {
        $order = checkout($this->customer, [
            ['product_id' => $this->bowl->id, 'quantity' => 2],
            ['product_id' => $this->scarf->id, 'quantity' => 1],
        ]);
        Payment::factory()->create(['order_id' => $order->id, 'status' => 'completed', 'amount' => $order->total]);
        $order->update(['status' => Order::STATUS_PAID]);

        $cancelled = app(OrderCancellationService::class)->cancelOrder($order);

        expect($cancelled->status)->toBe(Order::STATUS_CANCELLED)
            ->and($cancelled->orderShops->pluck('status')->unique()->all())->toBe(['cancelled'])
            ->and((float) $cancelled->total)->toBe(0.0)
            ->and($this->bowl->fresh()->stock)->toBe(5)
            ->and($this->scarf->fresh()->stock)->toBe(4)
            ->and($order->payments()->first()->status)->toBe('refunded');
    });

    it('no cancela un pedido que ya está en preparación', function () {
        $order = checkout($this->customer, [['product_id' => $this->bowl->id, 'quantity' => 1]]);
        $order->update(['status' => Order::STATUS_PREPARING]);

        expect(fn () => app(OrderCancellationService::class)->cancelOrder($order))
            ->toThrow(OrderCannotBeCancelledException::class);

        expect($this->bowl->fresh()->stock)->toBe(4);
    });

    it('cancela el pedido entero si se cancela su último subpedido activo', function () {
        $order = checkout($this->customer, [['product_id' => $this->bowl->id, 'quantity' => 1]]);

        app(OrderCancellationService::class)->cancelSuborder($order->orderShops->sole());

        expect($order->fresh()->status)->toBe(Order::STATUS_CANCELLED);
    });

    it('no cancela la parte de un taller que ya se envió', function () {
        $order = checkout($this->customer, [['product_id' => $this->bowl->id, 'quantity' => 1]]);
        $suborder = $order->orderShops->sole();
        $suborder->update(['status' => OrderShop::STATUS_SHIPPED]);

        expect(fn () => app(OrderCancellationService::class)->cancelSuborder($suborder))
            ->toThrow(OrderCannotBeCancelledException::class);
    });
});

describe('FulfillmentService', function () {
    beforeEach(function () {
        $this->order = checkout($this->customer, [
            ['product_id' => $this->bowl->id, 'quantity' => 1],
            ['product_id' => $this->scarf->id, 'quantity' => 1],
        ]);
        $this->subA = $this->order->orderShops->firstWhere('shop_id', $this->shopA->id);
        $this->subB = $this->order->orderShops->firstWhere('shop_id', $this->shopB->id);
        $this->service = app(FulfillmentService::class);
    });

    it('no deja preparar un pedido sin pagar', function () {
        expect(fn () => $this->service->updateSuborderStatus($this->subA, OrderShop::STATUS_PREPARING))
            ->toThrow(ArtesaDomainException::class, 'pendiente de pago');
    });

    it('lleva el pedido de pagado a entregado según avanzan talleres y repartidor', function () {
        $this->order->update(['status' => Order::STATUS_PAID]);

        $this->service->updateSuborderStatus($this->subA, OrderShop::STATUS_PREPARING);
        expect($this->order->fresh()->status)->toBe(Order::STATUS_PREPARING);

        $this->service->updateSuborderStatus($this->subA, OrderShop::STATUS_SHIPPED);
        $this->service->updateSuborderStatus($this->subB->fresh(), OrderShop::STATUS_SHIPPED);
        expect($this->order->fresh()->status)->toBe(Order::STATUS_SHIPPED)
            ->and(Shipment::count())->toBe(2);

        foreach (Shipment::all() as $shipment) {
            $this->service->updateShipment($shipment, ['status' => 'delivered']);
        }

        expect($this->order->fresh()->status)->toBe(Order::STATUS_DELIVERED)
            ->and(Shipment::whereNotNull('delivered_at')->count())->toBe(2);
    });

    it('no deja retroceder el estado de un subpedido', function () {
        $this->order->update(['status' => Order::STATUS_PAID]);
        $this->service->updateSuborderStatus($this->subA, OrderShop::STATUS_READY);

        expect(fn () => $this->service->updateSuborderStatus($this->subA->fresh(), OrderShop::STATUS_PREPARING))
            ->toThrow(ArtesaDomainException::class);
    });
});

describe('ShopVerificationService', function () {
    it('verifica y retira la verificación despublicando el catálogo', function () {
        $service = app(ShopVerificationService::class);
        $shop = artisanWithShop(['is_verified' => false])->shop;
        $product = catalogProduct($shop);

        expect($service->verify($shop)->is_verified)->toBeTrue();

        $service->revoke($shop);

        expect($shop->fresh()->is_verified)->toBeFalse()
            ->and($product->fresh()->status)->toBe(Product::STATUS_INACTIVE);
    });
});
