<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use App\Models\Concerns\TracksSyncOrigin;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use GeneratesUuid, TracksSyncOrigin;

    public const TIPOS = ['bien', 'servicio'];

    protected $table = 'productos';
    protected $guarded = [];

    public function impuesto()
    {
        return $this->belongsTo(Impuesto::class);
    }
}