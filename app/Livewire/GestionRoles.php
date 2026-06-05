<?php

namespace App\Livewire;

use Database\Seeders\RolesPermisosSeeder;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
#[Title('Roles')]
class GestionRoles extends Component
{
    public ?int $editandoRolId = null;

    // Campos para crear rol custom
    public bool   $mostrarModalCrear = false;
    public string $nuevoRolNombre    = '';

    // Matriz de permisos del rol en edición
    public array $permisosRol = [];

    public const ROLES_SISTEMA = ['Admin', 'Contador', 'Supervisor de Caja', 'Cajero'];

    public function abrirEdicion(int $rolId): void
    {
        $rol = Role::with('permissions')->findOrFail($rolId);
        $this->editandoRolId = $rolId;
        $this->permisosRol   = $rol->permissions->pluck('name')->toArray();
    }

    public function cerrarEdicion(): void
    {
        $this->editandoRolId = null;
        $this->permisosRol   = [];
    }

    public function guardarPermisos(): void
    {
        $rol = Role::findOrFail($this->editandoRolId);

        // Admin siempre tiene todos los permisos
        if ($rol->name === 'Admin') {
            $this->dispatch('toast', message: 'El rol Admin siempre tiene todos los permisos.', type: 'info');
            return;
        }

        $rol->syncPermissions($this->permisosRol);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($rol)
            ->withProperties(['permisos' => $this->permisosRol])
            ->log('rol.permisos_actualizados');

        $this->dispatch('toast', message: "Permisos del rol «{$rol->name}» actualizados.", type: 'success');
        $this->cerrarEdicion();
    }

    public function crearRol(): void
    {
        $this->validate(['nuevoRolNombre' => ['required', 'string', 'max:50', 'unique:roles,name']]);

        $rol = Role::create(['name' => trim($this->nuevoRolNombre), 'guard_name' => 'web']);

        activity()
            ->causedBy(auth()->user())
            ->withProperties(['rol' => $rol->name])
            ->log('rol.creado');

        $this->mostrarModalCrear = false;
        $this->nuevoRolNombre    = '';
        $this->dispatch('toast', message: "Rol «{$rol->name}» creado.", type: 'success');
    }

    public function render(): View
    {
        return view('livewire.gestion-roles', [
            'roles'           => Role::withCount('users')->with('permissions')->orderBy('name')->get(),
            'todosPeros'      => collect(RolesPermisosSeeder::PERMISOS),
            'todosPermisos'   => Permission::orderBy('name')->pluck('name'),
        ]);
    }
}
