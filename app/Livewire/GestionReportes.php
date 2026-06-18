<?php

namespace App\Livewire;

use App\Models\Establecimiento;
use App\Models\Factura;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Reportes')]
class GestionReportes extends Component
{
    public string $tipoReporte       = 'libro_ventas';
    public string $fechaDesde        = '';
    public string $fechaHasta        = '';
    public ?int   $establecimientoId = null;

    public function mount(): void
    {
        $this->fechaDesde = now()->startOfMonth()->format('Y-m-d');
        $this->fechaHasta = now()->endOfMonth()->format('Y-m-d');
    }

    private function consultaBase(bool $anuladas = false)
    {
        return Factura::with(['detalles', 'puntoEmision.establecimiento'])
            ->when($this->fechaDesde, fn($q) => $q->whereDate('fecha_emision', '>=', $this->fechaDesde))
            ->when($this->fechaHasta, fn($q) => $q->whereDate('fecha_emision', '<=', $this->fechaHasta))
            ->when($this->establecimientoId, fn($q) => $q->whereHas('puntoEmision', fn($q2) =>
                $q2->where('establecimiento_id', $this->establecimientoId)
            ))
            ->where('estado', $anuladas ? 'ANULADA' : 'VIGENTE')
            ->orderBy('fecha_emision')
            ->orderBy('numero_completo');
    }

    private function enriquecer($facturas): void
    {
        $facturas->each(function ($f) {
            $f->_exento    = $f->detalles->where('impuesto_tasa', 0)->sum('subtotal');
            $f->_gravado15 = $f->detalles->where('impuesto_tasa', 15)->sum('subtotal');
            $f->_isv15     = $f->detalles->where('impuesto_tasa', 15)->sum('isv');
            $f->_gravado18 = $f->detalles->where('impuesto_tasa', 18)->sum('subtotal');
            $f->_isv18     = $f->detalles->where('impuesto_tasa', 18)->sum('isv');
            $f->_exonerado   = $f->num_constancia_exonerado ? $f->_exento : 0;
            $f->_exento_puro = $f->num_constancia_exonerado ? 0 : $f->_exento;
        });
    }

    public function render(): View
    {
        $esAnuladas = $this->tipoReporte === 'anuladas';
        $facturas   = $this->consultaBase($esAnuladas)->limit(150)->get();
        $this->enriquecer($facturas);
        $totalRegistros = $this->consultaBase($esAnuladas)->count();

        $totales = [
            'exento'    => $facturas->sum('_exento_puro'),
            'exonerado' => $facturas->sum('_exonerado'),
            'gravado15' => $facturas->sum('_gravado15'),
            'isv15'     => $facturas->sum('_isv15'),
            'gravado18' => $facturas->sum('_gravado18'),
            'isv18'     => $facturas->sum('_isv18'),
            'total'     => $facturas->sum('total'),
        ];

        $establecimientos = Establecimiento::orderBy('nombre')->get();
        $tid = tenant('id');

        $exportParams = http_build_query(array_filter([
            'tipo'  => $this->tipoReporte,
            'desde' => $this->fechaDesde,
            'hasta' => $this->fechaHasta,
            'estab' => $this->establecimientoId,
        ]));

        return view('livewire.gestion-reportes', compact(
            'facturas', 'totales', 'totalRegistros',
            'establecimientos', 'tid', 'exportParams',
        ));
    }
}
