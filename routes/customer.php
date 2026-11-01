<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Panel del Cliente Comprador (Artesa)
|--------------------------------------------------------------------------
| Protegidas con middleware web, auth y rol customer.
*/

Route::get('/', function () {
    return response()->json([
        'message' => 'Área Privada del Cliente',
        'user' => auth()->user()->only('id', 'name', 'email'),
    ]);
})->name('dashboard');

Route::get('/orders', function () {
    return response()->json([
        'message' => 'Mis compras realizadas en Artesa',
        'orders' => auth()->user()->orders()->with('orderShops.shop')->latest()->get(),
    ]);
})->name('orders.index');

Route::get('/addresses', function () {
    return response()->json([
        'message' => 'Mis direcciones de entrega',
        'addresses' => auth()->user()->addresses,
    ]);
})->name('addresses.index');
