<?php

namespace App\Actions\Facturacion;

use App\Models\CaiAutorizacion;
use App\Models\Factura;
use App\Models\Impuesto;
use App\Models\PuntoEmision;
use App\Rules\Rtn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class EmisorFactura
{
    public function emitir(array $datos): Factura
    {
        Validator::make($datos, [
            'punto_emision_id'         => ['required', 'integer'],
            'tipo_documento'           => ['nullable', 'string', 'size:2'],
            'rtn_cliente'              => ['nullable', new Rtn],
            'nombre_cliente'           => ['nullable', 'string', 'max:255'],
            'tipo_pago'                => ['nullable', 'in:contado,credito'],
            'orden_compra_exenta'      => ['nullable', 'string', 'max:60'],
            'num_constancia_exonerado' => ['nullable', 'string', 'max:60'],
            'num_registro_sag'         => ['nullable', 'string', 'max:60'],
            'lineas'                   => ['required', 'array', 'min:1'],
            'lineas.*.descripcion'     => ['required', 'string', 'max:255'],
            'lineas.*.unidad_medida'   => ['nullable', 'string', 'max:20'],
            'lineas.*.cantidad'        => ['nullable', 'numeric', 'min:0.001'],
            'lineas.*.precio_unitario' => ['required', 'numeric', 'min:0'],
            'lineas.*.descuento'       => ['nullable', 'numeric', 'min:0'],
            'lineas.*.impuesto_id'     => ['required', 'integer'],
        ])->validate();

        return DB::transaction(function () use ($datos) {
            $puntoEmision = PuntoEmision::with('establecimiento')
                ->findOrFail($datos['punto_emision_id']);

            $tipoDocumento = $datos['tipo_documento'] ?? '01';

            $cai = CaiAutorizacion::usable()
                ->delPunto($puntoEmision->id, $tipoDocumento)
                ->orderBy('fecha_limite_emision')
                ->lockForUpdate()
                ->first();

            if (! $cai) {
                throw new RuntimeException(
                    "No hay CAI vigente para la caja {$puntoEmision->codigo}, tipo {$tipoDocumento}."
                );
            }

            $correlativo = $cai->correlativo_actual + 1;
            if ($correlativo > $cai->rango_final) {
                throw new RuntimeException("El CAI {$cai->cai} agotó su rango.");
            }

            $numero = implode('-', [
                str_pad($puntoEmision->establecimiento->codigo, 3, '0', STR_PAD_LEFT),
                str_pad($puntoEmision->codigo, 3, '0', STR_PAD_LEFT),
                str_pad($tipoDocumento, 2, '0', STR_PAD_LEFT),
                str_pad((string) $correlativo, 8, '0', STR_PAD_LEFT),
            ]);

            $exento = 0; $gravado = 0; $totalIsv = 0; $descuentoTotal = 0;
            $lineas = [];

            foreach ($datos['lineas'] as $linea) {
                $impuesto  = Impuesto::findOrFail($linea['impuesto_id']);
                $cantidad  = $linea['cantidad'] ?? 1;
                $precio    = $linea['precio_unitario'];
                $descLinea = $linea['descuento'] ?? 0;

                $subtotal = round($cantidad * $precio - $descLinea, 2);
                $isv      = round($subtotal * (float) $impuesto->tasa / 100, 2);

                if ((float) $impuesto->tasa == 0.0) {
                    $exento += $subtotal;
                } else {
                    $gravado += $subtotal;
                }
                $totalIsv       += $isv;
                $descuentoTotal += $descLinea;

                $lineas[] = [
                    'descripcion'     => $linea['descripcion'],
                    'unidad_medida'   => $linea['unidad_medida'] ?? 'unidad',
                    'cantidad'        => $cantidad,
                    'precio_unitario' => $precio,
                    'descuento'       => $descLinea,
                    'impuesto_codigo' => $impuesto->codigo,
                    'impuesto_tasa'   => $impuesto->tasa,
                    'subtotal'        => $subtotal,
                    'isv'             => $isv,
                    'total'           => round($subtotal + $isv, 2),
                ];
            }

            $total = round($exento + $gravado + $totalIsv, 2);

            $factura = Factura::create([
                'cai_autorizacion_id'    => $cai->id,
                'punto_emision_id'       => $puntoEmision->id,
                'establecimiento_codigo' => str_pad($puntoEmision->establecimiento->codigo, 3, '0', STR_PAD_LEFT),
                'punto_emision_codigo'   => str_pad($puntoEmision->codigo, 3, '0', STR_PAD_LEFT),
                'tipo_documento'         => $tipoDocumento,
                'correlativo'            => $correlativo,
                'numero_completo'        => $numero,
                'cai'                    => $cai->cai,
                'rtn_cliente'              => $datos['rtn_cliente'] ?? null,
                'nombre_cliente'           => $datos['nombre_cliente'] ?? 'Consumidor Final',
                'direccion_cliente'        => $datos['direccion_cliente'] ?? null,
                'orden_compra_exenta'      => $datos['orden_compra_exenta'] ?? null,
                'num_constancia_exonerado' => $datos['num_constancia_exonerado'] ?? null,
                'num_registro_sag'         => $datos['num_registro_sag'] ?? null,
                'subtotal_exento'        => $exento,
                'subtotal_gravado'       => $gravado,
                'total_isv'              => $totalIsv,
                'descuento'              => $descuentoTotal,
                'total'                  => $total,
                'tipo_pago'              => $datos['tipo_pago'] ?? 'contado',
                'estado'                 => 'VIGENTE',
                'fecha_emision'          => now(),
                // TODO: validar que el usuario tenga sesión activa en este punto_emision.
                // Depende del módulo SesionCaja (siguiente etapa).
                'emitida_por'            => Auth::id(),
            ]);

            $factura->detalles()->createMany($lineas);

            $cai->correlativo_actual = $correlativo;
            $cai->save();

            activity()
                ->causedBy(Auth::user())
                ->performedOn($factura)
                ->withProperties([
                    'numero'  => $factura->numero_completo,
                    'total'   => $factura->total,
                    'cliente' => $factura->nombre_cliente,
                ])
                ->log('factura.emitida');

            return $factura;
        });
    }
}
