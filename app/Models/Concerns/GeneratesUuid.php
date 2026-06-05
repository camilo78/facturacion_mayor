<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait GeneratesUuid
{
    protected static function bootGeneratesUuid(): void
    {
        static::creating(function ($model) {
            if (empty($model->uuid) && Schema::hasColumn($model->getTable(), 'uuid')) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}