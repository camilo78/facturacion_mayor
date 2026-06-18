<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaiAutorizacion;
use App\Models\Cliente;
use App\Models\Establecimiento;
use App\Models\Impuesto;
use App\Models\Instance;
use App\Models\Producto;
use App\Models\PuntoEmision;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SyncPullController extends Controller
{
    // Tablas que el Auxiliar puede jalar del Mayor (datos maestros)
    private const TABLAS = [
        'establecimientos',
        'puntos_emision',
        'cai_autorizaciones',
        'impuestos',
        'clientes',
        'productos',
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'desde'    => ['nullable', 'date'],
            'tablas'   => ['nullable', 'array'],
            'tablas.*' => ['string', Rule::in(self::TABLAS)],
        ]);

        $desde    = isset($validated['desde']) ? \Carbon\Carbon::parse($validated['desde']) : null;
        $tablas   = $validated['tablas'] ?? self::TABLAS;
        $instance = $request->attributes->get('sync_instance');

        $resultado = [];
        $totales   = [];

        foreach ($tablas as $tabla) {
            $registros         = $this->consultarTabla($tabla, $desde, $instance);
            $resultado[$tabla] = $registros;
            $totales[$tabla]   = count($registros);
        }

        return response()->json([
            'ok'     => true,
            'hasta'  => now()->toIso8601String(),
            'tablas' => $resultado,
            'totales'=> $totales,
        ]);
    }

    private function consultarTabla(string $tabla, ?\Carbon\Carbon $desde, ?Instance $instance): array
    {
        $query = match ($tabla) {
            'establecimientos'   => $this->queryEstablecimientos($instance),
            'puntos_emision'     => $this->queryPuntosEmision($instance),
            'cai_autorizaciones' => $this->queryCai($instance),
            'impuestos'          => Impuesto::query(),
            'clientes'           => Cliente::query(),
            'productos'          => Producto::with('impuesto:id,codigo,tasa'),
        };

        if ($desde) {
            $query->where('updated_at', '>', $desde);
        }

        return $query->get()->toArray();
    }

    // Si la instancia tiene establecimiento asignado, solo devuelve ese
    private function queryEstablecimientos(?Instance $instance): Builder
    {
        $query = Establecimiento::query();

        if ($instance?->establecimiento_id) {
            $query->where('id', $instance->establecimiento_id);
        }

        return $query;
    }

    // Puntos de emisión del establecimiento del Auxiliar (o todos si no está asignado)
    private function queryPuntosEmision(?Instance $instance): Builder
    {
        $query = PuntoEmision::query();

        if ($instance?->establecimiento_id) {
            $query->where('establecimiento_id', $instance->establecimiento_id);
        }

        return $query;
    }

    // CAIs de los puntos de emisión del establecimiento del Auxiliar
    private function queryCai(?Instance $instance): Builder
    {
        $query = CaiAutorizacion::query();

        if ($instance?->establecimiento_id) {
            $query->whereHas('puntoEmision', function (Builder $q) use ($instance) {
                $q->where('establecimiento_id', $instance->establecimiento_id);
            });
        }

        return $query;
    }
}
