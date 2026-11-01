<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del Panel de Administración y Moderación (Artesa)
|--------------------------------------------------------------------------
| Protegidas con middleware web, auth y rol admin o moderator.
*/

Route::get('/', function () {
    return response()->json([
        'message' => 'Panel de Administración y Moderación de Artesa',
        'user' => auth()->user()->only('id', 'name', 'email'),
    ]);
})->name('dashboard');

Route::get('/shops/pending', function () {
    return response()->json([
        'message' => 'Listado de talleres pendientes de verificación',
        'shops' => \App\Models\Shop::where('is_verified', false)->get(),
    ]);
})->name('shops.pending');

Route::get('/disputes', function () {
    return response()->json([
        'message' => 'Listado de disputas e incidencias de la plataforma',
        'disputes' => \App\Models\Dispute::with('order')->get(),
    ]);
})->name('disputes.index');
