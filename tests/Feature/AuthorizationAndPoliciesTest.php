<?php

/*
|--------------------------------------------------------------------------
| Sprint 3 · Roles, zonas y rutas (convertido a sintaxis Pest)
|--------------------------------------------------------------------------
| Las pruebas detalladas de cada policy están en tests/Feature/Policies.
*/

use App\Models\Product;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);
});

it('asigna los cinco roles con sus permisos', function () {
    expect(userWithRole('admin')->can('shops.verify'))->toBeTrue()
        ->and(userWithRole('moderator')->can('disputes.resolve'))->toBeTrue();

    $artisan = userWithRole('artisan');
    expect($artisan->can('products.create'))->toBeTrue()
        ->and($artisan->can('shops.verify'))->toBeFalse();

    $customer = userWithRole('customer');
    expect($customer->can('orders.create'))->toBeTrue()
        ->and($customer->can('products.create'))->toBeFalse();

    expect(userWithRole('courier')->can('orders.update_status'))->toBeTrue();
});

it('redirige al login a los invitados que entran en zonas privadas', function (string $url) {
    $this->get($url)->assertRedirect('/login');
})->with(['/admin', '/artisan', '/customer', '/courier', '/artisan/products', '/customer/orders']);

it('devuelve 403 al entrar en la zona de otro rol', function () {
    $customer = userWithRole('customer');

    $this->actingAs($customer)->get('/admin')->assertForbidden();
    $this->actingAs($customer)->get('/artisan')->assertForbidden();
    $this->actingAs($customer)->get('/courier')->assertForbidden();
    $this->actingAs($customer)->get('/customer')->assertOk();
});

it('permite a cada rol entrar en su panel', function (string $role, string $url) {
    $user = $role === 'artisan' ? artisanWithShop() : userWithRole($role);

    $this->actingAs($user)->get($url)->assertOk();
})->with([
    ['admin', '/admin'],
    ['moderator', '/admin'],
    ['artisan', '/artisan'],
    ['customer', '/customer'],
    ['courier', '/courier'],
]);

it('lleva a cada usuario a su panel tras el login', function (string $role, string $route) {
    $this->actingAs(userWithRole($role))->get('/dashboard')->assertRedirect(route($route));
})->with([
    ['admin', 'admin.dashboard'],
    ['artisan', 'artisan.dashboard'],
    ['customer', 'customer.dashboard'],
    ['courier', 'courier.dashboard'],
]);

it('da el rol de cliente a quien se registra', function () {
    $this->post('/register', [
        'name' => 'Nueva Clienta',
        'email' => 'nueva@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    expect(User::where('email', 'nueva@example.com')->first()->hasRole('customer'))->toBeTrue();
});

it('deja la zona pública abierta a los invitados', function () {
    $product = catalogProduct(artisanWithShop()->shop, ['title' => 'Jarrón azul cobalto']);

    $this->get('/')->assertOk();
    $this->get(route('catalog.index'))->assertOk()->assertSee('Jarrón azul cobalto');
    $this->get(route('catalog.index', ['q' => 'cobalto']))->assertOk()->assertSee('Jarrón azul cobalto');
    $this->get(route('catalog.index', ['q' => 'inexistente']))->assertOk()->assertDontSee('Jarrón azul cobalto');
    $this->get(route('catalog.show', $product))->assertOk()->assertSee('Jarrón azul cobalto');
    $this->get(route('shops.show', $product->shop))->assertOk();
});

it('oculta al público borradores y talleres sin verificar', function () {
    $draft = catalogProduct(artisanWithShop()->shop, ['status' => Product::STATUS_DRAFT]);
    $unverifiedShop = artisanWithShop(['is_verified' => false])->shop;
    $fromUnverified = catalogProduct($unverifiedShop);

    $this->get(route('catalog.show', $draft))->assertNotFound();
    $this->get(route('catalog.show', $fromUnverified))->assertNotFound();
    $this->get(route('shops.show', $unverifiedShop))->assertNotFound();
});
