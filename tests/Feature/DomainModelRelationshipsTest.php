<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Dispute;
use App\Models\DisputeMessage;
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
use Tests\TestCase;

class DomainModelRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Prueba la relación 1:1 entre User y Shop.
     */
    public function test_user_and_shop_one_to_one_relationship(): void
    {
        $user = User::create([
            'name' => 'Maestro Alfarero',
            'email' => 'alfarero@artesa.local',
            'password' => 'secret123',
            'phone' => '600112233',
        ]);

        $shop = Shop::create([
            'user_id' => $user->id,
            'name' => 'Taller Barro Vivo',
            'slug' => 'taller-barro-vivo',
            'cif_nif' => 'B12345678',
            'bio' => 'Taller tradicional de alfarería en barro rojo.',
            'craft_type' => 'Alfarería',
            'latitude' => 37.3890924,
            'longitude' => -5.9844589,
            'is_verified' => true,
            'commission_rate' => 10.00,
        ]);

        $this->assertInstanceOf(Shop::class, $user->shop);
        $this->assertEquals($shop->id, $user->shop->id);
        $this->assertInstanceOf(User::class, $shop->user);
        $this->assertEquals($user->id, $shop->user->id);
    }

    /**
     * Prueba la relación 1:N entre User y Address (con dirección default).
     */
    public function test_user_and_addresses_one_to_many_relationship(): void
    {
        $user = User::create([
            'name' => 'Cliente Artesa',
            'email' => 'cliente@artesa.local',
            'password' => 'secret123',
        ]);

        $address1 = Address::create([
            'user_id' => $user->id,
            'name' => 'Casa',
            'street' => 'Calle Sierpes 15, 2ºA',
            'city' => 'Sevilla',
            'province' => 'Sevilla',
            'postal_code' => '41004',
            'country' => 'ES',
            'is_default' => true,
        ]);

        $address2 = Address::create([
            'user_id' => $user->id,
            'name' => 'Oficina',
            'street' => 'Avenida de la Constitución 4',
            'city' => 'Sevilla',
            'province' => 'Sevilla',
            'postal_code' => '41001',
            'country' => 'ES',
            'is_default' => false,
        ]);

        $this->assertCount(2, $user->addresses);
        $this->assertEquals($address1->id, $user->defaultAddress->id);
        $this->assertEquals('Calle Sierpes 15, 2ºA, 41004 Sevilla (Sevilla), ES', $address1->formatted_address);
    }

    /**
     * Prueba la jerarquía auto-referenciada 1:N en Category.
     */
    public function test_hierarchical_category_self_referencing_relationship(): void
    {
        $parent = Category::create([
            'name' => 'Cerámica y Barro',
            'slug' => 'ceramica-y-barro',
            'icon' => 'pottery.svg',
        ]);

        $child1 = Category::create([
            'parent_id' => $parent->id,
            'name' => 'Vajillas',
            'slug' => 'vajillas',
        ]);

        $child2 = Category::create([
            'parent_id' => $parent->id,
            'name' => 'Jarrones y Vasijas',
            'slug' => 'jarrones-y-vasijas',
        ]);

        $this->assertCount(2, $parent->children);
        $this->assertEquals($parent->id, $child1->parent->id);
        $this->assertEquals($parent->id, $child2->parent->id);
    }

    /**
     * Prueba las relaciones 1:N de Product con Shop, Category, Variants e Images.
     */
    public function test_product_with_variants_and_images_relationships(): void
    {
        $artisan = User::create(['name' => 'Ebanista', 'email' => 'ebanista@artesa.local', 'password' => 'secret123']);
        $shop = Shop::create(['user_id' => $artisan->id, 'name' => 'Maderas Nobles', 'slug' => 'maderas-nobles', 'cif_nif' => 'B87654321']);
        $cat = Category::create(['name' => 'Madera', 'slug' => 'madera']);

        $product = Product::create([
            'shop_id' => $shop->id,
            'category_id' => $cat->id,
            'title' => 'Mesa de Olivo Rústica',
            'slug' => 'mesa-de-olivo-rustica',
            'base_price' => 150.00,
            'stock' => 5,
            'sku' => 'TAB-OLV-001',
            'status' => 'active',
            'featured' => true,
        ]);

        $variant1 = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Acabado Nogal Natural',
            'price_modifier' => 20.00,
            'stock' => 2,
        ]);

        $variant2 = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Acabado Barniz Mate',
            'price_modifier' => 0.00,
            'stock' => 3,
        ]);

        $img1 = ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/mesa-portada.jpg',
            'is_primary' => true,
            'sort_order' => 1,
        ]);

        $img2 = ProductImage::create([
            'product_id' => $product->id,
            'image_path' => 'products/mesa-detalle.jpg',
            'is_primary' => false,
            'sort_order' => 2,
        ]);

        $this->assertCount(2, $product->variants);
        $this->assertCount(2, $product->images);
        $this->assertEquals($img1->id, $product->primaryImage->id);
        $this->assertEquals($shop->id, $product->shop->id);
        $this->assertEquals($cat->id, $product->category->id);
    }

    /**
     * Prueba la relación N:M con atributos pivote entre Product y Tag.
     */
    public function test_product_and_tags_many_to_many_with_pivot_columns(): void
    {
        $artisan = User::create(['name' => 'Artesano', 'email' => 'art@artesa.local', 'password' => 'secret123']);
        $shop = Shop::create(['user_id' => $artisan->id, 'name' => 'Taller Artesano', 'slug' => 'taller-artesano', 'cif_nif' => 'B99887766']);
        $product = Product::create(['shop_id' => $shop->id, 'title' => 'Cuenco Cerámico', 'slug' => 'cuenco-ceramico', 'base_price' => 25.00]);

        $tag1 = Tag::create(['name' => 'Barro Rojo', 'slug' => 'barro-rojo']);
        $tag2 = Tag::create(['name' => 'Denominación Bailén', 'slug' => 'denominacion-bailen']);

        $product->tags()->attach($tag1->id, ['is_primary' => true, 'origin_certified' => false]);
        $product->tags()->attach($tag2->id, ['is_primary' => false, 'origin_certified' => true]);

        $this->assertCount(2, $product->tags);
        $firstTag = $product->tags->firstWhere('id', $tag1->id);
        $this->assertTrue((bool) $firstTag->pivot->is_primary);
        $this->assertFalse((bool) $firstTag->pivot->origin_certified);

        $secondTag = $product->tags->firstWhere('id', $tag2->id);
        $this->assertTrue((bool) $secondTag->pivot->origin_certified);
        $this->assertCount(1, $tag1->products);
    }

    /**
     * Prueba la relación N:M avanzada con pivote extendido OrderProduct y cálculos en código.
     */
    public function test_order_product_pivot_with_frozen_prices_and_calculations(): void
    {
        $customer = User::create(['name' => 'Comprador', 'email' => 'comprador@artesa.local', 'password' => 'secret123']);
        $artisan = User::create(['name' => 'Artesano Creador', 'email' => 'creador@artesa.local', 'password' => 'secret123']);
        $shop = Shop::create(['user_id' => $artisan->id, 'name' => 'Taller Cuero', 'slug' => 'taller-cuero', 'cif_nif' => 'B11223344']);
        $product = Product::create(['shop_id' => $shop->id, 'title' => 'Cinturón Artesano', 'slug' => 'cinturon-artesano', 'base_price' => 45.00]);

        $order = Order::create([
            'order_number' => 'ART-2026-TEST-01',
            'customer_id' => $customer->id,
            'status' => 'pending',
            'subtotal' => 90.00,
            'shipping_cost' => 5.00,
            'total' => 95.00,
        ]);

        $orderShop = OrderShop::create([
            'order_id' => $order->id,
            'shop_id' => $shop->id,
            'status' => 'pending',
            'subtotal' => 90.00,
            'shipping_cost' => 5.00,
            'total' => 95.00,
        ]);

        // Vinculamos producto al pedido con precio, comisión e IVA congelados
        $order->products()->attach($product->id, [
            'order_shop_id' => $orderShop->id,
            'quantity' => 2,
            'unit_price' => 45.00,
            'applied_tax' => 21.00,
            'artisan_commission_rate' => 10.00,
            'line_status' => 'pending',
            'customization_notes' => 'Talla 95cm con hebilla dorada',
        ]);

        // Verificamos que la relación recupera el modelo pivote OrderProduct
        $purchasedProduct = $order->products()->first();
        $this->assertInstanceOf(OrderProduct::class, $purchasedProduct->pivot);

        // Verificamos los cálculos auxiliares del modelo pivote
        // Subtotal = 45.00 * 2 = 90.00
        $this->assertEquals(90.00, $purchasedProduct->pivot->subtotal);
        // Ganancia neta del artesano = (45.00 - 10% comisión) * 2 = 40.50 * 2 = 81.00
        $this->assertEquals(81.00, $purchasedProduct->pivot->artisan_earnings);

        // Manipulación de datos del pivote desde código
        $order->products()->updateExistingPivot($product->id, [
            'line_status' => 'ready',
        ]);

        $updatedProduct = $order->products()->first();
        $this->assertEquals('ready', $updatedProduct->pivot->line_status);
    }

    /**
     * Prueba el flujo completo de pedido multi-tienda: pagos, envíos y auditoría de estados.
     */
    public function test_multi_shop_order_workflow_with_payments_and_shipments(): void
    {
        $customer = User::create(['name' => 'Cliente Multi', 'email' => 'multi@artesa.local', 'password' => 'secret123']);
        $artisan1 = User::create(['name' => 'Artesano 1', 'email' => 'art1@artesa.local', 'password' => 'secret123']);
        $artisan2 = User::create(['name' => 'Artesano 2', 'email' => 'art2@artesa.local', 'password' => 'secret123']);
        $shop1 = Shop::create(['user_id' => $artisan1->id, 'name' => 'Taller Cerámica', 'slug' => 'taller-ceramica', 'cif_nif' => 'B11111111']);
        $shop2 = Shop::create(['user_id' => $artisan2->id, 'name' => 'Taller Textil', 'slug' => 'taller-textil', 'cif_nif' => 'B22222222']);

        $order = Order::create([
            'order_number' => 'ART-2026-MULTI-01',
            'customer_id' => $customer->id,
            'status' => 'paid',
            'subtotal' => 80.00,
            'shipping_cost' => 10.00,
            'total' => 90.00,
        ]);

        // Dos sub-pedidos, uno por tienda
        $orderShop1 = OrderShop::create([
            'order_id' => $order->id,
            'shop_id' => $shop1->id,
            'status' => 'preparing',
            'subtotal' => 30.00,
            'shipping_cost' => 5.00,
            'total' => 35.00,
        ]);

        $orderShop2 = OrderShop::create([
            'order_id' => $order->id,
            'shop_id' => $shop2->id,
            'status' => 'shipped',
            'subtotal' => 50.00,
            'shipping_cost' => 5.00,
            'total' => 55.00,
        ]);

        // Envío individualizado para la tienda 2
        $shipment = Shipment::create([
            'order_shop_id' => $orderShop2->id,
            'carrier' => 'MRW',
            'tracking_number' => 'MRW-2026-987654',
            'status' => 'in_transit',
            'shipped_at' => now(),
        ]);

        // Pago registrado
        $payment = Payment::create([
            'order_id' => $order->id,
            'amount' => 90.00,
            'method' => 'stripe',
            'status' => 'completed',
            'external_reference' => 'ch_3MtK8v2eZvKYlo2C01234567',
            'paid_at' => now(),
        ]);

        // Historial de estado
        $history = OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => 'paid',
            'created_at' => now(),
        ]);

        $this->assertCount(2, $order->orderShops);
        $this->assertCount(1, $orderShop2->shipments);
        $this->assertEquals('MRW-2026-987654', $orderShop2->latestShipment->tracking_number);
        $this->assertCount(1, $order->payments);
        $this->assertCount(1, $order->statusHistory);
    }

    /**
     * Prueba el borrado lógico (SoftDeletes) en modelos y consultas.
     */
    public function test_soft_deletes_behavior_in_domain_models(): void
    {
        $user = User::create(['name' => 'Usuario Borrable', 'email' => 'borrar@artesa.local', 'password' => 'secret123']);
        $shop = Shop::create(['user_id' => $user->id, 'name' => 'Taller Borrable', 'slug' => 'taller-borrable', 'cif_nif' => 'B00000000']);
        $product = Product::create(['shop_id' => $shop->id, 'title' => 'Producto Borrable', 'slug' => 'producto-borrable', 'base_price' => 10.00]);

        $product->delete();

        $this->assertTrue($product->trashed());
        // Consulta normal no lo incluye
        $this->assertNull(Product::find($product->id));
        // Consulta con withTrashed sí lo recupera
        $this->assertNotNull(Product::withTrashed()->find($product->id));

        // Restauración
        $product->restore();
        $this->assertFalse($product->trashed());
        $this->assertNotNull(Product::find($product->id));
    }

    /**
     * Prueba la lista de deseos (wishlists N:M) y valoraciones de compra.
     */
    public function test_wishlists_and_reviews_relationships(): void
    {
        $user = User::create(['name' => 'Fan Artesanía', 'email' => 'fan@artesa.local', 'password' => 'secret123']);
        $artisan = User::create(['name' => 'Artesano Joyero', 'email' => 'joyero@artesa.local', 'password' => 'secret123']);
        $shop = Shop::create(['user_id' => $artisan->id, 'name' => 'Plata Viva', 'slug' => 'plata-viva', 'cif_nif' => 'B44332211']);
        $product = Product::create(['shop_id' => $shop->id, 'title' => 'Anillo Filigrana', 'slug' => 'anillo-filigrana', 'base_price' => 60.00]);

        // Añadir a favoritos
        $user->wishlistProducts()->attach($product->id);
        $this->assertCount(1, $user->wishlistProducts);
        $this->assertEquals($product->id, $user->wishlistProducts->first()->id);

        // Crear pedido y reseña
        $order = Order::create(['order_number' => 'ART-REV-01', 'customer_id' => $user->id, 'subtotal' => 60.00, 'total' => 60.00]);
        $review = Review::create([
            'product_id' => $product->id,
            'customer_id' => $user->id,
            'order_id' => $order->id,
            'rating' => 5,
            'comment' => 'Una pieza preciosa y entrega muy cuidada.',
            'is_approved' => true,
        ]);

        $this->assertCount(1, $product->reviews);
        $this->assertEquals(5, $product->reviews->first()->rating);
        $this->assertEquals($user->id, $review->customer->id);
    }
}
