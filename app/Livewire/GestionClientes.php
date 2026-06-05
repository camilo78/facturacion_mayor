<?php

namespace App\Livewire;

use App\Models\Cliente;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Clientes')]
class GestionClientes extends Component
{
    use WithPagination;

    public string $busqueda    = '';
    public bool   $soloActivos = true;

    public bool   $mostrarModal = false;
    public ?int   $editandoId  = null;

    public string $nombre    = '';
    public string $rtn       = '';
    public string $direccion = '';
    public string $telefono  = '';
    public string $email     = '';
    public bool   $activo    = true;

    public function updatedBusqueda(): void    { $this->resetPage(); }
    public function updatedSoloActivos(): void { $this->resetPage(); }

    public function abrirCrear(): void
    {
        $this->reset(['nombre', 'rtn', 'direccion', 'telefono', 'email', 'editandoId']);
        $this->activo       = true;
        $this->mostrarModal = true;
        $this->resetValidation();
    }

    public function abrirEditar(int $id): void
    {
        $c = Cliente::findOrFail($id);
        $this->editandoId   = $id;
        $this->nombre       = $c->nombre;
        $this->rtn          = $c->rtn ?? '';
        $this->direccion    = $c->direccion ?? '';
        $this->telefono     = $c->telefono ?? '';
        $this->email        = $c->email ?? '';
        $this->activo       = (bool) $c->activo;
        $this->mostrarModal = true;
        $this->resetValidation();
    }

    public function guardar(): void
    {
        $this->validate([
            'nombre'   => ['required', 'string', 'max:255'],
            'rtn'      => ['nullable', 'string', 'max:20'],
            'email'    => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'direccion'=> ['nullable', 'string', 'max:500'],
        ]);

        $datos = [
            'nombre'    => trim($this->nombre),
            'rtn'       => trim($this->rtn) ?: null,
            'direccion' => trim($this->direccion) ?: null,
            'telefono'  => trim($this->telefono) ?: null,
            'email'     => trim($this->email) ?: null,
            'activo'    => $this->activo,
        ];

        if ($this->editandoId) {
            Cliente::findOrFail($this->editandoId)->update($datos);
            $msg = 'Cliente actualizado.';
        } else {
            Cliente::create($datos);
            $msg = 'Cliente creado.';
        }

        $this->mostrarModal = false;
        $this->dispatch('toast', message: $msg, type: 'success');
    }

    public function toggleActivo(int $id): void
    {
        $c = Cliente::findOrFail($id);
        $c->update(['activo' => ! $c->activo]);
    }

    public function render(): View
    {
        $query = Cliente::orderBy('nombre');

        if ($this->soloActivos) {
            $query->where('activo', true);
        }

        if ($this->busqueda) {
            $b = $this->busqueda;
            $query->where(fn ($q) => $q
                ->where('nombre', 'like', "%$b%")
                ->orWhere('rtn', 'like', "%$b%")
                ->orWhere('email', 'like', "%$b%")
            );
        }

        return view('livewire.gestion-clientes', [
            'clientes' => $query->paginate(25),
        ]);
    }
}
