<?php

use App\Http\Controllers\Artisan\DashboardController;
use App\Http\Controllers\Artisan\ProductController;
use App\Http\Controllers\Artisan\SuborderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Panel del Artesano  (/artisan, artisan.*)
|--------------------------------------------------------------------------
| Middleware: web, auth, role:artisan (bootstrap/app.php).
*/

Route::get('/', DashboardController::class)->name('dashboard');

// CRUD del catálogo del taller (artisan.products.index|create|store|edit|update|destroy)
Route::resource('products', ProductController::class)->except('show');

// Subpedidos asignados al taller
Route::controller(SuborderController::class)->prefix('orders')->name('orders.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::patch('/{orderShop}/status', 'updateStatus')->name('update-status');
    Route::patch('/{orderShop}/cancel', 'cancel')->name('cancel');
});
