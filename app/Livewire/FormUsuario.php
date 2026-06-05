<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Spatie\Permission\Models\Role;

#[Layout('layouts.app')]
#[Title('Usuario')]
class FormUsuario extends Component
{
    public ?int $userId = null;

    public string $name     = '';
    public string $email    = '';
    public string $password = '';
    public string $rol      = '';
    public bool   $activo   = true;

    public bool   $mostrarPassword = false;

    public function mount(?int $userId = null): void
    {
        $this->userId = $userId;

        if ($userId) {
            $user = User::with('roles')->findOrFail($userId);
            $this->name   = $user->name;
            $this->email  = $user->email;
            $this->activo = (bool) $user->activo;
            $this->rol    = $user->roles->first()?->name ?? '';
        }
    }

    public function generarPassword(): void
    {
        $this->password       = Str::password(12);
        $this->mostrarPassword = true;
    }

    public function guardar(): mixed
    {
        $emailRule = $this->userId
            ? "required|email|unique:users,email,{$this->userId}"
            : 'required|email|unique:users,email';

        $this->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => [$emailRule],
            'password' => $this->userId ? ['nullable', 'min:8'] : ['required', 'min:8'],
            'rol'      => ['required', 'string'],
        ]);

        // No quitar el rol Admin si es el único admin activo
        if ($this->userId) {
            $usuario = User::findOrFail($this->userId);
            if ($usuario->hasRole('Admin') && $this->rol !== 'Admin') {
                $adminsActivos = User::role('Admin')->where('activo', true)->count();
                if ($adminsActivos <= 1) {
                    $this->addError('rol', 'No podés quitar el rol Admin al único administrador activo.');
                    return null;
                }
            }
        }

        $datos = [
            'name'   => trim($this->name),
            'email'  => trim($this->email),
            'activo' => $this->activo,
        ];

        if ($this->password) {
            $datos['password'] = Hash::make($this->password);
        }

        if ($this->userId) {
            $usuario = User::findOrFail($this->userId);
            $usuario->update($datos);
            $usuario->syncRoles([$this->rol]);

            activity()
                ->causedBy(auth()->user())
                ->performedOn($usuario)
                ->withProperties(['rol' => $this->rol])
                ->log('usuario.editado');

            $this->dispatch('toast', message: 'Usuario actualizado.', type: 'success');
        } else {
            $usuario = User::create($datos);
            $usuario->assignRole($this->rol);

            activity()
                ->causedBy(auth()->user())
                ->performedOn($usuario)
                ->withProperties(['rol' => $this->rol])
                ->log('usuario.creado');

            $this->dispatch('toast', message: 'Usuario creado.', type: 'success');

            return $this->redirect(
                route('tenant.usuarios', ['tenantId' => tenant('id')]),
                navigate: true
            );
        }
    }

    public function render(): View
    {
        return view('livewire.form-usuario', [
            'roles'    => Role::orderBy('name')->get(),
            'esEdicion'=> (bool) $this->userId,
        ]);
    }
}
