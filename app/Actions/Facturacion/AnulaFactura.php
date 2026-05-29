<?php

namespace App\Actions\Facturacion;

use App\Models\Factura;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AnulaFactura
{
    /**
     * Anula una factura vigente.
     *
     * IMPORTANTE: La anulación NO libera el correlativo del CAI.
     * El número fiscal queda consumido aunque la factura sea inválida
     * (regla del SAR — los correlativos no se reusan ni se decrementan).
     */
    public function anular(Factura $factura, string $motivo, int $userId): Factura
    {
        if (trim($motivo) === '') {
            throw new RuntimeException('El motivo de anulación es obligatorio.');
        }
        if ($factura->estado === 'ANULADA') {
            throw new RuntimeException(
                "La factura {$factura->numero_completo} ya está anulada."
            );
        }
        if ($factura->estado !== 'VIGENTE') {
            throw new RuntimeException(
                "Solo facturas VIGENTES pueden anularse (estado actual: {$factura->estado})."
            );
        }

        return DB::transaction(function () use ($factura, $motivo, $userId) {
            $factura->update([
                'estado'           => 'ANULADA',
                'motivo_anulacion' => $motivo,
                'anulada_por'      => $userId,
                'anulada_at'       => now(),
            ]);
            return $factura->fresh();
        });
    }
}