<?php

namespace App\Livewire;

use App\Models\Establecimiento;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Establecimientos')]
class GestionEstablecimientos extends Component
{
    public bool   $mostrarModal = false;
    public ?int   $editandoId  = null;
    public string $codigo      = '';
    public string $nombre      = '';
    public string $direccion   = '';
    public string $telefono    = '';
    public bool   $activo      = true;

    public function abrirCrear(): void
    {
        $this->reset(['codigo', 'nombre', 'direccion', 'telefono', 'editandoId']);
        $this->activo      = true;
        $this->mostrarModal = true;
        $this->resetValidation();
    }

    public function abrirEditar(int $id): void
    {
        $e = Establecimiento::findOrFail($id);
        $this->editandoId  = $id;
        $this->codigo      = $e->codigo;
        $this->nombre      = $e->nombre;
        $this->direccion   = $e->direccion ?? '';
        $this->telefono    = $e->telefono  ?? '';
        $this->activo      = (bool) $e->activo;
        $this->mostrarModal = true;
        $this->resetValidation();
    }

    public function guardar(): void
    {
        $this->validate([
            'codigo'    => ['required', 'string', 'max:3'],
            'nombre'    => ['required', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:500'],
            'telefono'  => ['nullable', 'string', 'max:20'],
        ]);

        $datos = [
            'codigo'    => str_pad(strtoupper(trim($this->codigo)), 3, '0', STR_PAD_LEFT),
            'nombre'    => trim($this->nombre),
            'direccion' => trim($this->direccion) ?: null,
            'telefono'  => trim($this->telefono)  ?: null,
            'activo'    => $this->activo,
        ];

        if ($this->editandoId) {
            Establecimiento::findOrFail($this->editandoId)->update($datos);
            $msg = 'Establecimiento actualizado.';
        } else {
            Establecimiento::create($datos);
            $msg = 'Establecimiento creado.';
        }

        $this->mostrarModal = false;
        $this->dispatch('toast', message: $msg, type: 'success');
    }

    public function toggleActivo(int $id): void
    {
        $e = Establecimiento::findOrFail($id);
        $e->update(['activo' => ! $e->activo]);
    }

    public function render(): View
    {
        return view('livewire.gestion-establecimientos', [
            'establecimientos' => Establecimiento::orderBy('codigo')->get(),
        ]);
    }
}
