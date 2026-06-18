<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Instance extends Model
{
    use CentralConnection;
    public $incrementing = false;
    protected $keyType   = 'string';

    protected $fillable = [
        'id',
        'tipo',
        'tenant_id',
        'establecimiento_id',
        'label',
        'last_seen_at',
        'activo',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'activo'       => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(InstanceToken::class);
    }

    public function esMayor(): bool
    {
        return $this->tipo === 'mayor';
    }

    public function esAuxiliar(): bool
    {
        return $this->tipo === 'auxiliar';
    }

    public function marcarVisto(): void
    {
        $this->update(['last_seen_at' => now()]);
    }
}
