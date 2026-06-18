<?php

namespace App\Observers;

use App\Models\SyncQueue;
use Illuminate\Database\Eloquent\Model;

/**
 * Encola cambios en modelos del tenant para sincronizar al Mayor.
 * Solo actúa cuando INSTANCE_MODE=auxiliar y la cola no está suspendida.
 */
class SyncQueueObserver
{
    // Campos que, al cambiar solos, NO generan entrada en la cola
    // (los setea el propio proceso de sync al recibir datos del Mayor)
    private const CAMPOS_SYNC = ['synced_at', 'updated_at'];

    public function created(Model $model): void
    {
        $this->encolar($model, 'crear');
    }

    public function updated(Model $model): void
    {
        // Ignorar si solo cambiaron campos internos de sync
        $dirty = array_keys($model->getDirty());
        if (! array_diff($dirty, self::CAMPOS_SYNC)) {
            return;
        }

        // Anulación de factura: acción especial
        $accion = ($model->getTable() === 'facturas' && $model->isDirty('anulada_at') && $model->anulada_at)
            ? 'anular'
            : 'actualizar';

        $this->encolar($model, $accion);
    }

    private function encolar(Model $model, string $accion): void
    {
        if (! config('instance.is_auxiliar')) {
            return;
        }

        if (SyncQueue::$suspendido) {
            return;
        }

        SyncQueue::create([
            'tabla'     => $model->getTable(),
            'uuid'      => $model->uuid,
            'accion'    => $accion,
            'datos'     => $this->prepararDatos($model),
            'origen_at' => now(),
        ]);
    }

    /**
     * Serializa el modelo reemplazando FKs enteras por UUIDs para evitar
     * colisiones de auto-increment entre la BD del Auxiliar y la del Mayor.
     */
    private function prepararDatos(Model $model): array
    {
        $datos = $model->toArray();

        return match ($model->getTable()) {
            'facturas'         => $this->datosFactura($model, $datos),
            'detalle_facturas' => $this->datosDetalle($model, $datos),
            default            => $datos,
        };
    }

    private function datosFactura(Model $factura, array $datos): array
    {
        unset($datos['cai_autorizacion_id'], $datos['punto_emision_id']);

        $datos['cai_autorizacion_uuid'] = $factura->caiAutorizacion?->uuid;
        $datos['punto_emision_uuid']    = $factura->puntoEmision?->uuid;

        return $datos;
    }

    private function datosDetalle(Model $detalle, array $datos): array
    {
        unset($datos['factura_id']);

        $datos['factura_uuid'] = $detalle->factura?->uuid;

        return $datos;
    }
}
