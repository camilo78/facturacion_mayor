<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InstanceModeMiddleware
{
    public function handle(Request $request, Closure $next, string $allowedMode): Response
    {
        $currentMode = config('instance.mode');

        if ($currentMode !== $allowedMode) {
            abort(403, sprintf(
                "Operación restringida. Esta ruta requiere modo '%s', pero la instancia está en modo '%s'.",
                $allowedMode,
                $currentMode
            ));
        }

        return $next($request);
    }
}