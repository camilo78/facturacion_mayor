<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use App\Models\Concerns\TracksSyncOrigin;
use Illuminate\Database\Eloquent\Model;

class DetalleFactura extends Model
{
    use GeneratesUuid, TracksSyncOrigin;

    protected $table = 'detalle_facturas';
    protected $guarded = [];

    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }
}