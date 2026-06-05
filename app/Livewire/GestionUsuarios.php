<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
#[Title('Usuarios')]
class GestionUsuarios extends Component
{
    use WithPagination;

    public string $busqueda    = '';
    public string $filtroRol   = '';
    public string $filtroActivo = '';

    public function updatedBusqueda(): void    { $this->resetPage(); }
    public function updatedFiltroRol(): void   { $this->resetPage(); }
    public function updatedFiltroActivo(): void { $this->resetPage(); }

    public function toggleActivo(int $id): void
    {
        $usuario = User::findOrFail($id);

        // No permitir desactivar al último Admin
        if ($usuario->activo && $usuario->hasRole('Admin')) {
            $adminsActivos = User::role('Admin')->where('activo', true)->count();
            if ($adminsActivos <= 1) {
                $this->dispatch('toast', message: 'No se puede desactivar al único administrador activo.', type: 'error');
                return;
            }
        }

        $usuario->update(['activo' => ! $usuario->activo]);
        $estado = $usuario->fresh()->activo ? 'activado' : 'desactivado';

        activity()
            ->causedBy(auth()->user())
            ->performedOn($usuario)
            ->withProperties(['activo' => $usuario->fresh()->activo])
            ->log("usuario.{$estado}");

        $this->dispatch('toast', message: "Usuario {$estado}.", type: 'success');
    }

    public function render(): View
    {
        $query = User::with('roles')->orderBy('name');

        if ($this->filtroRol) {
            $query->role($this->filtroRol);
        }

        if ($this->filtroActivo !== '') {
            $query->where('activo', (bool) $this->filtroActivo);
        }

        if ($this->busqueda) {
            $b = $this->busqueda;
            $query->where(fn ($q) => $q
                ->where('name', 'like', "%$b%")
                ->orWhere('email', 'like', "%$b%")
            );
        }

        return view('livewire.gestion-usuarios', [
            'usuarios' => $query->paginate(20),
            'roles'    => Role::orderBy('name')->get(),
        ]);
    }
}
