<div class="space-y-5">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Facturas</h1>
            <p class="text-sm text-gray-500 mt-0.5">Historial de facturas emitidas</p>
        </div>
        <a href="{{ route('tenant.factura.nueva', tenant('id')) }}" class="btn-primary">
            <x-icon name="plus" class="w-4 h-4 shrink-0"/>
            Nueva factura
        </a>
    </div>

    {{-- Filtros --}}
    <div class="card p-4">
        <div class="flex gap-3 items-center">
            <div class="relative flex-1 min-w-0">
                <x-icon name="magnifying-glass" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"/>
                <input wire:model.live.debounce.300ms="filtroBusqueda"
                       type="text" placeholder="Buscar número, cliente, RTN…"
                       class="form-input pl-9">
            </div>
            <select wire:model.live="filtroEstado" class="form-select w-40 shrink-0">
                <option value="">Todos los estados</option>
                <option value="VIGENTE">VIGENTE</option>
                <option value="ANULADA">ANULADA</option>
            </select>
            <input wire:model.live="filtroFechaDesde" type="date" class="form-input w-36 shrink-0" title="Desde">
            <input wire:model.live="filtroFechaHasta" type="date" class="form-input w-36 shrink-0" title="Hasta">
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card" padding="false">
        @if($facturas->isEmpty())
            <x-ui.empty-state
                icon="document-text"
                title="Sin facturas"
                description="No hay facturas que coincidan con los filtros aplicados."/>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-primary-600">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Número</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Cliente</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Caja</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Fecha</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Pago</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-white/90 uppercase tracking-wide">Total</th>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Estado</th>
                        <th class="px-5 py-3 w-28"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($facturas as $f)
                    <tr class="hover:bg-primary-50/60 transition-colors {{ $f->estado === 'ANULADA' ? 'opacity-60' : '' }}">
                        <td class="px-5 py-3">
                            <span class="font-mono text-xs font-semibold text-primary-700">{{ $f->numero_completo }}</span>
                        </td>
                        <td class="px-5 py-3 max-w-[180px]">
                            <p class="truncate text-gray-900">{{ $f->nombre_cliente }}</p>
                            @if($f->rtn_cliente)
                            <p class="font-mono text-xs text-gray-400">{{ $f->rtn_cliente }}</p>
                            @endif
                        </td>
                        <td class="px-5 py-3 font-mono text-xs text-gray-500">
                            {{ $f->establecimiento_codigo }}-{{ $f->punto_emision_codigo }}
                        </td>
                        <td class="px-5 py-3 text-gray-500 whitespace-nowrap">{{ $f->fecha_emision->format('d/m/Y H:i') }}</td>
                        <td class="px-5 py-3 capitalize text-gray-500">{{ $f->tipo_pago }}</td>
                        <td class="px-5 py-3 text-right tabular-nums font-semibold text-gray-900 whitespace-nowrap">
                            L. {{ number_format($f->total, 2) }}
                        </td>
                        <td class="px-5 py-3">
                            <x-ui.badge :color="$f->estado === 'VIGENTE' ? 'green' : 'red'">
                                {{ $f->estado }}
                            </x-ui.badge>
                        </td>
                        <td class="px-5 py-3">
                            <div class="flex items-center justify-end gap-1">
                                {{-- Ver --}}
                                <button wire:click="verFactura({{ $f->id }})"
                                        title="Ver factura"
                                        class="btn-ghost btn-sm text-gray-500">
                                    <x-icon name="eye" class="w-4 h-4"/>
                                </button>
                                {{-- Imprimir PDF --}}
                                <a href="{{ route('tenant.factura.pdf', [tenant('id'), $f->id]) }}"
                                   target="_blank"
                                   title="Descargar PDF"
                                   class="btn-ghost btn-sm text-gray-500">
                                    <x-icon name="printer" class="w-4 h-4"/>
                                </a>
                                {{-- Anular --}}
                                @if($f->estado === 'VIGENTE')
                                <button wire:click="abrirAnulacion({{ $f->id }}, '{{ $f->numero_completo }}')"
                                        wire:loading.attr="disabled"
                                        title="Anular factura"
                                        class="btn-ghost btn-sm text-red-500 hover:text-red-700 hover:bg-red-50">
                                    <x-icon name="x-circle" class="w-4 h-4"/>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($facturas->hasPages())
        <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-between text-sm text-gray-500">
            <span>Mostrando {{ $facturas->firstItem() }}–{{ $facturas->lastItem() }} de {{ $facturas->total() }}</span>
            {{ $facturas->links() }}
        </div>
        @else
        <div class="px-5 py-3 border-t border-gray-100 text-xs text-gray-400">
            {{ $facturas->total() }} factura(s) encontrada(s)
        </div>
        @endif
        @endif
    </div>

    {{-- ══ MODAL VER FACTURA ═══════════════════════════════════════════ --}}
    @if($mostrarModalVer && $facturaViendo)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50"
         x-data
         wire:click.self="cerrarVer">
        <div class="card w-full max-w-3xl shadow-2xl max-h-[90vh] flex flex-col">

            {{-- Header modal --}}
            <div class="px-5 py-4 border-b border-primary-100/60 bg-primary-50/40 flex items-center justify-between shrink-0">
                <div>
                    <h2 class="text-sm font-semibold text-gray-900">Detalle de factura</h2>
                    <p class="text-xs font-mono text-primary-700 mt-0.5">{{ $facturaViendo->numero_completo }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('tenant.factura.pdf', [tenant('id'), $facturaViendo->id]) }}"
                       target="_blank"
                       class="btn-secondary btn-sm">
                        <x-icon name="printer" class="w-3.5 h-3.5 shrink-0"/>
                        Imprimir PDF
                    </a>
                    <button wire:click="cerrarVer" class="btn-ghost btn-sm text-gray-500">
                        <x-icon name="x-mark" class="w-4 h-4"/>
                    </button>
                </div>
            </div>

            {{-- Cuerpo scrollable --}}
            <div class="overflow-y-auto flex-1">

                {{-- Badge estado --}}
                @if($facturaViendo->estado === 'ANULADA')
                <div class="px-5 pt-4">
                    <div class="flex items-start gap-2.5 bg-red-50 border border-red-200 px-3 py-2.5 text-xs text-red-800">
                        <x-icon name="x-circle" class="w-4 h-4 text-red-500 shrink-0 mt-0.5"/>
                        <div>
                            <span class="font-semibold">FACTURA ANULADA</span>
                            @if($facturaViendo->motivo_anulacion)
                                — {{ $facturaViendo->motivo_anulacion }}
                            @endif
                            @if($facturaViendo->anulada_at)
                                <span class="text-red-500 ml-1">({{ $facturaViendo->anulada_at->format('d/m/Y H:i') }})</span>
                            @endif
                        </div>
                    </div>
                </div>
                @endif

                {{-- Datos generales --}}
                <div class="p-5 grid grid-cols-2 sm:grid-cols-4 gap-4 border-b border-gray-100">
                    <div>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Fecha emisión</p>
                        <p class="text-sm text-gray-800 mt-0.5">{{ $facturaViendo->fecha_emision->format('d/m/Y') }}</p>
                        <p class="text-xs text-gray-400">{{ $facturaViendo->fecha_emision->format('H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Tipo de pago</p>
                        <p class="text-sm text-gray-800 mt-0.5 capitalize">{{ $facturaViendo->tipo_pago }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Establecimiento</p>
                        <p class="text-sm text-gray-800 mt-0.5">
                            {{ $facturaViendo->puntoEmision->establecimiento->nombre ?? '—' }}
                        </p>
                        <p class="text-xs font-mono text-gray-400">{{ $facturaViendo->establecimiento_codigo }}-{{ $facturaViendo->punto_emision_codigo }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide">Estado</p>
                        <div class="mt-1">
                            <x-ui.badge :color="$facturaViendo->estado === 'VIGENTE' ? 'green' : 'red'">
                                {{ $facturaViendo->estado }}
                            </x-ui.badge>
                        </div>
                    </div>
                </div>

                {{-- Cliente --}}
                <div class="px-5 py-4 border-b border-gray-100">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-2">Cliente</p>
                    <p class="text-sm font-semibold text-gray-900">{{ $facturaViendo->nombre_cliente }}</p>
                    @if($facturaViendo->rtn_cliente)
                    <p class="text-xs font-mono text-gray-500 mt-0.5">RTN: {{ $facturaViendo->rtn_cliente }}</p>
                    @else
                    <p class="text-xs text-gray-400 mt-0.5">Consumidor Final</p>
                    @endif
                    @if($facturaViendo->direccion_cliente)
                    <p class="text-xs text-gray-500 mt-0.5">{{ $facturaViendo->direccion_cliente }}</p>
                    @endif
                </div>

                {{-- Campos SAR exoneración (solo si aplica) --}}
                @if($facturaViendo->orden_compra_exenta || $facturaViendo->num_constancia_exonerado || $facturaViendo->num_registro_sag)
                <div class="px-5 py-4 border-b border-gray-100 bg-amber-50/40">
                    <p class="text-[10px] font-semibold text-amber-700 uppercase tracking-wide mb-2">Exoneración / Exención SAR</p>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        @if($facturaViendo->orden_compra_exenta)
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide">No. Orden de compra exenta</p>
                            <p class="text-xs font-mono font-semibold text-gray-800 mt-0.5">{{ $facturaViendo->orden_compra_exenta }}</p>
                        </div>
                        @endif
                        @if($facturaViendo->num_constancia_exonerado)
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide">No. Constancia Reg. Exonerado</p>
                            <p class="text-xs font-mono font-semibold text-gray-800 mt-0.5">{{ $facturaViendo->num_constancia_exonerado }}</p>
                        </div>
                        @endif
                        @if($facturaViendo->num_registro_sag)
                        <div>
                            <p class="text-[10px] text-gray-400 uppercase tracking-wide">No. Registro SAG</p>
                            <p class="text-xs font-mono font-semibold text-gray-800 mt-0.5">{{ $facturaViendo->num_registro_sag }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                {{-- Detalles --}}
                <div class="border-b border-gray-100">
                    <table class="min-w-full text-sm">
                        <thead class="bg-primary-600">
                            <tr>
                                <th class="px-5 py-2.5 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Descripción</th>
                                <th class="px-3 py-2.5 text-right text-xs font-semibold text-white/90 uppercase tracking-wide">Cant.</th>
                                <th class="px-3 py-2.5 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Unidad</th>
                                <th class="px-3 py-2.5 text-right text-xs font-semibold text-white/90 uppercase tracking-wide">P. Unit.</th>
                                <th class="px-3 py-2.5 text-right text-xs font-semibold text-white/90 uppercase tracking-wide">ISV</th>
                                <th class="px-5 py-2.5 text-right text-xs font-semibold text-white/90 uppercase tracking-wide">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($facturaViendo->detalles as $d)
                            <tr class="hover:bg-primary-50/40">
                                <td class="px-5 py-2.5 text-gray-800">{{ $d->descripcion }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums text-gray-600">{{ number_format($d->cantidad, 2) }}</td>
                                <td class="px-3 py-2.5 text-gray-500 text-xs">{{ $d->unidad_medida }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums text-gray-600">{{ number_format($d->precio_unitario, 2) }}</td>
                                <td class="px-3 py-2.5 text-right tabular-nums text-gray-500 text-xs">
                                    @if($d->impuesto_tasa > 0)
                                        {{ number_format($d->impuesto_tasa, 0) }}%
                                    @else
                                        <span class="text-gray-400">Exento</span>
                                    @endif
                                </td>
                                <td class="px-5 py-2.5 text-right tabular-nums font-semibold text-gray-900">
                                    L. {{ number_format($d->total, 2) }}
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Totales --}}
                <div class="p-5 flex justify-end border-b border-gray-100">
                    <div class="w-64 space-y-1.5 text-sm">
                        @if($facturaViendo->subtotal_exento > 0)
                        <div class="flex justify-between text-gray-500">
                            <span>Subtotal exento</span>
                            <span class="tabular-nums">L. {{ number_format($facturaViendo->subtotal_exento, 2) }}</span>
                        </div>
                        @endif
                        @if($facturaViendo->subtotal_gravado > 0)
                        <div class="flex justify-between text-gray-500">
                            <span>Subtotal gravado</span>
                            <span class="tabular-nums">L. {{ number_format($facturaViendo->subtotal_gravado, 2) }}</span>
                        </div>
                        @endif
                        @if($facturaViendo->descuento > 0)
                        <div class="flex justify-between text-red-600">
                            <span>Descuento</span>
                            <span class="tabular-nums">- L. {{ number_format($facturaViendo->descuento, 2) }}</span>
                        </div>
                        @endif
                        @if($facturaViendo->total_isv > 0)
                        <div class="flex justify-between text-gray-500 border-t border-gray-100 pt-1.5">
                            <span>ISV</span>
                            <span class="tabular-nums">L. {{ number_format($facturaViendo->total_isv, 2) }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between font-bold text-base bg-[#1b3a5c] text-white px-3 py-2 mt-1">
                            <span>TOTAL</span>
                            <span class="tabular-nums font-mono">L. {{ number_format($facturaViendo->total, 2) }}</span>
                        </div>
                    </div>
                </div>

                {{-- CAI --}}
                <div class="px-5 py-4">
                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-1.5">Código de Autorización de Impresión (CAI)</p>
                    <p class="font-mono text-xs font-semibold text-primary-700 tracking-wider">{{ $facturaViendo->cai }}</p>
                    @if($facturaViendo->caiAutorizacion)
                    <p class="text-xs text-gray-500 mt-1">
                        Rango: {{ str_pad($facturaViendo->caiAutorizacion->rango_inicial, 8, '0', STR_PAD_LEFT) }}
                        – {{ str_pad($facturaViendo->caiAutorizacion->rango_final, 8, '0', STR_PAD_LEFT) }}
                        &nbsp;·&nbsp;
                        Fecha límite: {{ $facturaViendo->caiAutorizacion->fecha_limite_emision->format('d/m/Y') }}
                    </p>
                    @endif
                </div>

            </div>{{-- fin scroll --}}
        </div>
    </div>
    @endif

    {{-- ══ MODAL ANULACIÓN ════════════════════════════════════════════ --}}
    @if($mostrarModalAnulacion)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40"
         x-data x-init="$el.querySelector('[data-focus]').focus()"
         wire:click.self="$set('mostrarModalAnulacion', false)">
        <div class="card w-full max-w-md shadow-xl">
            <div class="px-5 py-4 border-b border-primary-100/60 bg-primary-50/40">
                <h2 class="text-sm font-semibold text-gray-900">Anular factura</h2>
                <p class="text-xs font-mono text-gray-500 mt-0.5">{{ $facturaAnulandoNum }}</p>
            </div>
            <div class="p-5 space-y-4">
                <div class="flex items-start gap-2.5 bg-amber-50 border border-amber-200 px-3 py-2.5 text-xs text-amber-800">
                    <x-icon name="exclamation-triangle" class="w-4 h-4 text-amber-500 shrink-0 mt-0.5"/>
                    La anulación es irreversible. El número fiscal queda inutilizado (normativa SAR).
                </div>

                @if($errorAnulacion)
                <p class="text-sm text-red-600">{{ $errorAnulacion }}</p>
                @endif

                <div>
                    <label class="form-label">Motivo de anulación <span class="text-red-500">*</span></label>
                    <textarea wire:model="motivoAnulacion" rows="3" data-focus
                              placeholder="Describe el motivo…"
                              class="form-input resize-none"></textarea>
                </div>

                <div class="flex justify-end gap-3">
                    <button type="button" wire:click="$set('mostrarModalAnulacion', false)" class="btn-secondary">
                        Cancelar
                    </button>
                    <button wire:click="anular" wire:loading.attr="disabled" wire:target="anular" class="btn-danger">
                        <svg wire:loading wire:target="anular"
                             class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        Confirmar anulación
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
