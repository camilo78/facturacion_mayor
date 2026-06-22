<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Bootstrap pendiente: reintenta cada 5 min si la instancia aún no está registrada
// (primer arranque sin conexión al Mayor)
Schedule::command('instance:bootstrap')
    ->everyFiveMinutes()
    ->when(function () {
        if (! config('instance.is_auxiliar')) {
            return false;
        }
        return ! \App\Models\Instance::find(config('instance.uuid'));
    })
    ->withoutOverlapping(5);

// Sincronización con el Mayor — solo activa en nodos Auxiliar ya inicializados
Schedule::command('sync:push')
    ->everyFiveMinutes()
    ->when(fn () => config('instance.is_auxiliar'))
    ->withoutOverlapping(10)
    ->runInBackground();

Schedule::command('sync:pull')
    ->everyFiveMinutes()
    ->when(fn () => config('instance.is_auxiliar'))
    ->withoutOverlapping(10)
    ->runInBackground();
