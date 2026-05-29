<?php

namespace App\Console\Commands;

use App\Actions\Facturacion\EmisorFactura;
use App\Models\Impuesto;
use App\Models\PuntoEmision;
use App\Models\Tenant;
use Illuminate\Console\Command;

class FacturaPrueba extends Command
{
    protected $signature = 'factura:prueba {cantidad=1}';
    protected $description = 'Emite N facturas de prueba contra la caja 001 de la última empresa';

    public function handle(EmisorFactura $emisor): int
    {
        $cantidad = (int) $this->argument('cantidad');
        $tenant = Tenant::latest()->first();

        $tenant->run(function () use ($emisor, $cantidad) {
            $caja       = PuntoEmision::where('codigo', '001')->firstOrFail();
            $impuestoId = Impuesto::where('codigo', 'ISV15')->value('id');

            for ($i = 0; $i < $cantidad; $i++) {
                $f = $emisor->emitir([
                    'punto_emision_id' => $caja->id,
                    'nombre_cliente'   => 'Cliente de prueba',
                    'lineas' => [[
                        'descripcion'     => 'Producto de prueba',
                        'precio_unitario' => 100,
                        'impuesto_id'     => $impuestoId,
                    ]],
                ]);
                $this->line("Emitida: {$f->numero_completo}  total L {$f->total}");
            }
        });

        return self::SUCCESS;
    }
}