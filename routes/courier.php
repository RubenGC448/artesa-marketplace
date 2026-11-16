<?php

use App\Http\Controllers\Courier\DashboardController;
use App\Http\Controllers\Courier\ShipmentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Panel de Reparto  (/courier, courier.*)
|--------------------------------------------------------------------------
| Middleware: web, auth, role:courier (bootstrap/app.php).
*/

Route::get('/', DashboardController::class)->name('dashboard');

Route::get('/shipments', [ShipmentController::class, 'index'])->name('shipments.index');
Route::patch('/shipments/{shipment}', [ShipmentController::class, 'update'])->name('shipments.update');
