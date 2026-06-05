<div class="space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Clientes</h1>
            <p class="text-sm text-gray-500 mt-0.5">Catálogo de clientes registrados</p>
        </div>
        <button wire:click="abrirCrear" class="btn-primary">
            <x-icon name="plus" class="w-4 h-4"/>
            Nuevo cliente
        </button>
    </div>

    {{-- Filtros --}}
    <div class="card p-4">
        <div class="flex flex-wrap gap-3 items-center">
            <div class="relative flex-1 min-w-[220px] max-w-sm">
                <x-icon name="magnifying-glass" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"/>
                <input wire:model.live.debounce.300ms="busqueda"
                       type="text" placeholder="Nombre, RTN o correo…"
                       class="form-input pl-9">
            </div>
            <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-600 select-none">
                <input wire:model.live="soloActivos" type="checkbox"
                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                Solo activos
            </label>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card">
        @if($clientes->isEmpty())
            <x-ui.empty-state icon="users" title="Sin clientes"
                description="Registra el primer cliente del catálogo."
                cta-label="Nuevo cliente" cta-action="abrirCrear"/>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-primary-600">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Nombre</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">RTN</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide hidden md:table-cell">Teléfono</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide hidden lg:table-cell">Email</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Estado</th>
                        <th class="px-5 py-3 w-14"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($clientes as $c)
                    <tr class="hover:bg-primary-50/60 transition-colors">
                        <td class="px-5 py-3 font-medium text-gray-900">{{ $c->nombre }}</td>
                        <td class="px-5 py-3">
                            @if($c->rtn)
                                <span class="font-mono text-xs text-gray-600">{{ $c->rtn }}</span>
                            @else
                                <span class="text-gray-300 text-xs">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-gray-500 hidden md:table-cell">{{ $c->telefono ?? '—' }}</td>
                        <td class="px-5 py-3 text-gray-500 max-w-[200px] truncate hidden lg:table-cell">{{ $c->email ?? '—' }}</td>
                        <td class="px-5 py-3">
                            <button wire:click="toggleActivo({{ $c->id }})" wire:loading.attr="disabled">
                                <x-ui.badge :color="$c->activo ? 'green' : 'gray'">
                                    {{ $c->activo ? 'Activo' : 'Inactivo' }}
                                </x-ui.badge>
                            </button>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <button wire:click="abrirEditar({{ $c->id }})" class="btn-ghost btn-sm">
                                <x-icon name="pencil" class="w-3.5 h-3.5"/>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($clientes->hasPages())
        <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-between text-sm text-gray-500">
            <span class="text-xs">
                Mostrando {{ $clientes->firstItem() }}–{{ $clientes->lastItem() }} de {{ $clientes->total() }}
            </span>
            {{ $clientes->links() }}
        </div>
        @else
        <div class="px-5 py-3 border-t border-gray-100 text-xs text-gray-400">
            {{ $clientes->total() }} cliente(s)
        </div>
        @endif
        @endif
    </div>

    {{-- Modal --}}
    @if($mostrarModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
         x-data x-init="$el.querySelector('[data-focus]')?.focus()"
         wire:click.self="$set('mostrarModal', false)">
        <div class="card w-full max-w-md shadow-xl">
            <div class="px-5 py-4 border-b border-primary-100/60 bg-primary-50/40 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-gray-900">
                    {{ $editandoId ? 'Editar cliente' : 'Nuevo cliente' }}
                </h2>
                <button wire:click="$set('mostrarModal', false)"
                        class="text-gray-400 hover:text-gray-600 transition-colors p-1 -mr-1 rounded">
                    <x-icon name="x-mark" class="w-4 h-4"/>
                </button>
            </div>
            <form wire:submit="guardar" class="p-5 space-y-4">
                <div>
                    <label class="form-label">Nombre <span class="text-red-500">*</span></label>
                    <input wire:model="nombre" data-focus type="text" autocomplete="off"
                           class="form-input @error('nombre') error @enderror">
                    @error('nombre') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">RTN</label>
                    <input wire:model="rtn" type="text" maxlength="20" placeholder="0501-1978-000000"
                           class="form-input font-mono @error('rtn') error @enderror">
                    <p class="form-hint">Sin guiones para almacenamiento, se muestra con guiones.</p>
                    @error('rtn') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Teléfono</label>
                        <input wire:model="telefono" type="text" class="form-input @error('telefono') error @enderror">
                        @error('telefono') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Email</label>
                        <input wire:model="email" type="email" class="form-input @error('email') error @enderror">
                        @error('email') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="form-label">Dirección</label>
                    <input wire:model="direccion" type="text" class="form-input">
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input wire:model="activo" type="checkbox"
                           class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <span class="text-sm text-gray-700">Activo</span>
                </label>
                <div class="flex justify-end gap-3 pt-1 border-t border-gray-100">
                    <button type="button" wire:click="$set('mostrarModal', false)" class="btn-secondary">
                        Cancelar
                    </button>
                    <button type="submit" wire:loading.attr="disabled" wire:target="guardar" class="btn-primary">
                        <svg wire:loading wire:target="guardar"
                             class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
