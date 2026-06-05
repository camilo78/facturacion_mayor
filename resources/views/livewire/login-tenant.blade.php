<div class="card p-8">
    <h2 class="text-base font-semibold text-gray-900 mb-6">Iniciar sesión</h2>

    @if($error)
    <div class="mb-4 flex items-start gap-2.5 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
        <x-icon name="x-circle" class="w-4 h-4 shrink-0 mt-0.5 text-red-500"/>
        {{ $error }}
    </div>
    @endif

    <form wire:submit="login" class="space-y-4">
        <div>
            <label for="email" class="form-label">Correo electrónico</label>
            <input wire:model="email" id="email" type="email" autocomplete="email"
                   class="form-input @error('email') error @enderror">
            @error('email') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="form-label">Contraseña</label>
            <input wire:model="password" id="password" type="password" autocomplete="current-password"
                   class="form-input @error('password') error @enderror">
            @error('password') <p class="form-error">{{ $message }}</p> @enderror
        </div>

        <button type="submit"
                wire:loading.attr="disabled"
                wire:target="login"
                class="btn-primary w-full mt-2">
            <svg wire:loading wire:target="login"
                 class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            Entrar
        </button>
    </form>
</div>
