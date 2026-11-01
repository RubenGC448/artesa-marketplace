<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\OrderShop;
use App\Models\Product;
use App\Models\Review;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationAndPoliciesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ejecutamos el seeder de roles y permisos antes de cada test
        $this->seed(RoleAndPermissionSeeder::class);
    }

    public function test_roles_and_permissions_are_properly_assigned(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $artisan = User::factory()->create();
        $artisan->assignRole('artisan');

        $customer = User::factory()->create();
        $customer->assignRole('customer');

        $courier = User::factory()->create();
        $courier->assignRole('courier');

        $this->assertTrue($admin->hasRole('admin'));
        $this->assertTrue($admin->can('shops.verify'));

        $this->assertTrue($artisan->hasRole('artisan'));
        $this->assertTrue($artisan->can('products.create'));
        $this->assertFalse($artisan->can('shops.verify'));

        $this->assertTrue($customer->hasRole('customer'));
        $this->assertTrue($customer->can('orders.create'));
        $this->assertFalse($customer->can('products.create'));

        $this->assertTrue($courier->hasRole('courier'));
        $this->assertTrue($courier->can('orders.update_status'));
    }

    public function test_guest_is_redirected_from_protected_modular_routes(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/artisan')->assertRedirect('/login');
        $this->get('/customer')->assertRedirect('/login');
        $this->get('/courier')->assertRedirect('/login');
    }

    public function test_user_without_correct_role_receives_forbidden_status(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');

        // Un cliente no puede entrar a la zona de administración ni del artesano
        $this->actingAs($customer)->get('/admin')->assertForbidden();
        $this->actingAs($customer)->get('/artisan')->assertForbidden();
        $this->actingAs($customer)->get('/courier')->assertForbidden();

        // Pero sí accede a su propia zona de cliente
        $this->actingAs($customer)->get('/customer')->assertOk();
    }

    public function test_authorized_users_can_access_their_respective_modular_panels(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $artisan = User::factory()->create();
        $artisan->assignRole('artisan');

        $courier = User::factory()->create();
        $courier->assignRole('courier');

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($artisan)->get('/artisan')->assertOk();
        $this->actingAs($courier)->get('/courier')->assertOk();
    }

    public function test_shop_policy_enforces_strict_isolation_between_artisans(): void
    {
        $artisanA = User::factory()->create();
        $artisanA->assignRole('artisan');
        $shopA = Shop::factory()->create(['user_id' => $artisanA->id, 'is_verified' => true]);

        $artisanB = User::factory()->create();
        $artisanB->assignRole('artisan');
        $shopB = Shop::factory()->create(['user_id' => $artisanB->id, 'is_verified' => true]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Artesano A puede editar su propio taller
        $this->assertTrue(Gate::forUser($artisanA)->allows('update', $shopA));

        // Aislamiento: Artesano A NO puede editar el taller de Artesano B
        $this->assertFalse(Gate::forUser($artisanA)->allows('update', $shopB));

        // Super Admin tiene bypass global
        $this->assertTrue(Gate::forUser($admin)->allows('update', $shopB));
    }

    public function test_product_policy_prevents_modifying_products_from_other_shops(): void
    {
        $artisanA = User::factory()->create();
        $artisanA->assignRole('artisan');
        $shopA = Shop::factory()->create(['user_id' => $artisanA->id, 'is_verified' => true]);

        $artisanB = User::factory()->create();
        $artisanB->assignRole('artisan');
        $shopB = Shop::factory()->create(['user_id' => $artisanB->id, 'is_verified' => true]);

        $category = Category::factory()->create();
        $productA = Product::factory()->create(['shop_id' => $shopA->id, 'category_id' => $category->id]);
        $productB = Product::factory()->create(['shop_id' => $shopB->id, 'category_id' => $category->id]);

        // Artesano A edita su propio producto
        $this->assertTrue(Gate::forUser($artisanA)->allows('update', $productA));

        // Aislamiento: Artesano A no puede editar el producto del taller B
        $this->assertFalse(Gate::forUser($artisanA)->allows('update', $productB));

        // Artesano A no puede borrar el producto del taller B
        $this->assertFalse(Gate::forUser($artisanA)->allows('delete', $productB));
    }

    public function test_order_policy_restricts_suborder_management_to_the_owner_shop(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $address = Address::factory()->create(['user_id' => $customer->id]);

        $artisanA = User::factory()->create();
        $artisanA->assignRole('artisan');
        $shopA = Shop::factory()->create(['user_id' => $artisanA->id]);

        $artisanB = User::factory()->create();
        $artisanB->assignRole('artisan');
        $shopB = Shop::factory()->create(['user_id' => $artisanB->id]);

        $order = Order::factory()->create(['customer_id' => $customer->id, 'address_id' => $address->id]);

        $suborderA = OrderShop::factory()->create(['order_id' => $order->id, 'shop_id' => $shopA->id]);
        $suborderB = OrderShop::factory()->create(['order_id' => $order->id, 'shop_id' => $shopB->id]);

        // Artesano A solo gestiona el subpedido de su taller
        $this->assertTrue(Gate::forUser($artisanA)->allows('manageSuborder', $suborderA));
        $this->assertFalse(Gate::forUser($artisanA)->allows('manageSuborder', $suborderB));
    }

    public function test_order_cancellation_policy(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $address = Address::factory()->create(['user_id' => $customer->id]);

        $pendingOrder = Order::factory()->create([
            'customer_id' => $customer->id,
            'address_id' => $address->id,
            'status' => 'pending',
        ]);

        $preparingOrder = Order::factory()->create([
            'customer_id' => $customer->id,
            'address_id' => $address->id,
            'status' => 'preparing',
        ]);

        // Cliente puede cancelar en 'pending'
        $this->assertTrue(Gate::forUser($customer)->allows('cancel', $pendingOrder));

        // Cliente NO puede cancelar si ya está en confección 'preparing'
        $this->assertFalse(Gate::forUser($customer)->allows('cancel', $preparingOrder));
    }

    public function test_review_policy_requires_delivered_purchase_before_reviewing(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole('customer');
        $address = Address::factory()->create(['user_id' => $customer->id]);

        $artisan = User::factory()->create();
        $artisan->assignRole('artisan');
        $shop = Shop::factory()->create(['user_id' => $artisan->id]);

        $category = Category::factory()->create();
        $product = Product::factory()->create(['shop_id' => $shop->id, 'category_id' => $category->id]);

        // 1. Sin compra previa, no puede reseñar
        $this->assertFalse(Gate::forUser($customer)->allows('create', [Review::class, $product]));

        // 2. Crea un pedido que aún está en 'preparing'
        $preparingOrder = Order::factory()->create([
            'customer_id' => $customer->id,
            'address_id' => $address->id,
            'status' => 'preparing',
        ]);
        $suborder = OrderShop::factory()->create(['order_id' => $preparingOrder->id, 'shop_id' => $shop->id]);
        OrderProduct::factory()->create([
            'order_id' => $preparingOrder->id,
            'order_shop_id' => $suborder->id,
            'product_id' => $product->id,
        ]);

        $this->assertFalse(Gate::forUser($customer)->allows('create', [Review::class, $product]));

        // 3. Cambia a 'delivered': Ahora la policy autoriza la reseña verificada
        $preparingOrder->update(['status' => 'delivered']);
        $this->assertTrue(Gate::forUser($customer)->allows('create', [Review::class, $product]));

        // 4. Si ya existe la reseña, no se le permite duplicar valoración
        Review::factory()->create([
            'product_id' => $product->id,
            'customer_id' => $customer->id,
            'order_id' => $preparingOrder->id,
        ]);
        $this->assertFalse(Gate::forUser($customer)->allows('create', [Review::class, $product]));
    }
}
