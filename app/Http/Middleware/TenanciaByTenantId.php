<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenanciaByTenantId
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = $request->route('tenantId');
        $tenant   = Tenant::findOrFail($tenantId);

        tenancy()->initialize($tenant);
        session()->put('tenant_id', $tenantId);

        return $next($request);
    }
}
