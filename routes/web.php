<?php

use App\Http\Controllers\FacturaPdfController;
use App\Livewire\Dashboard;
use App\Livewire\FormFactura;
use App\Livewire\PosFactura;
use App\Livewire\AuditoriaLog;
use App\Livewire\FormUsuario;
use App\Livewire\GestionCai;
use App\Livewire\GestionCajas;
use App\Livewire\GestionClientes;
use App\Livewire\GestionEstablecimientos;
use App\Livewire\GestionProductos;
use App\Livewire\GestionRoles;
use App\Livewire\GestionUsuarios;
use App\Livewire\ListadoFacturas;
use App\Livewire\LoginTenant;
use App\Livewire\PerfilUsuario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('empresa/{tenantId}')
    ->name('tenant.')
    ->middleware('tenancy.by.id')
    ->group(function () {

        // Rutas públicas del tenant
        Route::get('login',  LoginTenant::class)->name('login');

        Route::post('logout', function () {
            Auth::logout();
            session()->forget(['tenant_id', 'caja_activa_id']);
            request()->session()->invalidate();
            request()->session()->regenerateToken();
            return redirect('/');
        })->name('logout');

        // Rutas protegidas
        Route::middleware('tenant.auth')->group(function () {
            Route::get('/',          fn () => redirect()->route('tenant.dashboard', ['tenantId' => request()->route('tenantId')]))->name('home');
            Route::get('dashboard',  Dashboard::class)->name('dashboard');

            // Facturación
            Route::get('facturas',              ListadoFacturas::class)->name('facturas');
            Route::get('factura/nueva',         FormFactura::class)->name('factura.nueva');
            Route::get('pos',                   PosFactura::class)->name('pos');
            Route::get('factura/{id}/pdf',      [FacturaPdfController::class, 'show'])->name('factura.pdf');

            // Catálogos
            Route::get('clientes',  GestionClientes::class)->name('clientes');
            Route::get('productos', GestionProductos::class)->name('productos');

            // Configuración
            Route::get('establecimientos', GestionEstablecimientos::class)->name('establecimientos');
            Route::get('cajas',            GestionCajas::class)->name('cajas');
            Route::get('cai',              GestionCai::class)->name('cai');

            // Usuarios y roles
            Route::get('usuarios',           GestionUsuarios::class)->name('usuarios');
            Route::get('usuario/crear',      FormUsuario::class)->name('usuario.crear');
            Route::get('usuario/{userId}',   FormUsuario::class)->name('usuario.editar');
            Route::get('roles',              GestionRoles::class)->name('roles');

            // Perfil y auditoría
            Route::get('perfil',    PerfilUsuario::class)->name('perfil');
            Route::get('auditoria', AuditoriaLog::class)->name('auditoria');
        });
    });
