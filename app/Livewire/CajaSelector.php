<?php

namespace App\Livewire;

use App\Models\PuntoEmision;
use Illuminate\View\View;
use Livewire\Component;

class CajaSelector extends Component
{
    public ?int $puntoEmisionId = null;

    public function mount(): void
    {
        $this->puntoEmisionId = session('caja_activa_id');

        if (! $this->puntoEmisionId) {
            $pe = PuntoEmision::where('activo', true)->first();
            if ($pe) {
                $this->puntoEmisionId = $pe->id;
                session(['caja_activa_id' => $pe->id]);
            }
        }
    }

    public function updatedPuntoEmisionId(): void
    {
        session(['caja_activa_id' => $this->puntoEmisionId]);
        $this->dispatch('cajaActiva', puntoEmisionId: $this->puntoEmisionId);
    }

    public function render(): View
    {
        return view('livewire.caja-selector', [
            'cajas' => PuntoEmision::with('establecimiento')
                ->where('activo', true)
                ->orderBy('establecimiento_id')
                ->orderBy('codigo')
                ->get(),
        ]);
    }
}
