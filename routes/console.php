<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Tareas programadas de Artesa
|--------------------------------------------------------------------------
*/

// Libera el stock de pedidos abandonados sin pagar
Schedule::command('artesa:cancel-unpaid-orders')->hourly();

// Informe diario de stock bajo
Schedule::command('artesa:low-stock-report')->dailyAt('08:00');
