<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use App\Models\Concerns\TracksSyncOrigin;
use Illuminate\Database\Eloquent\Model;

class Establecimiento extends Model
{
    use GeneratesUuid, TracksSyncOrigin;

    protected $table = 'establecimientos';
    protected $guarded = [];

    public function puntosEmision()
    {
        return $this->hasMany(PuntoEmision::class);
    }
}