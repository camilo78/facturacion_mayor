<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaiAutorizacion;
use App\Models\Cliente;
use App\Models\DetalleFactura;
use App\Models\Factura;
use App\Models\Producto;
use App\Models\PuntoEmision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SyncPushController extends Controller
{
    // Tablas que el Auxiliar puede empujar, en orden de procesamiento
    // (padres antes que hijos para respetar FK)
    private const ORDEN = ['clientes', 'productos', 'facturas', 'detalle_facturas'];

    private const MODELOS = [
        'clientes'        => Cliente::class,
        'productos'       => Producto::class,
        'facturas'        => Factura::class,
        'detalle_facturas'=> DetalleFactura::class,
    ];

    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lote'             => ['required', 'array', 'min:1', 'max:200'],
            'lote.*.tabla'     => ['required', 'string', Rule::in(array_keys(self::MODELOS))],
            'lote.*.uuid'      => ['required', 'uuid'],
            'lote.*.accion'    => ['required', Rule::in(['crear', 'actualizar', 'anular'])],
            'lote.*.datos'     => ['required', 'array'],
            'lote.*.origen_at' => ['required', 'date'],
        ]);

        // Ordenar: padres antes que hijos
        $lote = collect($validated['lote'])->sortBy(
            fn ($item) => array_search($item['tabla'], self::ORDEN)
        );

        $aceptados  = [];
        $rechazados = [];

        foreach ($lote as $item) {
            try {
                DB::transaction(fn () => $this->procesarItem($item));
                $aceptados[] = $item['uuid'];
            } catch (\Throwable $e) {
                $rechazados[] = [
                    'uuid'  => $item['uuid'],
                    'tabla' => $item['tabla'],
                    'error' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'ok'         => true,
            'aceptados'  => $aceptados,
            'rechazados' => $rechazados,
            'total'      => count($aceptados) + count($rechazados),
        ]);
    }

    private function procesarItem(array $item): void
    {
        $tabla  = $item['tabla'];
        $uuid   = $item['uuid'];
        $accion = $item['accion'];
        $datos  = $item['datos'];

        // Resolver FKs de enteros desde UUIDs (evita conflictos de auto-increment
        // entre la BD del Auxiliar y la del Mayor)
        $datos = match ($tabla) {
            'facturas'         => $this->resolverFkFactura($datos),
            'detalle_facturas' => $this->resolverFkDetalle($datos),
            default            => $datos,
        };

        // Eliminar campos que no existen en la tabla destino
        unset($datos['id'], $datos['cai_autorizacion_uuid'], $datos['punto_emision_uuid'], $datos['factura_uuid']);

        $clase = self::MODELOS[$tabla];

        $datos['synced_at'] = now();

        $clase::updateOrCreate(['uuid' => $uuid], $datos);
    }

    private function resolverFkFactura(array $datos): array
    {
        // El Auxiliar envía UUIDs en vez de integer IDs para evitar colisiones
        if (isset($datos['cai_autorizacion_uuid'])) {
            $cai = CaiAutorizacion::where('uuid', $datos['cai_autorizacion_uuid'])->firstOrFail();
            $datos['cai_autorizacion_id'] = $cai->id;
        }

        if (isset($datos['punto_emision_uuid'])) {
            $pe = PuntoEmision::where('uuid', $datos['punto_emision_uuid'])->firstOrFail();
            $datos['punto_emision_id'] = $pe->id;
        }

        return $datos;
    }

    private function resolverFkDetalle(array $datos): array
    {
        if (isset($datos['factura_uuid'])) {
            $factura = Factura::where('uuid', $datos['factura_uuid'])->firstOrFail();
            $datos['factura_id'] = $factura->id;
        }

        return $datos;
    }
}
