<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Panel del Artesano (Artesa)
|--------------------------------------------------------------------------
| Protegidas con middleware web, auth y rol artisan.
*/

Route::get('/', function () {
    $shop = auth()->user()->shop;
    return response()->json([
        'message' => 'Panel del Artesano',
        'shop' => $shop ? $shop->only('id', 'name', 'is_verified') : null,
    ]);
})->name('dashboard');

Route::get('/products', function () {
    $shop = auth()->user()->shop;
    return response()->json([
        'message' => 'Catálogo privado del taller',
        'products' => $shop ? $shop->products()->with('variants')->get() : [],
    ]);
})->name('products.index');

Route::get('/orders', function () {
    $shop = auth()->user()->shop;
    return response()->json([
        'message' => 'Subpedidos asignados a mi taller',
        'suborders' => $shop ? $shop->orderShops()->with('orderProducts')->get() : [],
    ]);
})->name('orders.index');
