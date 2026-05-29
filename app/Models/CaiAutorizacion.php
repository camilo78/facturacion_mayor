<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CaiAutorizacion extends Model
{
    use GeneratesUuid;

    protected $table = 'cai_autorizaciones';
    protected $guarded = [];

    protected $casts = [
        'rango_inicial'        => 'integer',
        'rango_final'          => 'integer',
        'correlativo_actual'   => 'integer',
        'fecha_limite_emision' => 'date',
        'activo'               => 'boolean',
    ];

    public const UMBRAL_AGOTAMIENTO    = 0.80; // 80% del rango usado
    public const DIAS_AVISO_VENCIMIENTO = 30;  // avisar 30 días antes

    // --- Relaciones ---

    public function puntoEmision()
    {
        return $this->belongsTo(PuntoEmision::class);
    }

    // --- Cantidades derivadas ---

    public function getTotalRangoAttribute(): int
    {
        return $this->rango_final - $this->rango_inicial + 1;
    }

    public function getUsadosAttribute(): int
    {
        return max(0, $this->correlativo_actual - $this->rango_inicial + 1);
    }

    public function getDisponiblesAttribute(): int
    {
        return max(0, $this->rango_final - $this->correlativo_actual);
    }

    public function getPorcentajeUsadoAttribute(): float
    {
        return $this->total_rango > 0 ? $this->usados / $this->total_rango : 0;
    }

    // --- Estado derivado ---

    public function getEstadoAttribute(): string
    {
        if ($this->correlativo_actual >= $this->rango_final) {
            return 'AGOTADO';
        }
        if (Carbon::today()->greaterThan($this->fecha_limite_emision)) {
            return 'VENCIDO';
        }
        return 'VIGENTE';
    }

    public function getUsableAttribute(): bool
    {
        return $this->activo && $this->estado === 'VIGENTE';
    }

    // --- Alertas ---

    public function getPorAgotarseAttribute(): bool
    {
        return $this->estado === 'VIGENTE'
            && $this->porcentaje_usado >= self::UMBRAL_AGOTAMIENTO;
    }

    public function getPorVencerAttribute(): bool
    {
        return $this->estado === 'VIGENTE'
            && $this->fecha_limite_emision->lessThanOrEqualTo(
                Carbon::today()->addDays(self::DIAS_AVISO_VENCIMIENTO)
            );
    }

    // --- Scopes ---

    public function scopeUsable(Builder $q): Builder
    {
        return $q->where('activo', true)
                 ->whereColumn('correlativo_actual', '<', 'rango_final')
                 ->whereDate('fecha_limite_emision', '>=', Carbon::today());
    }

    public function scopeDelPunto(Builder $q, int $puntoEmisionId, string $tipoDocumento): Builder
    {
        return $q->where('punto_emision_id', $puntoEmisionId)
                 ->where('tipo_documento', $tipoDocumento);
    }
}