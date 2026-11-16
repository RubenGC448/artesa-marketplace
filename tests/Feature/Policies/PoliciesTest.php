<?php

/*
|--------------------------------------------------------------------------
| Sprint 4 · Las cinco policies con casos permitidos y denegados
|--------------------------------------------------------------------------
| ShopPolicy, ProductPolicy, OrderPolicy (+ OrderShopPolicy para el
| subpedido), ReviewPolicy y DisputePolicy.
*/

use App\Models\Dispute;
use App\Models\Order;
use App\Models\OrderShop;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->admin = userWithRole('admin');
    $this->moderator = userWithRole('moderator');
    $this->owner = artisanWithShop();
    $this->otherArtisan = artisanWithShop();
    $this->customer = customerWithAddress();
    $this->otherCustomer = customerWithAddress();
    $this->courier = userWithRole('courier');
});

describe('ShopPolicy', function () {
    it('muestra los talleres verificados a cualquiera, incluidos invitados', function () {
        expect(Gate::forUser(null)->allows('viewAny', Shop::class))->toBeTrue()
            ->and(Gate::forUser(null)->allows('view', $this->owner->shop))->toBeTrue();
    });

    it('solo deja ver un taller sin verificar a su dueño y a moderación', function () {
        $pending = artisanWithShop(['is_verified' => false]);

        expect(Gate::forUser(null)->allows('view', $pending->shop))->toBeFalse()
            ->and(Gate::forUser($this->otherArtisan)->allows('view', $pending->shop))->toBeFalse()
            ->and(Gate::forUser($pending)->allows('view', $pending->shop))->toBeTrue()
            ->and(Gate::forUser($this->moderator)->allows('view', $pending->shop))->toBeTrue();
    });

    it('solo deja crear taller a un artesano que aún no tiene uno', function () {
        $newArtisan = userWithRole('artisan');

        expect(Gate::forUser($newArtisan)->allows('create', Shop::class))->toBeTrue()
            ->and(Gate::forUser($this->owner)->allows('create', Shop::class))->toBeFalse()
            ->and(Gate::forUser($this->customer)->allows('create', Shop::class))->toBeFalse();
    });

    it('aísla la edición y baja del taller a su titular', function () {
        $shop = $this->owner->shop;

        expect(Gate::forUser($this->owner)->allows('update', $shop))->toBeTrue()
            ->and(Gate::forUser($this->owner)->allows('delete', $shop))->toBeTrue()
            ->and(Gate::forUser($this->otherArtisan)->allows('update', $shop))->toBeFalse()
            ->and(Gate::forUser($this->otherArtisan)->allows('delete', $shop))->toBeFalse()
            ->and(Gate::forUser($this->moderator)->allows('update', $shop))->toBeFalse()
            ->and(Gate::forUser($this->admin)->allows('update', $shop))->toBeTrue();
    });

    it('reserva la verificación a moderación y administración', function () {
        $shop = $this->owner->shop;

        expect(Gate::forUser($this->moderator)->allows('verify', $shop))->toBeTrue()
            ->and(Gate::forUser($this->admin)->allows('verify', $shop))->toBeTrue()
            ->and(Gate::forUser($this->owner)->allows('verify', $shop))->toBeFalse()
            ->and(Gate::forUser($this->customer)->allows('verify', $shop))->toBeFalse();
    });
});

describe('ProductPolicy', function () {
    it('muestra al público solo piezas publicadas de talleres verificados', function () {
        $published = catalogProduct($this->owner->shop);
        $draft = catalogProduct($this->owner->shop, ['status' => Product::STATUS_DRAFT]);

        expect(Gate::forUser(null)->allows('viewAny', Product::class))->toBeTrue()
            ->and(Gate::forUser(null)->allows('view', $published))->toBeTrue()
            ->and(Gate::forUser(null)->allows('view', $draft))->toBeFalse()
            ->and(Gate::forUser($this->otherArtisan)->allows('view', $draft))->toBeFalse()
            ->and(Gate::forUser($this->owner)->allows('view', $draft))->toBeTrue()
            ->and(Gate::forUser($this->moderator)->allows('view', $draft))->toBeTrue();
    });

    it('solo deja crear piezas a artesanos con taller verificado', function () {
        $unverified = artisanWithShop(['is_verified' => false]);
        $withoutShop = userWithRole('artisan');

        expect(Gate::forUser($this->owner)->allows('create', Product::class))->toBeTrue()
            ->and(Gate::forUser($unverified)->allows('create', Product::class))->toBeFalse()
            ->and(Gate::forUser($withoutShop)->allows('create', Product::class))->toBeFalse()
            ->and(Gate::forUser($this->customer)->allows('create', Product::class))->toBeFalse();
    });

    it('aísla la edición y el borrado al taller dueño de la pieza', function () {
        $product = catalogProduct($this->owner->shop);

        expect(Gate::forUser($this->owner)->allows('update', $product))->toBeTrue()
            ->and(Gate::forUser($this->owner)->allows('delete', $product))->toBeTrue()
            ->and(Gate::forUser($this->otherArtisan)->allows('update', $product))->toBeFalse()
            ->and(Gate::forUser($this->otherArtisan)->allows('delete', $product))->toBeFalse()
            ->and(Gate::forUser($this->customer)->allows('update', $product))->toBeFalse()
            ->and(Gate::forUser($this->admin)->allows('delete', $product))->toBeTrue();
    });
});

describe('OrderPolicy y OrderShopPolicy', function () {
    beforeEach(function () {
        $this->vase = catalogProduct($this->owner->shop);
        $this->order = checkout($this->customer, [['product_id' => $this->vase->id, 'quantity' => 1]]);
        $this->suborder = $this->order->orderShops->sole();
    });

    it('deja ver el pedido al comprador, a moderación y al taller participante', function () {
        expect(Gate::forUser($this->customer)->allows('view', $this->order))->toBeTrue()
            ->and(Gate::forUser($this->moderator)->allows('view', $this->order))->toBeTrue()
            ->and(Gate::forUser($this->owner)->allows('view', $this->order))->toBeTrue()
            ->and(Gate::forUser($this->otherCustomer)->allows('view', $this->order))->toBeFalse()
            ->and(Gate::forUser($this->otherArtisan)->allows('view', $this->order))->toBeFalse()
            ->and(Gate::forUser($this->courier)->allows('view', $this->order))->toBeFalse();
    });

    it('solo deja comprar a clientes', function () {
        expect(Gate::forUser($this->customer)->allows('create', Order::class))->toBeTrue()
            ->and(Gate::forUser($this->owner)->allows('create', Order::class))->toBeFalse()
            ->and(Gate::forUser($this->courier)->allows('create', Order::class))->toBeFalse();
    });

    it('solo deja cancelar al comprador y antes de la preparación', function () {
        expect(Gate::forUser($this->customer)->allows('cancel', $this->order))->toBeTrue()
            ->and(Gate::forUser($this->otherCustomer)->allows('cancel', $this->order))->toBeFalse();

        $this->order->update(['status' => Order::STATUS_PREPARING]);

        expect(Gate::forUser($this->customer)->allows('cancel', $this->order))->toBeFalse();
    });

    it('solo deja actualizar el transporte al repartidor', function () {
        expect(Gate::forUser($this->courier)->allows('updateDeliveryStatus', $this->order))->toBeTrue()
            ->and(Gate::forUser($this->owner)->allows('updateDeliveryStatus', $this->order))->toBeFalse()
            ->and(Gate::forUser($this->customer)->allows('updateDeliveryStatus', $this->order))->toBeFalse();
    });

    it('aísla el subpedido: cada artesano gestiona solo el de su taller', function () {
        expect(Gate::forUser($this->owner)->allows('manageSuborder', $this->suborder))->toBeTrue()
            ->and(Gate::forUser($this->otherArtisan)->allows('manageSuborder', $this->suborder))->toBeFalse()
            ->and(Gate::forUser($this->customer)->allows('manageSuborder', $this->suborder))->toBeFalse()
            ->and(Gate::forUser($this->customer)->allows('view', $this->suborder))->toBeTrue()
            ->and(Gate::forUser($this->otherArtisan)->allows('view', $this->suborder))->toBeFalse();
    });
});

describe('ReviewPolicy', function () {
    it('exige una compra entregada y una sola reseña por pieza', function () {
        $product = catalogProduct($this->owner->shop);

        expect(Gate::forUser($this->customer)->allows('create', [Review::class, $product]))->toBeFalse();

        $order = checkout($this->customer, [['product_id' => $product->id, 'quantity' => 1]]);

        expect(Gate::forUser($this->customer)->allows('create', [Review::class, $product]))->toBeFalse();

        $order->update(['status' => Order::STATUS_DELIVERED]);

        expect(Gate::forUser($this->customer)->allows('create', [Review::class, $product]))->toBeTrue()
            ->and(Gate::forUser($this->otherCustomer)->allows('create', [Review::class, $product]))->toBeFalse()
            ->and(Gate::forUser($this->owner)->allows('create', [Review::class, $product]))->toBeFalse();

        Review::factory()->create(['product_id' => $product->id, 'customer_id' => $this->customer->id, 'order_id' => $order->id]);

        expect(Gate::forUser($this->customer)->allows('create', [Review::class, $product]))->toBeFalse();
    });

    it('solo deja responder al taller de la pieza y borrar al autor o a moderación', function () {
        $product = catalogProduct($this->owner->shop);
        $review = Review::factory()->create(['product_id' => $product->id, 'customer_id' => $this->customer->id]);

        expect(Gate::forUser($this->owner)->allows('reply', $review))->toBeTrue()
            ->and(Gate::forUser($this->otherArtisan)->allows('reply', $review))->toBeFalse()
            ->and(Gate::forUser($this->customer)->allows('delete', $review))->toBeTrue()
            ->and(Gate::forUser($this->moderator)->allows('delete', $review))->toBeTrue()
            ->and(Gate::forUser($this->otherCustomer)->allows('delete', $review))->toBeFalse();
    });
});

describe('DisputePolicy', function () {
    beforeEach(function () {
        $product = catalogProduct($this->owner->shop);
        $this->order = checkout($this->customer, [['product_id' => $product->id, 'quantity' => 1]]);
        $this->dispute = Dispute::factory()->create([
            'order_id' => $this->order->id,
            'customer_id' => $this->customer->id,
            'shop_id' => $this->owner->shop->id,
        ]);
    });

    it('solo deja abrir una disputa al comprador del pedido', function () {
        expect(Gate::forUser($this->customer)->allows('create', [Dispute::class, $this->order]))->toBeTrue()
            ->and(Gate::forUser($this->otherCustomer)->allows('create', [Dispute::class, $this->order]))->toBeFalse()
            ->and(Gate::forUser($this->owner)->allows('create', [Dispute::class, $this->order]))->toBeFalse();
    });

    it('limita ver y responder al comprador, al taller afectado y a moderación', function () {
        foreach ([$this->customer, $this->owner, $this->moderator] as $allowed) {
            expect(Gate::forUser($allowed)->allows('view', $this->dispute))->toBeTrue()
                ->and(Gate::forUser($allowed)->allows('reply', $this->dispute))->toBeTrue();
        }

        foreach ([$this->otherCustomer, $this->otherArtisan, $this->courier] as $denied) {
            expect(Gate::forUser($denied)->allows('view', $this->dispute))->toBeFalse();
        }
    });

    it('cierra las respuestas y la resolución cuando la disputa está resuelta', function () {
        expect(Gate::forUser($this->moderator)->allows('resolve', $this->dispute))->toBeTrue()
            ->and(Gate::forUser($this->owner)->allows('resolve', $this->dispute))->toBeFalse();

        $this->dispute->update(['status' => 'resolved']);

        expect(Gate::forUser($this->customer)->allows('reply', $this->dispute))->toBeFalse()
            ->and(Gate::forUser($this->moderator)->allows('resolve', $this->dispute))->toBeFalse();
    });
});
