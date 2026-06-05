<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use App\Models\Concerns\TracksSyncOrigin;
use Illuminate\Database\Eloquent\Model;

class Factura extends Model
{
    use GeneratesUuid, TracksSyncOrigin;

    protected $table = 'facturas';
    protected $guarded = [];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'anulada_at'    => 'datetime',
    ];

    public function detalles()
    {
        return $this->hasMany(DetalleFactura::class);
    }

    public function caiAutorizacion()
    {
        return $this->belongsTo(CaiAutorizacion::class);
    }

    public function puntoEmision()
    {
        return $this->belongsTo(PuntoEmision::class);
    }
}