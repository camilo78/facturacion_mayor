<?php

namespace App\Livewire;

use App\Models\CaiAutorizacion;
use App\Models\Factura;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render(): View
    {
        $hoy = today();

        $facturasHoy = Factura::whereDate('fecha_emision', $hoy)->where('estado', 'VIGENTE');
        $alertasCai  = CaiAutorizacion::usable()->get()->filter(fn ($c) => $c->por_agotarse || $c->por_vencer);

        return view('livewire.dashboard', [
            'cntHoy'          => (clone $facturasHoy)->count(),
            'totalHoy'        => (clone $facturasHoy)->sum('total'),
            'cntVigentes'     => Factura::where('estado', 'VIGENTE')->count(),
            'cntAnuladasHoy'  => Factura::whereDate('anulada_at', $hoy)->where('estado', 'ANULADA')->count(),
            'alertasCai'      => $alertasCai,
            'ultimasFacturas' => Factura::with('puntoEmision.establecimiento')
                ->orderByDesc('fecha_emision')->orderByDesc('id')
                ->limit(8)->get(),
        ]);
    }
}
