<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Storage;
use App\Models\Tenant;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
#[Title('Configuración de Empresa')]
class ConfiguracionEmpresa extends Component
{
    use WithFileUploads;

    public string  $nombre           = '';
    public string  $nombreComercial  = '';
    public string  $rtn              = '';
    public string  $email            = '';
    public string  $telefono         = '';
    public string  $colorPrimario    = '#1b3a5c';
    public string  $colorSecundario  = '#009898';
    public ?string $logoActual       = null;
    public mixed   $logoFile         = null;

    public function mount(): void
    {
        $this->authorize('empresa.editar');

        $t = tenant();
        $this->nombre          = $t->nombre          ?? '';
        $this->nombreComercial = $t->nombre_comercial ?? '';
        $this->rtn             = $t->rtn              ?? '';
        $this->email           = $t->email            ?? '';
        $this->telefono        = $t->telefono         ?? '';
        $this->colorPrimario   = $t->color_primario   ?? '#1b3a5c';
        $this->colorSecundario = $t->color_secundario ?? '#009898';
        $this->logoActual      = $t->logo;
    }

    public function guardar(): void
    {
        $this->authorize('empresa.editar');

        $tenantId = tenant('id');

        $this->validate([
            'nombre'          => ['required', 'string', 'max:255'],
            'nombreComercial' => ['nullable', 'string', 'max:255'],
            'rtn'             => ['required', 'string', 'max:20'],
            'email'           => ['required', 'email', 'max:255'],
            'telefono'        => ['nullable', 'string', 'max:20'],
            'colorPrimario'   => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'colorSecundario' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logoFile'        => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:2048'],
        ], [
            'nombre.required' => 'El nombre legal es obligatorio.',
            'rtn.required'    => 'El RTN es obligatorio.',
            'email.required'  => 'El correo electrónico es obligatorio.',
            'email.email'     => 'Ingrese un correo válido.',
            'logoFile.image'  => 'El archivo debe ser una imagen.',
            'logoFile.mimes'  => 'Formatos aceptados: JPG, PNG, GIF, WEBP.',
            'logoFile.max'    => 'El logo no debe superar 2 MB.',
        ]);

        // Verificar unicidad de RTN en la BD central (Rule::unique usaría la BD del tenant)
        if (Tenant::where('rtn', trim($this->rtn))->where('id', '!=', $tenantId)->exists()) {
            $this->addError('rtn', 'Este RTN ya está registrado en otra empresa.');
            return;
        }

        $datos = [
            'nombre'          => trim($this->nombre),
            'nombre_comercial'=> trim($this->nombreComercial) ?: null,
            'rtn'             => trim($this->rtn),
            'email'           => trim($this->email),
            'telefono'        => trim($this->telefono) ?: null,
            'color_primario'  => strtolower($this->colorPrimario),
            'color_secundario'=> strtolower($this->colorSecundario),
        ];

        if ($this->logoFile) {
            // Eliminar logo anterior si existe
            if ($this->logoActual && Storage::disk('central_public')->exists($this->logoActual)) {
                Storage::disk('central_public')->delete($this->logoActual);
            }
            $ext      = strtolower($this->logoFile->getClientOriginalExtension());
            $filename = "{$tenantId}.{$ext}";
            // putFileAs mueve el archivo del temp de Livewire al disco central
            Storage::disk('central_public')->putFileAs('logos', $this->logoFile, $filename, 'public');
            $path             = "logos/{$filename}";
            $datos['logo']    = $path;
            $this->logoActual = $path;
            $this->logoFile   = null;
        }

        tenant()->update($datos);

        $this->dispatch('toast', message: 'Información de la empresa actualizada.', type: 'success');
    }

    public function quitarLogo(): void
    {
        $this->authorize('empresa.editar');

        if ($this->logoActual && Storage::disk('central_public')->exists($this->logoActual)) {
            Storage::disk('central_public')->delete($this->logoActual);
        }

        tenant()->update(['logo' => null]);
        $this->logoActual = null;

        $this->dispatch('toast', message: 'Logo eliminado.', type: 'success');
    }

    public function render(): View
    {
        return view('livewire.configuracion-empresa');
    }
}
