<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Re-inicializa el tenant desde la sesión para peticiones Livewire AJAX
 * (/livewire/update), que no pasan por el middleware TenanciaByTenantId.
 */
class InitializeTenancyFromSession
{
    public function handle(Request $request, Closure $next): Response
    {
        // Solo inicializar en rutas de tenant (/empresa/...) o en peticiones Livewire AJAX.
        // En el dominio central sin contexto de tenant no se debe tocar la conexión.
        $esTenantRoute  = $request->routeIs('tenant.*');
        $esLivewireAjax = $request->is('livewire*') && $request->hasHeader('X-Livewire');

        if (($esTenantRoute || $esLivewireAjax) && ! tenancy()->initialized && session()->has('tenant_id')) {
            $tenant = \App\Models\Tenant::find(session('tenant_id'));
            if ($tenant) {
                tenancy()->initialize($tenant);
            }
        }

        return $next($request);
    }
}
