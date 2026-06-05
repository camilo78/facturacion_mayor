<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use App\Models\Concerns\TracksSyncOrigin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, GeneratesUuid, TracksSyncOrigin, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'activo',
        'ultimo_login',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'ultimo_login'      => 'datetime',
            'password'          => 'hashed',
            'activo'            => 'boolean',
        ];
    }

    public function sesionActiva(): HasOne
    {
        // La tabla sesiones_caja se crea en el módulo SesionCaja (siguiente etapa).
        // El guard Schema::hasTable evita errores mientras la tabla no exista.
        if (! Schema::hasTable('sesiones_caja')) {
            // Retornar una relación vacía que no ejecuta query
            return $this->hasOne(self::class, 'id', 'id')->whereRaw('0=1');
        }

        // @phpstan-ignore-next-line
        return $this->hasOne(\App\Models\SesionCaja::class)->where('estado', 'abierta');
    }

    public function isAdmin(): bool
    {
        return $this->hasRole('Admin');
    }

    public function isActivo(): bool
    {
        return (bool) ($this->activo ?? true);
    }
}
