<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'instance.mode'  => \App\Http\Middleware\InstanceModeMiddleware::class,
            'tenancy.by.id'  => \App\Http\Middleware\TenanciaByTenantId::class,
            'tenant.auth'    => \App\Http\Middleware\TenantAuth::class,
            'auth.sync'      => \App\Http\Middleware\AuthenticateSyncToken::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\InitializeTenancyFromSession::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
