<?php

namespace App\Http\Middleware;

use App\Models\InstanceToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSyncToken
{
    public function handle(Request $request, Closure $next): Response
    {
        // Extraer Bearer token
        $plain = $request->bearerToken();

        if (! $plain) {
            return response()->json(['error' => 'Token de sincronización requerido.'], 401);
        }

        // Buscar por hash (operación en central DB, ANTES de inicializar tenancy)
        $tokenModel = InstanceToken::findByPlainToken($plain);

        if (! $tokenModel) {
            return response()->json(['error' => 'Token inválido o revocado.'], 401);
        }

        $instance = $tokenModel->instance;

        if (! $instance->activo) {
            return response()->json(['error' => 'Instancia desactivada.'], 403);
        }

        if ($instance->esMayor()) {
            return response()->json(['error' => 'Solo los Auxiliares pueden sincronizar.'], 403);
        }

        if (! $instance->tenant_id) {
            return response()->json(['error' => 'Instancia sin empresa asignada.'], 422);
        }

        // Registrar uso ANTES de inicializar tenancy (los modelos centrales
        // necesitan la conexión central; después del init usan la del tenant)
        $tokenModel->registrarUso();
        $instance->marcarVisto();

        // Compartir con el resto del request sin contaminar el input validado
        $request->attributes->set('sync_instance', $instance);

        // Inicializar tenancy → a partir de aquí, DB apunta al tenant del Auxiliar
        $tenant = \App\Models\Tenant::find($instance->tenant_id);
        tenancy()->initialize($tenant);

        return $next($request);
    }
}
