<div class="space-y-5">

    {{-- ══ HEADER ════════════════════════════════════════════════════════ --}}
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-base font-bold text-gray-900">Reportes</h1>
            <p class="text-xs text-gray-500 mt-0.5">Libro de Ventas, Resumen ISV y Anuladas · conforme SAR Honduras</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('tenant.reportes.pdf', $tid) }}?{{ $exportParams }}"
               target="_blank"
               class="btn-secondary btn-sm">
                <x-icon name="printer" class="w-3.5 h-3.5 shrink-0"/>
                Generar PDF
            </a>
            <a href="{{ route('tenant.reportes.csv', $tid) }}?{{ $exportParams }}"
               class="btn-secondary btn-sm">
                <x-icon name="arrow-down-tray" class="w-3.5 h-3.5 shrink-0"/>
                Exportar CSV
            </a>
        </div>
    </div>

    {{-- ══ FILTROS ════════════════════════════════════════════════════════ --}}
    <div class="card">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div>
                <label class="form-label">Fecha desde</label>
                <input wire:model.blur="fechaDesde" type="date" class="form-input"/>
            </div>
            <div>
                <label class="form-label">Fecha hasta</label>
                <input wire:model.blur="fechaHasta" type="date" class="form-input"/>
            </div>
            <div>
                <label class="form-label">Establecimiento</label>
                <select wire:model.live="establecimientoId" class="form-select">
                    <option value="">Todos</option>
                    @foreach($establecimientos as $est)
                    <option value="{{ $est->id }}">{{ $est->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <button wire:click="$refresh" class="btn-primary w-full">
                    <x-icon name="magnifying-glass" class="w-3.5 h-3.5 shrink-0"/>
                    Consultar
                </button>
            </div>
        </div>
    </div>

    {{-- ══ TABS TIPO REPORTE ══════════════════════════════════════════════ --}}
    <div class="flex gap-1 border-b border-gray-200">
        @foreach(['libro_ventas' => 'Libro de Ventas', 'isv' => 'Resumen ISV', 'anuladas' => 'Facturas Anuladas'] as $tipo => $label)
        <button wire:click="$set('tipoReporte', '{{ $tipo }}')"
                @class([
                    'px-4 py-2 text-xs font-semibold border-b-2 -mb-px transition-colors',
                    'border-primary-600 text-primary-700' => $tipoReporte === $tipo,
                    'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' => $tipoReporte !== $tipo,
                ])>
            {{ $label }}
        </button>
        @endforeach
    </div>

    {{-- ══ TABLA PREVIEW ══════════════════════════════════════════════════ --}}
    <div class="card" padding="false">

        {{-- Info barra --}}
        <div class="px-4 py-2.5 bg-primary-50/40 border-b border-primary-100/60 flex items-center justify-between">
            <span class="text-xs text-gray-500">
                @if($tipoReporte === 'libro_ventas') Libro de Ventas
                @elseif($tipoReporte === 'isv') Resumen ISV — para declaración ISV-103
                @else Facturas Anuladas
                @endif
                &nbsp;·&nbsp;
                <span class="font-semibold text-gray-700">{{ $totalRegistros }}</span> registro{{ $totalRegistros !== 1 ? 's' : '' }}
                @if($totalRegistros > 150)
                  &nbsp;<span class="text-amber-600">(vista previa: 150 de {{ $totalRegistros }})</span>
                @endif
            </span>
            <span class="text-[10px] font-mono text-gray-400">
                {{ $fechaDesde ? \Carbon\Carbon::parse($fechaDesde)->format('d/m/Y') : '—' }}
                al
                {{ $fechaHasta ? \Carbon\Carbon::parse($fechaHasta)->format('d/m/Y') : '—' }}
            </span>
        </div>

        @if($facturas->isEmpty())
        <x-ui.empty-state icon="document-text" title="Sin resultados"
            description="No hay facturas para el período y filtros seleccionados."/>
        @else

        <div class="overflow-x-auto">

        {{-- ─ LIBRO DE VENTAS / ISV ─ --}}
        @if($tipoReporte !== 'anuladas')
        <table class="min-w-full text-xs">
            <thead class="bg-[#1b3a5c]">
                <tr>
                    <th class="px-3 py-2.5 text-left text-white/80 font-semibold uppercase tracking-wide whitespace-nowrap">No. Factura</th>
                    <th class="px-3 py-2.5 text-left text-white/80 font-semibold uppercase tracking-wide whitespace-nowrap">Fecha</th>
                    <th class="px-3 py-2.5 text-left text-white/80 font-semibold uppercase tracking-wide whitespace-nowrap">RTN</th>
                    <th class="px-3 py-2.5 text-left text-white/80 font-semibold uppercase tracking-wide">Cliente</th>
                    <th class="px-3 py-2.5 text-right text-white/80 font-semibold uppercase tracking-wide whitespace-nowrap">Exento</th>
                    <th class="px-3 py-2.5 text-right text-white/80 font-semibold uppercase tracking-wide whitespace-nowrap">Exonerado</th>
                    <th class="px-3 py-2.5 text-right text-white/80 font-semibold uppercase tracking-wide whitespace-nowrap">Grav. 15%</th>
                    <th class="px-3 py-2.5 text-right text-white/80 font-semibold uppercase tracking-wide whitespace-nowrap">ISV 15%</th>
                    <th class="px-3 py-2.5 text-right text-white/80 font-semibold uppercase tracking-wide whitespace-nowrap">Grav. 18%</th>
                    <th class="px-3 py-2.5 text-right text-white/80 font-semibold uppercase tracking-wide whitespace-nowrap">ISV 18%</th>
                    <th class="px-3 py-2.5 text-right text-white/80 font-semibold uppercase tracking-wide whitespace-nowrap">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($facturas as $f)
                <tr class="hover:bg-primary-50/30">
                    <td class="px-3 py-2 font-mono text-primary-700 whitespace-nowrap">{{ $f->numero_completo }}</td>
                    <td class="px-3 py-2 text-gray-600 whitespace-nowrap">{{ $f->fecha_emision->format('d/m/Y') }}</td>
                    <td class="px-3 py-2 font-mono text-gray-500 whitespace-nowrap">{{ $f->rtn_cliente ?? 'CF' }}</td>
                    <td class="px-3 py-2 text-gray-800 max-w-[200px] truncate">{{ $f->nombre_cliente }}</td>
                    <td class="px-3 py-2 text-right tabular-nums text-gray-600">
                        @if($f->_exento_puro > 0) L.{{ number_format($f->_exento_puro, 2) }} @else <span class="text-gray-300">—</span> @endif
                    </td>
                    <td class="px-3 py-2 text-right tabular-nums text-gray-600">
                        @if($f->_exonerado > 0) L.{{ number_format($f->_exonerado, 2) }} @else <span class="text-gray-300">—</span> @endif
                    </td>
                    <td class="px-3 py-2 text-right tabular-nums text-gray-600">
                        @if($f->_gravado15 > 0) L.{{ number_format($f->_gravado15, 2) }} @else <span class="text-gray-300">—</span> @endif
                    </td>
                    <td class="px-3 py-2 text-right tabular-nums text-gray-600">
                        @if($f->_isv15 > 0) L.{{ number_format($f->_isv15, 2) }} @else <span class="text-gray-300">—</span> @endif
                    </td>
                    <td class="px-3 py-2 text-right tabular-nums text-gray-600">
                        @if($f->_gravado18 > 0) L.{{ number_format($f->_gravado18, 2) }} @else <span class="text-gray-300">—</span> @endif
                    </td>
                    <td class="px-3 py-2 text-right tabular-nums text-gray-600">
                        @if($f->_isv18 > 0) L.{{ number_format($f->_isv18, 2) }} @else <span class="text-gray-300">—</span> @endif
                    </td>
                    <td class="px-3 py-2 text-right tabular-nums font-semibold text-gray-900">L.{{ number_format($f->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            {{-- Fila de totales --}}
            <tfoot>
                <tr class="bg-[#1b3a5c]/90 text-white text-xs font-bold">
                    <td colspan="4" class="px-3 py-2.5 text-right uppercase tracking-wide">Totales del período</td>
                    <td class="px-3 py-2.5 text-right tabular-nums">L.{{ number_format($totales['exento'], 2) }}</td>
                    <td class="px-3 py-2.5 text-right tabular-nums">L.{{ number_format($totales['exonerado'], 2) }}</td>
                    <td class="px-3 py-2.5 text-right tabular-nums">L.{{ number_format($totales['gravado15'], 2) }}</td>
                    <td class="px-3 py-2.5 text-right tabular-nums">L.{{ number_format($totales['isv15'], 2) }}</td>
                    <td class="px-3 py-2.5 text-right tabular-nums">L.{{ number_format($totales['gravado18'], 2) }}</td>
                    <td class="px-3 py-2.5 text-right tabular-nums">L.{{ number_format($totales['isv18'], 2) }}</td>
                    <td class="px-3 py-2.5 text-right tabular-nums">L.{{ number_format($totales['total'], 2) }}</td>
                </tr>
            </tfoot>
        </table>

        {{-- Resumen ISV debajo cuando es tab ISV --}}
        @if($tipoReporte === 'isv')
        <div class="p-5 border-t border-gray-100 bg-gray-50/60">
            <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wide mb-3">Resumen para declaración ISV-103</p>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                <div class="bg-white border border-gray-200 rounded-lg p-3">
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide">Casilla 4020 · Ventas Exentas</p>
                    <p class="text-base font-bold text-gray-900 tabular-nums mt-1">L. {{ number_format($totales['exento'], 2) }}</p>
                </div>
                <div class="bg-white border border-gray-200 rounded-lg p-3">
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide">Casilla 4030 · Ventas Exoneradas</p>
                    <p class="text-base font-bold text-gray-900 tabular-nums mt-1">L. {{ number_format($totales['exonerado'], 2) }}</p>
                </div>
                <div class="bg-white border border-blue-200 rounded-lg p-3">
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide">Casilla 4000 · Ventas Gravadas 15%</p>
                    <p class="text-base font-bold text-gray-900 tabular-nums mt-1">L. {{ number_format($totales['gravado15'], 2) }}</p>
                    <p class="text-[10px] text-primary-600 mt-0.5">ISV 15% (C.4001): L. {{ number_format($totales['isv15'], 2) }}</p>
                </div>
                <div class="bg-white border border-amber-200 rounded-lg p-3">
                    <p class="text-[10px] text-gray-400 uppercase tracking-wide">Casilla 4010 · Ventas Gravadas 18%</p>
                    <p class="text-base font-bold text-gray-900 tabular-nums mt-1">L. {{ number_format($totales['gravado18'], 2) }}</p>
                    <p class="text-[10px] text-amber-600 mt-0.5">ISV 18% (C.4011): L. {{ number_format($totales['isv18'], 2) }}</p>
                </div>
            </div>
            <div class="mt-3 flex items-center justify-between bg-[#1b3a5c] text-white rounded-lg px-4 py-2.5">
                <span class="text-xs font-semibold uppercase tracking-wide">Casilla 4040 · Total ventas del período</span>
                <span class="text-lg font-extrabold tabular-nums">L. {{ number_format($totales['total'], 2) }}</span>
            </div>
        </div>
        @endif

        @else
        {{-- ─ ANULADAS ─ --}}
        <table class="min-w-full text-xs">
            <thead class="bg-red-700">
                <tr>
                    <th class="px-3 py-2.5 text-left text-white/90 font-semibold uppercase tracking-wide whitespace-nowrap">No. Factura</th>
                    <th class="px-3 py-2.5 text-left text-white/90 font-semibold uppercase tracking-wide whitespace-nowrap">Fecha emisión</th>
                    <th class="px-3 py-2.5 text-left text-white/90 font-semibold uppercase tracking-wide whitespace-nowrap">Fecha anulación</th>
                    <th class="px-3 py-2.5 text-left text-white/90 font-semibold uppercase tracking-wide whitespace-nowrap">RTN</th>
                    <th class="px-3 py-2.5 text-left text-white/90 font-semibold uppercase tracking-wide">Cliente</th>
                    <th class="px-3 py-2.5 text-left text-white/90 font-semibold uppercase tracking-wide">Motivo anulación</th>
                    <th class="px-3 py-2.5 text-right text-white/90 font-semibold uppercase tracking-wide whitespace-nowrap">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($facturas as $f)
                <tr class="hover:bg-red-50/40">
                    <td class="px-3 py-2 font-mono text-red-700 whitespace-nowrap">{{ $f->numero_completo }}</td>
                    <td class="px-3 py-2 text-gray-600 whitespace-nowrap">{{ $f->fecha_emision->format('d/m/Y') }}</td>
                    <td class="px-3 py-2 text-gray-500 whitespace-nowrap">{{ $f->anulada_at?->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="px-3 py-2 font-mono text-gray-500 whitespace-nowrap">{{ $f->rtn_cliente ?? 'CF' }}</td>
                    <td class="px-3 py-2 text-gray-800 max-w-[180px] truncate">{{ $f->nombre_cliente }}</td>
                    <td class="px-3 py-2 text-gray-600 max-w-[220px]">{{ $f->motivo_anulacion ?? '—' }}</td>
                    <td class="px-3 py-2 text-right tabular-nums text-gray-500 line-through">L.{{ number_format($f->total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="bg-red-700/90 text-white text-xs font-bold">
                    <td colspan="6" class="px-3 py-2.5 text-right uppercase tracking-wide">Total anulado</td>
                    <td class="px-3 py-2.5 text-right tabular-nums line-through">L.{{ number_format($totales['total'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
        @endif

        </div>
        @endif
    </div>

    {{-- Nota legal --}}
    <p class="text-[10px] text-gray-400">
        Conforme al Artículo 41 de la Ley del ISV y Artículo 9 de su Reglamento. El Libro de Ventas debe conservarse por 5 años (Art. 116 Código Tributario). Los datos de las casillas ISV-103 corresponden a las ventas del período seleccionado.
    </p>

</div>
