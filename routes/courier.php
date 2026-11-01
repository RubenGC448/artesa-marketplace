<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Panel de Reparto y Logística Local (Artesa)
|--------------------------------------------------------------------------
| Protegidas con middleware web, auth y rol courier.
*/

Route::get('/', function () {
    return response()->json([
        'message' => 'Panel de Rutas y Reparto de Artesa',
        'user' => auth()->user()->only('id', 'name', 'email'),
    ]);
})->name('dashboard');

Route::get('/shipments', function () {
    return response()->json([
        'message' => 'Envíos locales pendientes de entrega',
        'shipments' => \App\Models\Shipment::where('status', '!=', 'delivered')->with('orderShop.shop')->get(),
    ]);
})->name('shipments.index');
