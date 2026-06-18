<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class ConnectivityChecker
{
    public static function mayorReachable(): bool
    {
        $url = config('instance.mayor_url');
        if (! $url) {
            return false;
        }

        try {
            $response = Http::timeout(5)->get(rtrim($url, '/') . '/up');

            return $response->successful();
        } catch (\Throwable) {
            return false;
        }
    }
}
