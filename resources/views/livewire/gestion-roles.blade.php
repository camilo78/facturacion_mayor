<div class="space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Roles</h1>
            <p class="text-sm text-gray-500 mt-0.5">Grupos de permisos asignables a usuarios</p>
        </div>
        <button wire:click="$set('mostrarModalCrear', true)" class="btn-secondary">
            <x-icon name="plus" class="w-4 h-4"/>
            Nuevo rol
        </button>
    </div>

    {{-- Listado de roles --}}
    @if($editandoRolId)
    {{-- ── EDITOR DE PERMISOS ──────────────────────────────── --}}
    @php $rolEditando = $roles->find($editandoRolId); @endphp
    <div class="card">
        <div class="px-5 py-4 border-b border-primary-100/60 bg-primary-50/40 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <h2 class="text-sm font-semibold text-gray-900">
                    Permisos del rol: <span class="text-primary-700">{{ $rolEditando->name }}</span>
                </h2>
                @if(in_array($rolEditando->name, $this::ROLES_SISTEMA))
                <x-ui.badge color="amber">Rol del sistema</x-ui.badge>
                @endif
            </div>
            <button wire:click="cerrarEdicion" class="btn-ghost btn-sm">
                <x-icon name="x-mark" class="w-4 h-4"/>
                Cerrar
            </button>
        </div>

        @if($rolEditando->name === 'Admin')
        <div class="p-5">
            <div class="flex items-start gap-2.5 rounded-md bg-blue-50 border border-blue-200 px-3 py-2.5 text-sm text-blue-800">
                <x-icon name="information-circle" class="w-4 h-4 text-blue-500 shrink-0 mt-0.5"/>
                El rol Admin siempre tiene todos los permisos del sistema. No es editable.
            </div>
        </div>
        @else

        @if(in_array($rolEditando->name, $this::ROLES_SISTEMA))
        <div class="px-5 pt-4">
            <div class="flex items-start gap-2.5 rounded-md bg-amber-50 border border-amber-200 px-3 py-2.5 text-xs text-amber-800">
                <x-icon name="exclamation-triangle" class="w-4 h-4 text-amber-500 shrink-0 mt-0.5"/>
                Estás modificando un rol del sistema. Los cambios afectan a todos los usuarios con este rol.
            </div>
        </div>
        @endif

        <div class="p-5 space-y-6">
            @foreach($todosPeros as $seccion => $permisosSec)
            <div>
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">{{ $seccion }}</h3>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-2">
                    @foreach($permisosSec as $perm)
                    <label class="flex items-center gap-2 cursor-pointer select-none group">
                        <input type="checkbox"
                               wire:model="permisosRol"
                               value="{{ $perm }}"
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-xs text-gray-700 group-hover:text-gray-900 transition-colors">
                            {{ $perm }}
                        </span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>

        <div class="px-5 py-4 border-t border-primary-100/60 bg-primary-50/30 flex justify-end gap-3">
            <button wire:click="cerrarEdicion" class="btn-secondary">Cancelar</button>
            <button wire:click="guardarPermisos" wire:loading.attr="disabled" wire:target="guardarPermisos" class="btn-primary">
                <svg wire:loading wire:target="guardarPermisos"
                     class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Guardar permisos
            </button>
        </div>
        @endif
    </div>

    @else
    {{-- ── LISTADO ────────────────────────────────────────── --}}
    <div class="card">
        <table class="min-w-full text-sm">
            <thead class="bg-primary-600">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Rol</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Permisos</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Usuarios</th>
                    <th class="px-5 py-3 w-20"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($roles as $rol)
                <tr class="hover:bg-primary-50/60 transition-colors">
                    <td class="px-5 py-3">
                        <span class="font-medium text-gray-900">{{ $rol->name }}</span>
                        @if(in_array($rol->name, $this::ROLES_SISTEMA))
                            <span class="ml-1.5 text-[10px] bg-amber-50 text-amber-700 border border-amber-200 px-1.5 py-0.5 rounded">sistema</span>
                        @endif
                    </td>
                    <td class="px-5 py-3">
                        <span class="tabular-nums text-gray-600">
                            {{ $rol->name === 'Admin' ? 'Todos' : $rol->permissions->count() }}
                        </span>
                        <span class="text-gray-400 text-xs ml-1">permisos</span>
                    </td>
                    <td class="px-5 py-3 tabular-nums text-gray-600">
                        {{ $rol->users_count }}
                    </td>
                    <td class="px-5 py-3 text-right">
                        <button wire:click="abrirEdicion({{ $rol->id }})" class="btn-ghost btn-sm">
                            <x-icon name="pencil" class="w-3.5 h-3.5"/>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    {{-- Modal nuevo rol --}}
    @if($mostrarModalCrear)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
         x-data x-init="$el.querySelector('[data-focus]')?.focus()"
         wire:click.self="$set('mostrarModalCrear', false)">
        <div class="card w-full max-w-sm shadow-xl">
            <div class="px-5 py-4 border-b border-primary-100/60 bg-primary-50/40">
                <h2 class="text-sm font-semibold text-gray-900">Nuevo rol</h2>
            </div>
            <form wire:submit="crearRol" class="p-5 space-y-4">
                <div>
                    <label class="form-label">Nombre del rol <span class="text-red-500">*</span></label>
                    <input wire:model="nuevoRolNombre" data-focus type="text"
                           placeholder="Ej: Vendedor, Gerente…"
                           class="form-input @error('nuevoRolNombre') error @enderror">
                    @error('nuevoRolNombre') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <p class="form-hint">Después de crear el rol podrás asignarle permisos desde el editor.</p>
                <div class="flex justify-end gap-3 pt-1 border-t border-gray-100">
                    <button type="button" wire:click="$set('mostrarModalCrear', false)" class="btn-secondary">Cancelar</button>
                    <button type="submit" wire:loading.attr="disabled" class="btn-primary">Crear rol</button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
