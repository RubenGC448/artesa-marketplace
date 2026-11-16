<?php

/*
|--------------------------------------------------------------------------
| Sprint 4 · Scopes (7 exigidos, 4 complejos)
|--------------------------------------------------------------------------
| Simples:   Product::active, Shop::verified, Order::withStatus
| Complejos: Product::search, Product::bestSelling, Product::topRated,
|            Order::visibleTo
| Extra:     Product::inCatalog, Product/ProductVariant::lowStock,
|            Order::unpaidOlderThan, Shop::pendingVerification
*/

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderShop;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\Shop;
use App\Models\Tag;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

/**
 * Registra una venta directa (sin pasar por checkout) para preparar escenarios.
 */
function sellProduct(Product $product, int $quantity, string $orderStatus, $date = null, string $lineStatus = 'ready'): void
{
    $order = Order::factory()->create(['status' => $orderStatus, 'created_at' => $date ?? now()]);
    $suborder = OrderShop::factory()->create(['order_id' => $order->id, 'shop_id' => $product->shop_id]);

    OrderProduct::factory()->create([
        'order_id' => $order->id,
        'order_shop_id' => $suborder->id,
        'product_id' => $product->id,
        'quantity' => $quantity,
        'line_status' => $lineStatus,
    ]);
}

it('active e inCatalog devuelven solo piezas publicadas (y de talleres verificados)', function () {
    $verified = artisanWithShop()->shop;
    $unverified = artisanWithShop(['is_verified' => false])->shop;

    $published = catalogProduct($verified);
    catalogProduct($verified, ['status' => Product::STATUS_DRAFT]);
    $hidden = catalogProduct($unverified);

    expect(Product::active()->pluck('id')->all())->toEqualCanonicalizing([$published->id, $hidden->id])
        ->and(Product::inCatalog()->pluck('id')->all())->toEqual([$published->id]);
});

it('verified y pendingVerification separan los talleres por estado de verificación', function () {
    $ok = artisanWithShop()->shop;
    $pending = artisanWithShop(['is_verified' => false])->shop;

    expect(Shop::verified()->pluck('id')->all())->toEqual([$ok->id])
        ->and(Shop::pendingVerification()->pluck('id')->all())->toEqual([$pending->id]);
});

it('withStatus filtra por uno o varios estados', function () {
    Order::factory()->create(['status' => 'paid']);
    Order::factory()->create(['status' => 'delivered']);
    Order::factory()->create(['status' => 'cancelled']);

    expect(Order::withStatus('paid')->count())->toBe(1)
        ->and(Order::withStatus(['paid', 'delivered'])->count())->toBe(2);
});

it('search busca en título, etiquetas, categoría padre y taller', function () {
    $shop = artisanWithShop(['name' => 'Taller Encina', 'craft_type' => 'Ebanistería'])->shop;
    $parent = Category::factory()->create(['name' => 'Cerámica']);
    $child = Category::factory()->create(['name' => 'Vajillas', 'parent_id' => $parent->id]);
    $kitchen = Category::factory()->create(['name' => 'Cocina']);

    $plate = catalogProduct($shop, ['title' => 'Plato hondo', 'description' => 'Barro cocido', 'category_id' => $child->id]);
    $board = catalogProduct($shop, ['title' => 'Tabla de cortar', 'description' => 'Madera maciza', 'category_id' => $kitchen->id]);
    $board->tags()->attach(Tag::factory()->create(['name' => 'Lino natural'])->id);

    expect(Product::search('plato')->pluck('id')->all())->toEqual([$plate->id])
        ->and(Product::search('lino')->pluck('id')->all())->toEqual([$board->id])
        ->and(Product::search('Cerámica')->pluck('id')->all())->toEqual([$plate->id])
        ->and(Product::search('Ebanister')->count())->toBe(2)
        ->and(Product::search('inexistente')->count())->toBe(0)
        ->and(Product::search('   ')->count())->toBe(2);
});

it('bestSelling suma unidades de pedidos entregados en el periodo y ordena', function () {
    $shop = artisanWithShop()->shop;
    [$p1, $p2, $p3, $p4] = [catalogProduct($shop), catalogProduct($shop), catalogProduct($shop), catalogProduct($shop)];

    sellProduct($p1, 3, 'delivered');
    sellProduct($p1, 5, 'pending');                        // no entregado: no cuenta
    sellProduct($p2, 5, 'delivered');
    sellProduct($p3, 10, 'delivered', now()->subDays(60)); // fuera del periodo
    sellProduct($p4, 7, 'delivered', null, 'cancelled');   // línea cancelada: no cuenta

    $ranking = Product::bestSelling(now()->subDays(30), now()->addMinute())->get();

    expect($ranking->pluck('id')->all())->toEqual([$p2->id, $p1->id])
        ->and((int) $ranking->first()->units_sold)->toBe(5)
        ->and((int) $ranking->last()->units_sold)->toBe(3)
        ->and(Product::bestSelling()->pluck('id')->first())->toBe($p3->id); // sin periodo: histórico
});

it('topRated exige un mínimo de reseñas aprobadas y una media mínima', function () {
    $shop = artisanWithShop()->shop;
    [$p1, $p2, $p3, $p4] = [catalogProduct($shop), catalogProduct($shop), catalogProduct($shop), catalogProduct($shop)];

    Review::factory()->create(['product_id' => $p1->id, 'rating' => 5, 'is_approved' => true]);
    Review::factory()->create(['product_id' => $p1->id, 'rating' => 5, 'is_approved' => true]);
    Review::factory()->create(['product_id' => $p2->id, 'rating' => 4, 'is_approved' => true]);
    Review::factory()->create(['product_id' => $p2->id, 'rating' => 3, 'is_approved' => true]); // media 3,5
    Review::factory()->create(['product_id' => $p3->id, 'rating' => 4, 'is_approved' => true]);
    Review::factory()->create(['product_id' => $p3->id, 'rating' => 1, 'is_approved' => false]); // no aprobada: no cuenta

    $top = Product::topRated(1, 4.0)->get();

    expect($top->pluck('id')->all())->toEqual([$p1->id, $p3->id])
        ->and((float) $top->first()->avg_rating)->toBe(5.0)
        ->and((int) $top->first()->approved_reviews_count)->toBe(2)
        ->and(Product::topRated(2, 4.0)->pluck('id')->all())->toEqual([$p1->id])
        ->and(Product::topRated(1, 3.0)->pluck('id')->all())->not->toContain($p4->id);
});

it('visibleTo limita los pedidos según el rol de quien consulta', function () {
    $customer = customerWithAddress();
    $otherCustomer = customerWithAddress();
    $artisan = artisanWithShop();
    $otherArtisan = artisanWithShop();

    $mine = checkout($customer, [['product_id' => catalogProduct($artisan->shop)->id, 'quantity' => 1]]);
    $notMine = checkout($otherCustomer, [['product_id' => catalogProduct($otherArtisan->shop)->id, 'quantity' => 1]]);

    expect(Order::visibleTo($customer)->pluck('id')->all())->toEqual([$mine->id])
        ->and(Order::visibleTo($artisan)->pluck('id')->all())->toEqual([$mine->id])
        ->and(Order::visibleTo($otherArtisan)->pluck('id')->all())->toEqual([$notMine->id])
        ->and(Order::visibleTo(userWithRole('moderator'))->count())->toBe(2)
        ->and(Order::visibleTo(userWithRole('artisan'))->count())->toBe(0); // artesano sin taller
});

it('lowStock y unpaidOlderThan localizan stock bajo y pedidos abandonados', function () {
    $shop = artisanWithShop()->shop;
    $scarce = catalogProduct($shop, ['stock' => 2]);
    catalogProduct($shop, ['stock' => 20]);
    $withVariants = catalogProduct($shop, ['stock' => 0]);
    $variant = ProductVariant::factory()->create(['product_id' => $withVariants->id, 'stock' => 1]);

    $old = Order::factory()->pending()->create(['created_at' => now()->subDays(3)]);
    Order::factory()->pending()->create();
    Order::factory()->paid()->create(['created_at' => now()->subDays(3)]);

    expect(Product::lowStock(3)->pluck('id')->all())->toEqual([$scarce->id]) // el de variantes se mide por variante
        ->and(ProductVariant::lowStock(3)->pluck('id')->all())->toEqual([$variant->id])
        ->and(Order::unpaidOlderThan(now()->subHours(48))->pluck('id')->all())->toEqual([$old->id]);
});
