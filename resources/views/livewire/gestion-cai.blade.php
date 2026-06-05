<div class="space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">CAI — Autorizaciones</h1>
            <p class="text-sm text-gray-500 mt-0.5">Claves de autorización de impresión del SAR</p>
        </div>
        <div class="flex items-center gap-3">
            <select wire:model.live="filtroPunto" class="form-select text-sm">
                <option value="">Todas las cajas</option>
                @foreach($puntosEmision as $pe)
                    <option value="{{ $pe->id }}">{{ $pe->establecimiento->codigo ?? '?' }}-{{ $pe->codigo }} {{ $pe->nombre }}</option>
                @endforeach
            </select>
            <button wire:click="abrirCrear" class="btn-primary">
                <x-icon name="plus" class="w-4 h-4"/>
                Nuevo CAI
            </button>
        </div>
    </div>

    <div class="card" padding="false">
        @if($cais->isEmpty())
            <x-ui.empty-state icon="key" title="Sin CAIs registrados"
                description="Registra el CAI otorgado por el SAR para cada caja."
                cta-label="Nuevo CAI" cta-action="abrirCrear"/>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-primary-600">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Caja</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Tipo</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">CAI</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide w-44">Uso</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Vence</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Estado</th>
                        <th class="px-5 py-3 w-24"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($cais as $c)
                    @php
                        $pct = round($c->porcentaje_usado * 100);
                        $estadoColor = match($c->estado) {
                            'VIGENTE' => 'green',
                            'AGOTADO' => 'gray',
                            'VENCIDO' => 'red',
                            default   => 'gray',
                        };
                    @endphp
                    <tr class="hover:bg-primary-50/60 transition-colors">
                        <td class="px-5 py-3">
                            <span class="font-mono text-xs font-medium text-gray-700">
                                {{ $c->puntoEmision->establecimiento->codigo ?? '?' }}-{{ $c->puntoEmision->codigo ?? '?' }}
                            </span>
                            <span class="block text-xs text-gray-400">{{ $c->puntoEmision->nombre ?? '' }}</span>
                        </td>
                        <td class="px-5 py-3 font-mono text-gray-600">{{ $c->tipo_documento }}</td>
                        <td class="px-5 py-3">
                            <span class="font-mono text-xs text-gray-600 block truncate max-w-[180px]" title="{{ $c->cai }}">
                                {{ $c->cai }}
                            </span>
                            <span class="text-xs text-gray-400">{{ number_format($c->rango_inicial) }} → {{ number_format($c->rango_final) }}</span>
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center gap-2">
                                <div class="flex-1 bg-gray-100 rounded-full h-1.5 min-w-[60px]">
                                    <div class="h-1.5 rounded-full transition-all {{ $pct >= 80 ? 'bg-red-500' : 'bg-primary-500' }}"
                                         style="width: {{ min($pct, 100) }}%"></div>
                                </div>
                                <span class="text-xs text-gray-500 tabular-nums w-8 text-right">{{ $pct }}%</span>
                            </div>
                            <div class="flex gap-1 mt-1">
                                @if($c->por_agotarse)
                                    <x-ui.badge color="orange">Por agotar</x-ui.badge>
                                @endif
                                @if($c->por_vencer)
                                    <x-ui.badge color="amber">Por vencer</x-ui.badge>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-3 text-sm text-gray-600 whitespace-nowrap">
                            {{ $c->fecha_limite_emision->format('d/m/Y') }}
                            @if($c->estado === 'VIGENTE')
                            <span class="block text-xs text-gray-400">
                                {{ $c->fecha_limite_emision->diffInDays(today()) }}d restantes
                            </span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <x-ui.badge :color="$estadoColor">{{ $c->estado }}</x-ui.badge>
                            @if(! $c->activo)
                                <x-ui.badge class="ml-1">Desact.</x-ui.badge>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-right whitespace-nowrap">
                            <button wire:click="abrirEditar({{ $c->id }})" class="btn-ghost btn-sm mr-1">
                                <x-icon name="pencil" class="w-3.5 h-3.5"/>
                            </button>
                            <button wire:click="toggleActivo({{ $c->id }})"
                                    class="btn-ghost btn-sm text-xs text-gray-400">
                                {{ $c->activo ? 'Desact.' : 'Activar' }}
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    {{-- Modal CAI --}}
    @if($mostrarModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
        <div class="card w-full max-w-lg shadow-xl">
            <div class="px-5 py-4 border-b border-primary-100/60 bg-primary-50/40">
                <h2 class="text-sm font-semibold text-gray-900">{{ $editandoId ? 'Editar CAI' : 'Nuevo CAI' }}</h2>
            </div>
            <form wire:submit="guardar" class="p-5 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Caja</label>
                        <select wire:model="puntoEmisionId" class="form-select @error('puntoEmisionId') error @enderror">
                            <option value="">Seleccionar…</option>
                            @foreach($puntosEmision as $pe)
                                <option value="{{ $pe->id }}">{{ $pe->establecimiento->codigo ?? '?' }}-{{ $pe->codigo }} {{ $pe->nombre }}</option>
                            @endforeach
                        </select>
                        @error('puntoEmisionId') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Tipo documento</label>
                        <select wire:model="tipoDocumento" class="form-select">
                            <option value="01">01 — Factura</option>
                            <option value="03">03 — Nota de crédito</option>
                            <option value="04">04 — Nota de débito</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="form-label">Código CAI</label>
                    <input wire:model="cai" type="text" placeholder="XXXXXX-XXXXXX-XXXXXX-XXXXXX-XXXXXX-XX"
                           class="form-input font-mono text-xs @error('cai') error @enderror">
                    @error('cai') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="form-label">Rango inicial</label>
                        <input wire:model="rangoInicial" type="number" min="1"
                               class="form-input @error('rangoInicial') error @enderror">
                        @error('rangoInicial') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label">Rango final</label>
                        <input wire:model="rangoFinal" type="number" min="1"
                               class="form-input @error('rangoFinal') error @enderror">
                        @error('rangoFinal') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>
                <div>
                    <label class="form-label">Fecha límite de emisión</label>
                    <input wire:model="fechaLimiteEmision" type="date"
                           class="form-input @error('fechaLimiteEmision') error @enderror">
                    @error('fechaLimiteEmision') <p class="form-error">{{ $message }}</p> @enderror
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
