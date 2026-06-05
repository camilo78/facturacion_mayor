<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Schema;

trait TracksSyncOrigin
{
    protected static function bootTracksSyncOrigin(): void
    {
        static::creating(function ($model) {
            if (empty($model->origin) && Schema::hasColumn($model->getTable(), 'origin')) {
                $model->origin = config('instance.uuid');
            }
        });
    }
}
