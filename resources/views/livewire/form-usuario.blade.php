<div class="max-w-2xl space-y-5">

    <div class="flex items-center gap-3">
        <a href="{{ route('tenant.usuarios', tenant('id')) }}"
           class="text-gray-400 hover:text-gray-600 transition-colors p-1 -ml-1 rounded">
            <x-icon name="arrow-left" class="w-5 h-5"/>
        </a>
        <div>
            <h1 class="text-xl font-semibold text-gray-900">
                {{ $esEdicion ? 'Editar usuario' : 'Nuevo usuario' }}
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ $esEdicion ? 'Modificar datos y rol del usuario' : 'Crear una nueva cuenta de acceso' }}
            </p>
        </div>
    </div>

    <form wire:submit="guardar" class="space-y-5">

        {{-- Datos básicos --}}
        <div class="card p-5 space-y-4">
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Datos de acceso</h2>

            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="form-label">Nombre completo <span class="text-red-500">*</span></label>
                    <input wire:model="name" type="text" autocomplete="off"
                           class="form-input @error('name') error @enderror">
                    @error('name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="col-span-2">
                    <label class="form-label">Email <span class="text-red-500">*</span></label>
                    <input wire:model="email" type="email" autocomplete="off"
                           class="form-input @error('email') error @enderror">
                    @error('email') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="form-label">
                    Contraseña
                    @if($esEdicion)
                        <span class="text-gray-400 font-normal">(dejar en blanco para no cambiar)</span>
                    @else
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                <div class="flex gap-2">
                    <div class="relative flex-1">
                        <input wire:model="password"
                               x-data
                               :type="$wire.mostrarPassword ? 'text' : 'password'"
                               autocomplete="new-password"
                               class="form-input font-mono @error('password') error @enderror"
                               placeholder="{{ $esEdicion ? '••••••••' : 'Mínimo 8 caracteres' }}">
                    </div>
                    <button type="button" wire:click="generarPassword"
                            class="btn-secondary btn-sm shrink-0 whitespace-nowrap">
                        Generar
                    </button>
                </div>
                @if($mostrarPassword && $password)
                <div class="mt-1.5 flex items-center gap-2 rounded-md bg-amber-50 border border-amber-200 px-3 py-2">
                    <x-icon name="eye" class="w-4 h-4 text-amber-500 shrink-0"/>
                    <p class="text-xs font-mono text-amber-800 break-all select-all">{{ $password }}</p>
                </div>
                <p class="form-hint">Guardá esta contraseña — no se podrá recuperar después.</p>
                @endif
                @error('password') <p class="form-error">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Rol y estado --}}
        <div class="card p-5 space-y-4">
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Rol y estado</h2>

            <div>
                <label class="form-label">Rol <span class="text-red-500">*</span></label>
                <select wire:model="rol" class="form-select @error('rol') error @enderror">
                    <option value="">Seleccionar rol…</option>
                    @foreach($roles as $r)
                        <option value="{{ $r->name }}">{{ $r->name }}</option>
                    @endforeach
                </select>
                @error('rol') <p class="form-error">{{ $message }}</p> @enderror

                {{-- Descripción contextual del rol seleccionado --}}
                @if($rol)
                @php
                    $desc = match($rol) {
                        'Admin'             => 'Acceso completo a toda la empresa.',
                        'Contador'          => 'Acceso fiscal y de reportes. Sin operación de caja ni gestión de usuarios.',
                        'Supervisor de Caja'=> 'Puede emitir y anular facturas, y ver usuarios. Sin acceso a configuración.',
                        'Cajero'            => 'Solo emisión de facturas y consulta de clientes.',
                        default             => null,
                    };
                @endphp
                @if($desc)
                <p class="form-hint">{{ $desc }}</p>
                @endif
                @endif
            </div>

            <label class="flex items-center gap-2 cursor-pointer">
                <input wire:model="activo" type="checkbox"
                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                <span class="text-sm text-gray-700">Usuario activo</span>
            </label>
        </div>

        {{-- Acciones --}}
        <div class="flex items-center justify-between">
            <a href="{{ route('tenant.usuarios', tenant('id')) }}" class="btn-secondary">
                Cancelar
            </a>
            <button type="submit" wire:loading.attr="disabled" wire:target="guardar" class="btn-primary">
                <svg wire:loading wire:target="guardar"
                     class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{ $esEdicion ? 'Guardar cambios' : 'Crear usuario' }}
            </button>
        </div>

    </form>
</div>
