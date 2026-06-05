<div class="space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Establecimientos</h1>
            <p class="text-sm text-gray-500 mt-0.5">Sucursales y casa matriz</p>
        </div>
        <button wire:click="abrirCrear" class="btn-primary">
            <x-icon name="plus" class="w-4 h-4"/>
            Nuevo
        </button>
    </div>

    <div class="card" padding="false">
        @if($establecimientos->isEmpty())
            <x-ui.empty-state icon="building-office" title="Sin establecimientos"
                description="Crea el primer establecimiento (casa matriz 000)."
                cta-label="Nuevo establecimiento" cta-action="abrirCrear"/>
        @else
        <table class="min-w-full text-sm">
            <thead class="bg-primary-600">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Código</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Nombre</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Dirección</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Teléfono</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Estado</th>
                    <th class="px-5 py-3 w-20"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($establecimientos as $e)
                <tr class="hover:bg-primary-50/60 transition-colors">
                    <td class="px-5 py-3 font-mono font-semibold text-primary-700">{{ $e->codigo }}</td>
                    <td class="px-5 py-3 text-gray-900 font-medium">{{ $e->nombre }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $e->direccion ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $e->telefono ?? '—' }}</td>
                    <td class="px-5 py-3">
                        <button wire:click="toggleActivo({{ $e->id }})" wire:loading.attr="disabled">
                            <x-ui.badge :color="$e->activo ? 'green' : 'gray'">
                                {{ $e->activo ? 'Activo' : 'Inactivo' }}
                            </x-ui.badge>
                        </button>
                    </td>
                    <td class="px-5 py-3 text-right">
                        <button wire:click="abrirEditar({{ $e->id }})" class="btn-ghost btn-sm">
                            <x-icon name="pencil" class="w-3.5 h-3.5"/>
                        </button>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>

    {{-- Modal --}}
    @if($mostrarModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
        <div class="card w-full max-w-md shadow-xl">
            <div class="px-5 py-4 border-b border-primary-100/60 bg-primary-50/40">
                <h2 class="text-sm font-semibold text-gray-900">
                    {{ $editandoId ? 'Editar establecimiento' : 'Nuevo establecimiento' }}
                </h2>
            </div>
            <form wire:submit="guardar" class="p-5 space-y-4">
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="form-label">Código SAR</label>
                        <input wire:model="codigo" type="text" maxlength="3" placeholder="000"
                               class="form-input font-mono @error('codigo') error @enderror">
                        @error('codigo') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div class="col-span-2">
                        <label class="form-label">Nombre</label>
                        <input wire:model="nombre" type="text"
                               class="form-input @error('nombre') error @enderror">
                        @error('nombre') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="form-label">Dirección <span class="text-gray-400 font-normal">(opcional)</span></label>
                    <input wire:model="direccion" type="text" class="form-input">
                </div>
                <div>
                    <label class="form-label">Teléfono <span class="text-gray-400 font-normal">(opcional)</span></label>
                    <input wire:model="telefono" type="text" class="form-input">
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input wire:model="activo" type="checkbox" class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                    <span class="text-sm text-gray-700">Activo</span>
                </label>
                <div class="flex justify-end gap-3 pt-1 border-t border-gray-100">
                    <button type="button" wire:click="$set('mostrarModal', false)" class="btn-secondary">Cancelar</button>
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
