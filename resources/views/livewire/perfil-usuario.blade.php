<div class="max-w-2xl space-y-5">

    <div>
        <h1 class="text-xl font-semibold text-gray-900">Mi perfil</h1>
        <p class="text-sm text-gray-500 mt-0.5">Datos personales y seguridad de tu cuenta</p>
    </div>

    {{-- Datos básicos --}}
    <div class="card p-5 space-y-4">
        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Datos personales</h2>

        <div class="flex items-center gap-4 pb-2">
            <div class="w-12 h-12 rounded-full bg-primary-100 text-primary-700 text-lg font-bold flex items-center justify-center shrink-0">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>
            <div>
                <p class="font-semibold text-gray-900">{{ $user->name }}</p>
                <p class="text-sm text-gray-500">{{ $user->email }}</p>
                @if($rol = $user->roles->first())
                <x-ui.badge color="blue" class="mt-1">{{ $rol->name }}</x-ui.badge>
                @endif
            </div>
        </div>

        <form wire:submit="guardarPerfil" class="space-y-4">
            <div>
                <label class="form-label">Nombre completo</label>
                <input wire:model="name" type="text" class="form-input @error('name') error @enderror">
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="form-label">Email</label>
                <input wire:model="email" type="email" class="form-input @error('email') error @enderror">
                @error('email') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="flex justify-end">
                <button type="submit" wire:loading.attr="disabled" wire:target="guardarPerfil" class="btn-primary">
                    <svg wire:loading wire:target="guardarPerfil"
                         class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Guardar perfil
                </button>
            </div>
        </form>
    </div>

    {{-- Cambio de contraseña --}}
    <div class="card p-5 space-y-4">
        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Cambiar contraseña</h2>

        <form wire:submit="cambiarPassword" class="space-y-4">
            <div>
                <label class="form-label">Contraseña actual</label>
                <input wire:model="passwordActual" type="password" autocomplete="current-password"
                       class="form-input @error('passwordActual') error @enderror">
                @error('passwordActual') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="form-label">Nueva contraseña</label>
                    <input wire:model="passwordNuevo" type="password" autocomplete="new-password"
                           class="form-input @error('passwordNuevo') error @enderror">
                    @error('passwordNuevo') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Confirmar contraseña</label>
                    <input wire:model="passwordConfirm" type="password" autocomplete="new-password"
                           class="form-input @error('passwordConfirm') error @enderror">
                    @error('passwordConfirm') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex justify-end">
                <button type="submit" wire:loading.attr="disabled" wire:target="cambiarPassword" class="btn-primary">
                    <svg wire:loading wire:target="cambiarPassword"
                         class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Cambiar contraseña
                </button>
            </div>
        </form>
    </div>

    {{-- Permisos efectivos --}}
    <div class="card p-5">
        <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Permisos efectivos</h2>
        <p class="text-xs text-gray-500 mb-3">Permisos activos según tu rol actual. Útil para soporte y verificación.</p>
        @if($permisosEfectivos->isEmpty())
            <p class="text-sm text-gray-400">Sin permisos asignados.</p>
        @else
        <div class="flex flex-wrap gap-1.5">
            @foreach($permisosEfectivos as $perm)
            <span class="font-mono text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ $perm }}</span>
            @endforeach
        </div>
        @endif
    </div>

</div>
