<div class="space-y-5">

    <div>
        <h1 class="text-xl font-semibold text-gray-900">Auditoría</h1>
        <p class="text-sm text-gray-500 mt-0.5">Registro de acciones en el sistema</p>
    </div>

    {{-- Filtros --}}
    <div class="card p-4">
        <div class="flex flex-wrap gap-3 items-end">

            {{-- Evento / descripción --}}
            <div class="flex-1 min-w-[200px]">
                <label class="form-label">Evento</label>
                <div class="relative">
                    <x-icon name="magnifying-glass" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"/>
                    <input wire:model.live.debounce.300ms="filtroEvento"
                           type="text" placeholder="Ej: factura.emitida, usuario.editado…"
                           class="form-input pl-9">
                </div>
            </div>

            {{-- Usuario --}}
            <div class="flex-1 min-w-[180px]">
                <label class="form-label">Usuario</label>
                <div class="relative">
                    <x-icon name="user-circle" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"/>
                    <input wire:model.live.debounce.300ms="filtroUsuario"
                           type="text" placeholder="Nombre o email…"
                           class="form-input pl-9">
                </div>
            </div>

            {{-- Rango de fechas --}}
            <div class="shrink-0">
                <label class="form-label">Período</label>
                <div class="flex items-center gap-1.5">
                    <input wire:model.live="filtroFechaDesde" type="date"
                           class="form-input w-36" title="Desde">
                    <span class="text-gray-400 text-sm select-none">–</span>
                    <input wire:model.live="filtroFechaHasta" type="date"
                           class="form-input w-36" title="Hasta">
                </div>
            </div>

            {{-- Limpiar (solo si hay algún filtro activo) --}}
            @if($filtroEvento || $filtroUsuario || $filtroFechaDesde || $filtroFechaHasta)
            <div class="shrink-0">
                <label class="form-label opacity-0 select-none">·</label>
                <button wire:click="$set('filtroEvento', ''); $set('filtroUsuario', ''); $set('filtroFechaDesde', ''); $set('filtroFechaHasta', '')"
                        class="btn-ghost btn-sm text-gray-400 hover:text-gray-700 whitespace-nowrap">
                    <x-icon name="x-mark" class="w-3.5 h-3.5"/>
                    Limpiar
                </button>
            </div>
            @endif

        </div>
    </div>

    {{-- Tabla --}}
    <div class="card">
        @if($actividades->isEmpty())
            <x-ui.empty-state icon="clipboard-list" title="Sin registros de auditoría"
                description="Los eventos del sistema aparecerán aquí cuando ocurran."/>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-primary-600">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Fecha</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Usuario</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Evento</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($actividades as $a)
                    @php
                        $eventColor = match(true) {
                            str_contains($a->description, 'emitida')    => 'green',
                            str_contains($a->description, 'anulada')    => 'red',
                            str_contains($a->description, 'creado')     => 'blue',
                            str_contains($a->description, 'editado')    => 'amber',
                            str_contains($a->description, 'eliminado')  => 'red',
                            str_contains($a->description, 'desactivado')=> 'gray',
                            str_contains($a->description, 'activado')   => 'green',
                            default                                      => 'gray',
                        };
                    @endphp
                    <tr class="hover:bg-primary-50/60 transition-colors">
                        <td class="px-5 py-3 text-xs text-gray-500 whitespace-nowrap">
                            {{ $a->created_at->format('d/m/Y H:i:s') }}
                        </td>
                        <td class="px-5 py-3">
                            @if($a->causer)
                                <p class="text-sm font-medium text-gray-900">{{ $a->causer->name }}</p>
                                <p class="text-xs text-gray-400">{{ $a->causer->email }}</p>
                            @else
                                <span class="text-xs text-gray-300">Sistema</span>
                            @endif
                        </td>
                        <td class="px-5 py-3">
                            <x-ui.badge :color="$eventColor">{{ $a->description }}</x-ui.badge>
                        </td>
                        <td class="px-5 py-3 text-xs text-gray-500 max-w-sm">
                            @if($a->properties && $a->properties->isNotEmpty())
                            <div class="font-mono bg-gray-50 border border-gray-100 rounded px-2 py-1 text-[10px] break-all">
                                @foreach($a->properties as $k => $v)
                                    @if(!in_array($k, ['old', 'attributes']) || is_scalar($v))
                                    <span class="text-gray-500">{{ $k }}:</span>
                                    <span class="text-gray-800">{{ is_array($v) ? json_encode($v) : $v }}</span>
                                    @if(!$loop->last) &nbsp;<span class="text-gray-300">·</span>&nbsp; @endif
                                    @endif
                                @endforeach
                            </div>
                            @else
                            <span class="text-gray-300">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($actividades->hasPages())
        <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-between text-sm text-gray-500">
            <span class="text-xs">
                Mostrando {{ $actividades->firstItem() }}–{{ $actividades->lastItem() }} de {{ $actividades->total() }}
            </span>
            {{ $actividades->links() }}
        </div>
        @else
        <div class="px-5 py-3 border-t border-gray-100 text-xs text-gray-400">
            {{ $actividades->total() }} registro(s)
        </div>
        @endif
        @endif
    </div>

</div>
