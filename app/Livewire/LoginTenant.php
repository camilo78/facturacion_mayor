<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\View\View;

#[Layout('layouts.guest')]
#[Title('Iniciar sesión')]
class LoginTenant extends Component
{
    public string $email    = '';
    public string $password = '';
    public string $error    = '';

    public function login(): void
    {
        $this->error = '';

        $this->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            $this->error = 'Correo o contraseña incorrectos.';
            return;
        }

        session()->regenerate();

        $this->redirect(
            route('tenant.facturas', ['tenantId' => tenant('id')]),
            navigate: true
        );
    }

    public function render(): View
    {
        return view('livewire.login-tenant');
    }
}
