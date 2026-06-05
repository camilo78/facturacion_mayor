<?php

namespace App\Livewire;

use App\Models\CaiAutorizacion;
use App\Models\PuntoEmision;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('CAI')]
class GestionCai extends Component
{
    public bool   $mostrarModal       = false;
    public ?int   $editandoId         = null;
    public ?int   $filtroPunto        = null;

    public ?int   $puntoEmisionId      = null;
    public string $tipoDocumento       = '01';
    public string $cai                 = '';
    public ?int   $rangoInicial        = null;
    public ?int   $rangoFinal          = null;
    public string $fechaLimiteEmision  = '';
    public bool   $activo              = true;

    public function abrirCrear(): void
    {
        $this->reset(['puntoEmisionId', 'cai', 'rangoInicial', 'rangoFinal', 'fechaLimiteEmision', 'editandoId']);
        $this->tipoDocumento = '01';
        $this->activo        = true;
        $this->mostrarModal  = true;
        $this->resetValidation();
    }

    public function abrirEditar(int $id): void
    {
        $c = CaiAutorizacion::findOrFail($id);
        $this->editandoId         = $id;
        $this->puntoEmisionId     = $c->punto_emision_id;
        $this->tipoDocumento      = $c->tipo_documento;
        $this->cai                = $c->cai;
        $this->rangoInicial       = $c->rango_inicial;
        $this->rangoFinal         = $c->rango_final;
        $this->fechaLimiteEmision = $c->fecha_limite_emision->toDateString();
        $this->activo             = (bool) $c->activo;
        $this->mostrarModal       = true;
        $this->resetValidation();
    }

    public function guardar(): void
    {
        $this->validate([
            'puntoEmisionId'     => ['required', 'integer'],
            'tipoDocumento'      => ['required', 'string', 'size:2'],
            'cai'                => ['required', 'string', 'max:40'],
            'rangoInicial'       => ['required', 'integer', 'min:1'],
            'rangoFinal'         => ['required', 'integer', 'gt:rangoInicial'],
            'fechaLimiteEmision' => ['required', 'date', 'after:today'],
        ]);

        $datos = [
            'punto_emision_id'     => $this->puntoEmisionId,
            'tipo_documento'       => $this->tipoDocumento,
            'cai'                  => strtoupper(trim($this->cai)),
            'rango_inicial'        => $this->rangoInicial,
            'rango_final'          => $this->rangoFinal,
            'fecha_limite_emision' => $this->fechaLimiteEmision,
            'activo'               => $this->activo,
        ];

        if ($this->editandoId) {
            CaiAutorizacion::findOrFail($this->editandoId)->update($datos);
            $msg = 'CAI actualizado.';
        } else {
            // correlativo_actual = rango_inicial - 1 (ningún folio emitido todavía)
            $datos['correlativo_actual'] = $this->rangoInicial - 1;
            CaiAutorizacion::create($datos);
            $msg = 'CAI creado.';
        }

        $this->mostrarModal = false;
        $this->dispatch('toast', message: $msg, type: 'success');
    }

    public function toggleActivo(int $id): void
    {
        $c = CaiAutorizacion::findOrFail($id);
        $c->update(['activo' => ! $c->activo]);
    }

    public function render(): View
    {
        $query = CaiAutorizacion::with('puntoEmision.establecimiento')
            ->orderBy('punto_emision_id')
            ->orderByDesc('created_at');

        if ($this->filtroPunto) {
            $query->where('punto_emision_id', $this->filtroPunto);
        }

        return view('livewire.gestion-cai', [
            'cais'          => $query->get(),
            'puntosEmision' => PuntoEmision::with('establecimiento')
                                ->orderBy('establecimiento_id')
                                ->orderBy('codigo')
                                ->get(),
        ]);
    }
}
