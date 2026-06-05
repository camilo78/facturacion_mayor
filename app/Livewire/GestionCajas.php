<?php

namespace App\Livewire;

use App\Models\Establecimiento;
use App\Models\PuntoEmision;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Cajas')]
class GestionCajas extends Component
{
    public bool   $mostrarModal     = false;
    public ?int   $editandoId       = null;
    public ?int   $establecimientoId = null;
    public string $codigo           = '';
    public string $nombre           = '';
    public string $emisorTipo       = 'mayor';
    public bool   $activo           = true;

    public function abrirCrear(): void
    {
        $this->reset(['codigo', 'nombre', 'editandoId', 'establecimientoId']);
        $this->emisorTipo  = 'mayor';
        $this->activo      = true;
        $this->mostrarModal = true;
        $this->resetValidation();
    }

    public function abrirEditar(int $id): void
    {
        $pe = PuntoEmision::findOrFail($id);
        $this->editandoId         = $id;
        $this->establecimientoId  = $pe->establecimiento_id;
        $this->codigo             = $pe->codigo;
        $this->nombre             = $pe->nombre;
        $this->emisorTipo         = $pe->emisor_tipo;
        $this->activo             = (bool) $pe->activo;
        $this->mostrarModal       = true;
        $this->resetValidation();
    }

    public function guardar(): void
    {
        $this->validate([
            'establecimientoId' => ['required', 'integer'],
            'codigo'            => ['required', 'string', 'max:3'],
            'nombre'            => ['required', 'string', 'max:255'],
            'emisorTipo'        => ['required', 'in:mayor,auxiliar'],
        ]);

        $datos = [
            'establecimiento_id' => $this->establecimientoId,
            'codigo'             => str_pad(strtoupper(trim($this->codigo)), 3, '0', STR_PAD_LEFT),
            'nombre'             => trim($this->nombre),
            'emisor_tipo'        => $this->emisorTipo,
            'activo'             => $this->activo,
        ];

        if ($this->editandoId) {
            PuntoEmision::findOrFail($this->editandoId)->update($datos);
            $msg = 'Caja actualizada.';
        } else {
            PuntoEmision::create($datos);
            $msg = 'Caja creada.';
        }

        $this->mostrarModal = false;
        $this->dispatch('toast', message: $msg, type: 'success');
    }

    public function toggleActivo(int $id): void
    {
        $pe = PuntoEmision::findOrFail($id);
        $pe->update(['activo' => ! $pe->activo]);
    }

    public function render(): View
    {
        return view('livewire.gestion-cajas', [
            'cajas'            => PuntoEmision::with('establecimiento')
                                    ->orderBy('establecimiento_id')
                                    ->orderBy('codigo')
                                    ->get(),
            'establecimientos' => Establecimiento::where('activo', true)
                                    ->orderBy('codigo')
                                    ->get(),
        ]);
    }
}
