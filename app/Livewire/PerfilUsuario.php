<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Mi perfil')]
class PerfilUsuario extends Component
{
    public string $name            = '';
    public string $email           = '';
    public string $passwordActual  = '';
    public string $passwordNuevo   = '';
    public string $passwordConfirm = '';

    public function mount(): void
    {
        $user        = auth()->user();
        $this->name  = $user->name;
        $this->email = $user->email;
    }

    public function guardarPerfil(): void
    {
        $this->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', "unique:users,email,{$this->getAuthId()}"],
        ]);

        auth()->user()->update([
            'name'  => trim($this->name),
            'email' => trim($this->email),
        ]);

        $this->dispatch('toast', message: 'Perfil actualizado.', type: 'success');
    }

    public function cambiarPassword(): void
    {
        $this->validate([
            'passwordActual'  => ['required'],
            'passwordNuevo'   => ['required', Password::min(8)],
            'passwordConfirm' => ['same:passwordNuevo'],
        ]);

        if (! Hash::check($this->passwordActual, auth()->user()->password)) {
            $this->addError('passwordActual', 'La contraseña actual no es correcta.');
            return;
        }

        auth()->user()->update(['password' => Hash::make($this->passwordNuevo)]);

        $this->reset(['passwordActual', 'passwordNuevo', 'passwordConfirm']);
        $this->dispatch('toast', message: 'Contraseña cambiada.', type: 'success');
    }

    private function getAuthId(): int
    {
        return auth()->id();
    }

    public function render(): View
    {
        $user = auth()->user()->load('roles', 'permissions');

        return view('livewire.perfil-usuario', [
            'user'            => $user,
            'permisosEfectivos' => $user->getAllPermissions()->pluck('name')->sort()->values(),
        ]);
    }
}
