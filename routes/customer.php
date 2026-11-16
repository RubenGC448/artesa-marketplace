<?php

use App\Http\Controllers\Customer\AddressController;
use App\Http\Controllers\Customer\DashboardController;
use App\Http\Controllers\Customer\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Área privada del Cliente  (/customer, customer.*)
|--------------------------------------------------------------------------
| Middleware: web, auth, role:customer (bootstrap/app.php).
*/

Route::get('/', DashboardController::class)->name('dashboard');

Route::controller(OrderController::class)->prefix('orders')->name('orders.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/{order}', 'show')->name('show');
    Route::patch('/{order}/cancel', 'cancel')->name('cancel');
});

Route::get('/addresses', [AddressController::class, 'index'])->name('addresses.index');
