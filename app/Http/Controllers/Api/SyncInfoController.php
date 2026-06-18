<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SyncInfoController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $instance = $request->attributes->get('sync_instance');
        $tenant   = $instance->tenant;

        return response()->json([
            'ok'       => true,
            'instance' => [
                'id'                => $instance->id,
                'label'             => $instance->label,
                'tipo'              => $instance->tipo,
                'establecimiento_id'=> $instance->establecimiento_id,
            ],
            'tenant' => [
                'id'              => $tenant?->id,
                'nombre'          => $tenant?->nombre,
                'nombre_comercial' => $tenant?->nombre_comercial,
                'rtn'             => $tenant?->rtn,
                'email'           => $tenant?->email,
                'telefono'        => $tenant?->telefono,
                'plan'            => $tenant?->plan,
                'estado'          => $tenant?->estado,
            ],
        ]);
    }
}
