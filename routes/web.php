<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Public\CatalogController;
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\ShopController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Zona pública (sin autenticación)
|--------------------------------------------------------------------------
| Escaparate y buscador. Las zonas privadas por rol están en
| routes/{admin,artisan,customer,courier}.php (ver bootstrap/app.php).
*/

Route::get('/', HomeController::class)->name('home');

Route::controller(CatalogController::class)->prefix('catalogo')->name('catalog.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{product:slug}', 'show')->name('show');
});

Route::get('/talleres/{shop:slug}', [ShopController::class, 'show'])->name('shops.show');

/*
|--------------------------------------------------------------------------
| Entrada común tras el login (Jetstream)
|--------------------------------------------------------------------------
| Redirige al panel del rol del usuario.
*/

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});
