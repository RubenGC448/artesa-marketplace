<?php

/*
|--------------------------------------------------------------------------
| Sprint 2 · Modelo de datos y relaciones (convertido a sintaxis Pest)
|--------------------------------------------------------------------------
*/

use App\Models\Address;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderShop;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\Shipment;
use App\Models\Shop;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('relaciona User y Shop 1:1', function () {
    $user = User::create(['name' => 'Maestro Alfarero', 'email' => 'alfarero@artesa.local', 'password' => 'secret123', 'phone' => '600112233']);

    $shop = Shop::create([
        'user_id' => $user->id,
        'name' => 'Taller Barro Vivo',
        'slug' => 'taller-barro-vivo',
        'cif_nif' => 'B12345678',
        'craft_type' => 'Alfarería',
        'is_verified' => true,
        'commission_rate' => 10.00,
    ]);

    expect($user->shop->id)->toBe($shop->id)
        ->and($shop->user->id)->toBe($user->id);
});

it('relaciona User y Address 1:N con dirección predeterminada', function () {
    $user = User::create(['name' => 'Cliente Artesa', 'email' => 'cliente@artesa.local', 'password' => 'secret123']);

    $home = Address::create([
        'user_id' => $user->id, 'name' => 'Casa', 'street' => 'Calle Sierpes 15, 2ºA', 'city' => 'Sevilla',
        'province' => 'Sevilla', 'postal_code' => '41004', 'country' => 'ES', 'is_default' => true,
    ]);
    Address::create([
        'user_id' => $user->id, 'name' => 'Oficina', 'street' => 'Avenida de la Constitución 4', 'city' => 'Sevilla',
        'province' => 'Sevilla', 'postal_code' => '41001', 'country' => 'ES', 'is_default' => false,
    ]);

    expect($user->addresses)->toHaveCount(2)
        ->and($user->defaultAddress->id)->toBe($home->id)
        ->and($home->formatted_address)->toBe('Calle Sierpes 15, 2ºA, 41004 Sevilla (Sevilla), ES');
});

it('relaciona Category consigo misma (padre e hijas)', function () {
    $parent = Category::create(['name' => 'Cerámica y Barro', 'slug' => 'ceramica-y-barro']);
    $child = Category::create(['parent_id' => $parent->id, 'name' => 'Vajillas', 'slug' => 'vajillas']);
    Category::create(['parent_id' => $parent->id, 'name' => 'Jarrones', 'slug' => 'jarrones']);

    expect($parent->children)->toHaveCount(2)
        ->and($child->parent->id)->toBe($parent->id);
});

it('relaciona Product con taller, categoría, variantes e imágenes', function () {
    $artisan = User::create(['name' => 'Ebanista', 'email' => 'ebanista@artesa.local', 'password' => 'secret123']);
    $shop = Shop::create(['user_id' => $artisan->id, 'name' => 'Maderas Nobles', 'slug' => 'maderas-nobles', 'cif_nif' => 'B87654321']);
    $category = Category::create(['name' => 'Madera', 'slug' => 'madera']);

    $product = Product::create([
        'shop_id' => $shop->id, 'category_id' => $category->id, 'title' => 'Mesa de Olivo Rústica',
        'slug' => 'mesa-de-olivo-rustica', 'base_price' => 150.00, 'stock' => 5, 'status' => 'active',
    ]);

    ProductVariant::create(['product_id' => $product->id, 'name' => 'Nogal', 'price_modifier' => 20.00, 'stock' => 2]);
    ProductVariant::create(['product_id' => $product->id, 'name' => 'Barniz mate', 'price_modifier' => 0.00, 'stock' => 3]);
    $cover = ProductImage::create(['product_id' => $product->id, 'image_path' => 'products/mesa-1.jpg', 'is_primary' => true, 'sort_order' => 1]);
    ProductImage::create(['product_id' => $product->id, 'image_path' => 'products/mesa-2.jpg', 'is_primary' => false, 'sort_order' => 2]);

    expect($product->variants)->toHaveCount(2)
        ->and($product->images)->toHaveCount(2)
        ->and($product->primaryImage->id)->toBe($cover->id)
        ->and($product->shop->id)->toBe($shop->id)
        ->and($product->category->id)->toBe($category->id)
        ->and($product->available_stock)->toBe(5); // con variantes: suma de variantes
});

it('relaciona Product y Tag N:M con columnas en la pivote', function () {
    $artisan = User::create(['name' => 'Artesano', 'email' => 'art@artesa.local', 'password' => 'secret123']);
    $shop = Shop::create(['user_id' => $artisan->id, 'name' => 'Taller', 'slug' => 'taller', 'cif_nif' => 'B99887766']);
    $product = Product::create(['shop_id' => $shop->id, 'title' => 'Cuenco', 'slug' => 'cuenco', 'base_price' => 25.00]);

    $clay = Tag::create(['name' => 'Barro Rojo', 'slug' => 'barro-rojo']);
    $origin = Tag::create(['name' => 'Denominación Bailén', 'slug' => 'denominacion-bailen']);

    $product->tags()->attach($clay->id, ['is_primary' => true, 'origin_certified' => false]);
    $product->tags()->attach($origin->id, ['is_primary' => false, 'origin_certified' => true]);

    expect($product->tags)->toHaveCount(2)
        ->and((bool) $product->tags->firstWhere('id', $clay->id)->pivot->is_primary)->toBeTrue()
        ->and((bool) $product->tags->firstWhere('id', $origin->id)->pivot->origin_certified)->toBeTrue()
        ->and($clay->products)->toHaveCount(1);
});

it('maneja desde código la pivote extendida order_product con importes congelados', function () {
    $customer = User::create(['name' => 'Comprador', 'email' => 'comprador@artesa.local', 'password' => 'secret123']);
    $artisan = User::create(['name' => 'Creador', 'email' => 'creador@artesa.local', 'password' => 'secret123']);
    $shop = Shop::create(['user_id' => $artisan->id, 'name' => 'Taller Cuero', 'slug' => 'taller-cuero', 'cif_nif' => 'B11223344']);
    $product = Product::create(['shop_id' => $shop->id, 'title' => 'Cinturón', 'slug' => 'cinturon', 'base_price' => 45.00]);

    $order = Order::create(['order_number' => 'ART-TEST-01', 'customer_id' => $customer->id, 'status' => 'pending', 'subtotal' => 90, 'shipping_cost' => 5, 'total' => 95]);
    $orderShop = OrderShop::create(['order_id' => $order->id, 'shop_id' => $shop->id, 'status' => 'pending', 'subtotal' => 90, 'shipping_cost' => 5, 'total' => 95]);

    $order->products()->attach($product->id, [
        'order_shop_id' => $orderShop->id,
        'quantity' => 2,
        'unit_price' => 45.00,
        'applied_tax' => 21.00,
        'artisan_commission_rate' => 10.00,
        'line_status' => 'pending',
        'customization_notes' => 'Talla 95 cm',
    ]);

    $line = $order->products()->first()->pivot;

    expect($line)->toBeInstanceOf(OrderProduct::class)
        ->and($line->subtotal)->toBe(90.0)          // 45 × 2
        ->and($line->artisan_earnings)->toBe(81.0)  // (45 − 10 %) × 2
        ->and($line->tax_amount)->toBe(15.62);      // IVA incluido en 90 €

    $order->products()->updateExistingPivot($product->id, ['line_status' => 'ready']);

    expect($order->products()->first()->pivot->line_status)->toBe('ready');
});

it('modela un pedido multitienda con pagos, envíos e historial', function () {
    $customer = User::create(['name' => 'Cliente', 'email' => 'multi@artesa.local', 'password' => 'secret123']);
    $shop1 = Shop::create(['user_id' => User::create(['name' => 'A1', 'email' => 'a1@artesa.local', 'password' => 'x'])->id, 'name' => 'Cerámica', 'slug' => 'ceramica', 'cif_nif' => 'B11111111']);
    $shop2 = Shop::create(['user_id' => User::create(['name' => 'A2', 'email' => 'a2@artesa.local', 'password' => 'x'])->id, 'name' => 'Textil', 'slug' => 'textil', 'cif_nif' => 'B22222222']);

    $order = Order::create(['order_number' => 'ART-MULTI-01', 'customer_id' => $customer->id, 'status' => 'paid', 'subtotal' => 80, 'shipping_cost' => 10, 'total' => 90]);
    OrderShop::create(['order_id' => $order->id, 'shop_id' => $shop1->id, 'status' => 'preparing', 'subtotal' => 30, 'shipping_cost' => 5, 'total' => 35]);
    $suborder2 = OrderShop::create(['order_id' => $order->id, 'shop_id' => $shop2->id, 'status' => 'shipped', 'subtotal' => 50, 'shipping_cost' => 5, 'total' => 55]);

    Shipment::create(['order_shop_id' => $suborder2->id, 'carrier' => 'MRW', 'tracking_number' => 'MRW-1', 'status' => 'in_transit', 'shipped_at' => now()]);
    Payment::create(['order_id' => $order->id, 'amount' => 90, 'method' => 'stripe', 'status' => 'completed', 'paid_at' => now()]);
    OrderStatusHistory::create(['order_id' => $order->id, 'status' => 'paid', 'created_at' => now()]);

    expect($order->orderShops)->toHaveCount(2)
        ->and($suborder2->latestShipment->tracking_number)->toBe('MRW-1')
        ->and($order->payments)->toHaveCount(1)
        ->and($order->statusHistory)->toHaveCount(1);
});

it('aplica borrado lógico y restauración', function () {
    $user = User::create(['name' => 'Borrable', 'email' => 'borrar@artesa.local', 'password' => 'secret123']);
    $shop = Shop::create(['user_id' => $user->id, 'name' => 'Taller', 'slug' => 'taller-borrable', 'cif_nif' => 'B00000000']);
    $product = Product::create(['shop_id' => $shop->id, 'title' => 'Producto', 'slug' => 'producto-borrable', 'base_price' => 10]);

    $product->delete();

    expect($product->trashed())->toBeTrue()
        ->and(Product::find($product->id))->toBeNull()
        ->and(Product::withTrashed()->find($product->id))->not->toBeNull();

    $product->restore();

    expect(Product::find($product->id))->not->toBeNull();
});

it('relaciona favoritos (N:M) y reseñas', function () {
    $fan = User::create(['name' => 'Fan', 'email' => 'fan@artesa.local', 'password' => 'secret123']);
    $artisan = User::create(['name' => 'Joyero', 'email' => 'joyero@artesa.local', 'password' => 'secret123']);
    $shop = Shop::create(['user_id' => $artisan->id, 'name' => 'Plata Viva', 'slug' => 'plata-viva', 'cif_nif' => 'B44332211']);
    $product = Product::create(['shop_id' => $shop->id, 'title' => 'Anillo', 'slug' => 'anillo', 'base_price' => 60]);

    $fan->wishlistProducts()->attach($product->id);

    $order = Order::create(['order_number' => 'ART-REV-01', 'customer_id' => $fan->id, 'subtotal' => 60, 'total' => 60]);
    $review = Review::create(['product_id' => $product->id, 'customer_id' => $fan->id, 'order_id' => $order->id, 'rating' => 5, 'is_approved' => true]);

    expect($fan->wishlistProducts->first()->id)->toBe($product->id)
        ->and($product->reviews->first()->rating)->toBe(5)
        ->and($review->customer->id)->toBe($fan->id);
});
