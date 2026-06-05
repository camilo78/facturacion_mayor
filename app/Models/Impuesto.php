<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use App\Models\Concerns\TracksSyncOrigin;
use Illuminate\Database\Eloquent\Model;

class Impuesto extends Model
{
    use GeneratesUuid, TracksSyncOrigin;

    protected $table = 'impuestos';
    protected $guarded = [];
}