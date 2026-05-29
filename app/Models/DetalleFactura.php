<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;

class DetalleFactura extends Model
{
    use GeneratesUuid;

    protected $table = 'detalle_facturas';
    protected $guarded = [];

    public function factura()
    {
        return $this->belongsTo(Factura::class);
    }
}