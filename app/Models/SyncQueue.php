<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SyncQueue extends Model
{
    protected $table = 'sync_queue';

    protected $fillable = [
        'tabla',
        'uuid',
        'accion',
        'datos',
        'origen_at',
        'enviado_at',
        'intentos',
        'ultimo_error',
    ];

    protected $casts = [
        'datos'      => 'array',
        'origen_at'  => 'datetime',
        'enviado_at' => 'datetime',
    ];

    // ── Scopes ───────────────────────────────────────────────────────────────

    /** Registros aún no enviados al Mayor */
    public function scopePendiente(Builder $q): Builder
    {
        return $q->whereNull('enviado_at');
    }

    /** Pendientes con reintentos disponibles (máx. 5) */
    public function scopeReintentable(Builder $q): Builder
    {
        return $q->pendiente()->where('intentos', '<', 5);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function marcarEnviado(): void
    {
        $this->update(['enviado_at' => now()]);
    }

    public function registrarError(string $error): void
    {
        $this->increment('intentos');
        $this->update(['ultimo_error' => $error]);
    }

    // ── Control para suprimir el observer durante operaciones de sync ─────────

    public static bool $suspendido = false;

    public static function suspender(callable $fn): void
    {
        static::$suspendido = true;
        try {
            $fn();
        } finally {
            static::$suspendido = false;
        }
    }
}
