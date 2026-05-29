<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;

class Producto extends Model
{
    use GeneratesUuid;

    public const TIPOS = ['bien', 'servicio'];

    protected $table = 'productos';
    protected $guarded = [];

    public function impuesto()
    {
        return $this->belongsTo(Impuesto::class);
    }
}