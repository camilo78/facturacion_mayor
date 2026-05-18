<?php

return [
    'mode' => env('INSTANCE_MODE', 'mayor'),
    'uuid' => env('INSTANCE_UUID'),
    'label' => env('INSTANCE_LABEL', 'Sin nombre'),

    'is_mayor'    => env('INSTANCE_MODE', 'mayor') === 'mayor',
    'is_auxiliar' => env('INSTANCE_MODE') === 'auxiliar',
];