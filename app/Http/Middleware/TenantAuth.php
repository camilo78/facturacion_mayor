<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TenantAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return redirect()->route('tenant.login', [
                'tenantId' => $request->route('tenantId'),
            ]);
        }

        // Bloquear usuarios desactivados en cada request
        if (! Auth::user()->isActivo()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('tenant.login', [
                'tenantId' => $request->route('tenantId'),
            ])->withErrors(['email' => 'Tu cuenta está desactivada. Contactá al administrador.']);
        }

        return $next($request);
    }
}
