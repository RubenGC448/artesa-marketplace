<?php

use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use App\Services\Orders\CheckoutService;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
| Los tests de Feature usan Tests\TestCase. Cada fichero declara
| uses(RefreshDatabase::class) y siembra los roles en beforeEach.
*/

pest()->extend(TestCase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers de dominio para los tests
|--------------------------------------------------------------------------
| Requieren que RoleAndPermissionSeeder se haya ejecutado antes.
*/

function userWithRole(string $role, array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->assignRole($role);

    return $user;
}

function artisanWithShop(array $shopAttributes = []): User
{
    $artisan = userWithRole('artisan');

    Shop::factory()->create(array_merge(['user_id' => $artisan->id, 'is_verified' => true], $shopAttributes));

    return $artisan->load('shop');
}

function customerWithAddress(): User
{
    $customer = userWithRole('customer');

    Address::factory()->default()->create(['user_id' => $customer->id, 'name' => $customer->name]);

    return $customer->load('addresses');
}

function catalogProduct(Shop $shop, array $attributes = []): Product
{
    return Product::factory()->create(array_merge([
        'shop_id' => $shop->id,
        'status' => Product::STATUS_ACTIVE,
        'stock' => 10,
        'base_price' => 50.00,
        'tax_rate' => 21.00,
    ], $attributes));
}

/**
 * Compra real a través del servicio de checkout.
 *
 * @param  array<int, array{product_id:int, variant_id?:int|null, quantity:int}>  $items
 */
function checkout(User $customer, array $items): Order
{
    return app(CheckoutService::class)->placeOrder($customer, $customer->addresses->first(), $items);
}
