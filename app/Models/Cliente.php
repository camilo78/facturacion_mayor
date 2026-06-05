<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use App\Models\Concerns\TracksSyncOrigin;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use GeneratesUuid, TracksSyncOrigin;

    protected $table = 'clientes';
    protected $guarded = [];
}