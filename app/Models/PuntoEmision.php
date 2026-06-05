<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use App\Models\Concerns\TracksSyncOrigin;
use Illuminate\Database\Eloquent\Model;

class PuntoEmision extends Model
{
    use GeneratesUuid, TracksSyncOrigin;

    protected $table = 'puntos_emision';
    protected $guarded = [];

    public function establecimiento()
    {
        return $this->belongsTo(Establecimiento::class);
    }
}