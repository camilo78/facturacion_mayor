<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint usado por el Auxiliar para sincronizar usuarios desde el Mayor.
 *
 * Seguridad:
 *  - Protegido por middleware auth.sync (Bearer token sha256).
 *  - El middleware inicializa tenancy → los queries de User son siempre
 *    del tenant autenticado, nunca de otros tenants.
 *  - Se devuelve el hash bcrypt tal como está en la BD (nunca plaintext).
 *    El Auxiliar lo inserta con DB::table() para evitar re-hasheo.
 *  - No se incluye remember_token ni datos sensibles innecesarios.
 */
class SyncUsuariosController
{
    public function __invoke(Request $request): JsonResponse
    {
        $desde = $request->query('desde');

        $query = User::with('roles')->where('activo', true);

        if ($desde) {
            $query->where('updated_at', '>', $desde);
        }

        $usuarios = $query->get()->map(fn (User $u) => [
            'uuid'              => $u->uuid,
            'name'              => $u->name,
            'email'             => $u->email,
            // getRawOriginal evita que el cast 'hashed' transforme el valor
            'password'          => $u->getRawOriginal('password'),
            'email_verified_at' => $u->email_verified_at?->toIso8601String(),
            'activo'            => $u->activo,
            'roles'             => $u->roles->pluck('name')->all(),
        ]);

        return response()->json([
            'usuarios' => $usuarios,
            'total'    => $usuarios->count(),
            'hasta'    => now()->toIso8601String(),
        ]);
    }
}
