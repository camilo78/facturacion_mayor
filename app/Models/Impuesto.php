<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;

class Impuesto extends Model
{
    use GeneratesUuid;

    protected $table = 'impuestos';
    protected $guarded = [];
}