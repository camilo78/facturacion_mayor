<?php

namespace App\Models;

use App\Models\Concerns\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    use GeneratesUuid;

    protected $table = 'clientes';
    protected $guarded = [];
}