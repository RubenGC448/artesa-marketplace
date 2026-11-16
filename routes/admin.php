<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DisputeController;
use App\Http\Controllers\Admin\ShopVerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Panel de Administración y Moderación  (/admin, admin.*)
|--------------------------------------------------------------------------
| Middleware: web, auth, role:admin|moderator (bootstrap/app.php).
*/

Route::get('/', DashboardController::class)->name('dashboard');

Route::controller(ShopVerificationController::class)->prefix('talleres')->name('shops.')->group(function () {
    Route::get('/pendientes', 'index')->name('pending');
    Route::patch('/{shop}/verificar', 'verify')->name('verify');
    Route::patch('/{shop}/retirar-verificacion', 'revoke')->name('revoke');
});

Route::get('/disputas', [DisputeController::class, 'index'])->name('disputes.index');
