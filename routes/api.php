<?php

use App\Http\Controllers\Api\SyncInfoController;
use App\Http\Controllers\Api\SyncPullController;
use App\Http\Controllers\Api\SyncPushController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de sincronización Mayor ↔ Auxiliar
|--------------------------------------------------------------------------
| Autenticadas con Bearer token (tabla instance_tokens, hash sha256).
| El middleware inicializa el tenant del Auxiliar automáticamente.
*/

Route::middleware('auth.sync')->group(function () {
    Route::get('/sync/info',  SyncInfoController::class)->name('sync.info');
    Route::post('/sync/push', SyncPushController::class)->name('sync.push');
    Route::post('/sync/pull', SyncPullController::class)->name('sync.pull');
});
