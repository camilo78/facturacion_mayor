<?php

namespace App\Providers;

use App\Models\Cliente;
use App\Models\DetalleFactura;
use App\Models\Factura;
use App\Models\Producto;
use App\Observers\SyncQueueObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Observer solo en nodos Auxiliar. En el Mayor las facturas
        // pasan por EmisorFactura y se sincronizan en dirección contraria.
        if (config('instance.is_auxiliar')) {
            $observer = new SyncQueueObserver();

            Factura::observe($observer);
            DetalleFactura::observe($observer);
            Cliente::observe($observer);
            Producto::observe($observer);
        }
    }
}
