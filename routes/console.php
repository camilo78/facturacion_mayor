<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Sincronización con el Mayor — solo activa en nodos Auxiliar
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
