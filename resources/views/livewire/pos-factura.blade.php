{{--
    POS — Terminal de punto de venta
    Layout: fullscreen, sin sidebar. Dos columnas: productos (izq) | carrito (der).
    Lector de código de barras: enfoca el input de búsqueda; el escáner escribe + Enter → buscarBarcode().
--}}
<div class="h-full flex flex-col"
     x-data="{}"
     @keydown.f2.window.prevent="$wire.abrirPago()"
     @keydown.escape.window="$wire.set('mostrarPago', false); $wire.set('mostrarClienteModal', false)">

{{-- ═══════════════════════════════ HEADER ═══════════════════════════════ --}}
<header class="h-12 shrink-0 bg-[#1b3a5c] flex items-center justify-between px-4 gap-4 z-20">

    {{-- Logo + nombre --}}
    <div class="flex items-center gap-2.5 shrink-0">
        <div class="w-7 h-7 rounded-lg bg-primary-600 flex items-center justify-center shadow">
            <x-icon name="clipboard-list" class="w-4 h-4 text-white"/>
        </div>
        <span class="text-white font-bold text-sm tracking-tight">
            Factunet <span class="text-primary-300 font-normal">POS</span>
        </span>
    </div>

    {{-- Estado CAI --}}
    <div class="flex-1 flex justify-center">
        @if($caiActivo)
            @if($caiActivo['por_agotarse'] || $caiActivo['por_vencer'])
                <span class="text-xs text-yellow-400 font-medium">⚠ CAI próximo a agotarse</span>
            @else
                <span class="text-xs text-[#6d90ab]">
                    Sig. correlativo: <strong class="text-[#8bafc8]">#{{ number_format($caiActivo['siguiente']) }}</strong>
                    &nbsp;·&nbsp;{{ number_format($caiActivo['disponibles']) }} disponibles
                </span>
            @endif
        @else
            <span class="text-xs text-red-400 font-semibold">⚠ Sin CAI vigente — configure una caja con CAI activo</span>
        @endif
    </div>

    {{-- Caja + usuario + modo tradicional --}}
    <div class="flex items-center gap-3 shrink-0">
        @livewire('caja-selector')
        <div class="hidden sm:flex items-center gap-1.5 text-xs text-[#8bafc8]">
            <x-icon name="user-circle" class="w-3.5 h-3.5"/>
            <span>{{ auth()->user()->name }}</span>
        </div>
        <a href="{{ route('tenant.factura.nueva', tenant('id')) }}" wire:navigate
           class="text-xs text-[#6d90ab] hover:text-white border border-[#2d5478] hover:border-[#4d6d87]
                  rounded px-2.5 py-1 transition-colors whitespace-nowrap">
            ← Modo tradicional
        </a>
    </div>
</header>

{{-- ═════════════════════════ PANTALLA POST-VENTA ═════════════════════════ --}}
@if($facturaEmitida)
<div class="flex-1 flex items-center justify-center bg-gray-100 p-6">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden">

        {{-- Cabecera verde --}}
        <div class="bg-green-500 px-6 py-5 text-center">
            <div class="w-14 h-14 rounded-full bg-white/20 flex items-center justify-center mx-auto mb-2">
                <svg class="w-8 h-8 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-white font-bold text-xl">¡Venta completada!</h2>
            <p class="text-green-100 text-sm mt-0.5">{{ $facturaEmitida['fecha'] }}</p>
        </div>

        {{-- Detalle del recibo --}}
        <div class="px-5 py-4 space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500">Factura</span>
                <span class="font-mono font-semibold text-gray-900 text-xs tracking-wide">{{ $facturaEmitida['numero'] }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-gray-500">Cliente</span>
                <span class="font-medium text-gray-900 text-right max-w-[180px] truncate">{{ $facturaEmitida['cliente'] }}</span>
            </div>
            @if($facturaEmitida['rtn'])
            <div class="flex justify-between">
                <span class="text-gray-500">RTN</span>
                <span class="font-mono text-xs text-gray-700">{{ $facturaEmitida['rtn'] }}</span>
            </div>
            @endif

            <div class="border-t border-gray-100 pt-2 mt-1 space-y-1">
                @if($facturaEmitida['exento'] > 0)
                <div class="flex justify-between text-gray-500">
                    <span>Exento</span>
                    <span class="tabular-nums">L. {{ number_format($facturaEmitida['exento'], 2) }}</span>
                </div>
                @endif
                @if($facturaEmitida['gravado'] > 0)
                <div class="flex justify-between text-gray-500">
                    <span>Gravado</span>
                    <span class="tabular-nums">L. {{ number_format($facturaEmitida['gravado'], 2) }}</span>
                </div>
                <div class="flex justify-between text-gray-500">
                    <span>ISV</span>
                    <span class="tabular-nums">L. {{ number_format($facturaEmitida['isv'], 2) }}</span>
                </div>
                @endif
            </div>

            <div class="flex justify-between items-baseline border-t border-gray-200 pt-2 mt-1">
                <span class="font-bold text-gray-900">TOTAL</span>
                <span class="text-2xl font-bold text-primary-700 tabular-nums">L. {{ number_format($facturaEmitida['total'], 2) }}</span>
            </div>

            @if($facturaEmitida['cambio'] > 0)
            <div class="flex justify-between items-center bg-green-50 border border-green-200 rounded-lg px-3 py-2">
                <span class="text-sm font-medium text-green-700">Cambio</span>
                <span class="text-lg font-bold text-green-700 tabular-nums">L. {{ number_format($facturaEmitida['cambio'], 2) }}</span>
            </div>
            @endif
        </div>

        {{-- Acciones --}}
        <div class="px-5 pb-5 grid grid-cols-2 gap-3">
            <a href="{{ route('tenant.factura.pdf', ['tenantId' => tenant('id'), 'id' => $facturaEmitida['id']]) }}"
               target="_blank"
               class="btn-secondary text-sm py-3 justify-center">
                <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/>
                </svg>
                Imprimir PDF
            </a>
            <button wire:click="nuevaVenta" class="btn-primary text-sm py-3">
                <svg wire:loading wire:target="nuevaVenta" class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Nueva venta
            </button>
        </div>
    </div>
</div>

{{-- ══════════════════════════════ POS PRINCIPAL ═══════════════════════════ --}}
@else
<div class="flex-1 flex overflow-hidden">

    {{-- ────────────────── PANEL IZQUIERDO — Productos (62%) ──────────────── --}}
    <div class="flex flex-col bg-white border-r border-gray-200 overflow-hidden" style="width:62%">

        {{-- Barra búsqueda / barcode --}}
        <div class="px-3 py-2.5 border-b border-gray-100 bg-white">
            <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803a7.5 7.5 0 0010.607 10.607z"/>
                </svg>
                <input wire:model.live.debounce.250ms="busqueda"
                       @keydown.enter.prevent="$wire.buscarBarcode()"
                       @focusBusqueda.window="$el.focus()"
                       type="text"
                       placeholder="Buscar producto o escanear código de barras…"
                       autocomplete="off" spellcheck="false"
                       autofocus
                       class="w-full pl-9 pr-9 py-2.5 text-sm border border-gray-200 rounded-lg bg-gray-50
                              focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 focus:bg-white
                              transition-colors"/>
                @if($busqueda)
                <button wire:click="$set('busqueda', '')"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
                @endif
            </div>
        </div>

        {{-- Grilla de productos --}}
        <div class="flex-1 overflow-y-auto p-3">
            @if($productos->isEmpty())
                <div class="flex flex-col items-center justify-center h-full gap-3 text-gray-300">
                    <svg class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>
                    </svg>
                    <p class="text-sm text-gray-400">
                        {{ $busqueda ? 'No se encontraron productos para "' . $busqueda . '"' : 'No hay productos activos' }}
                    </p>
                </div>
            @else
                <div class="grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(140px, 1fr))">
                    @foreach($productos as $producto)
                    <button wire:click="agregarProducto({{ $producto->id }})"
                            wire:loading.attr="disabled"
                            wire:target="agregarProducto({{ $producto->id }})"
                            class="group flex flex-col bg-white border border-gray-200 rounded-xl text-left overflow-hidden
                                   hover:border-primary-400 hover:shadow-sm
                                   active:scale-[.98] active:bg-primary-50
                                   transition-all duration-75 cursor-pointer
                                   focus:outline-none focus:ring-2 focus:ring-primary-500
                                   disabled:opacity-40 select-none">

                        {{-- Imagen o placeholder --}}
                        <div class="w-full aspect-square bg-gray-50 overflow-hidden shrink-0">
                            @if($producto->imagen && file_exists(public_path($producto->imagen)))
                                <img src="{{ asset($producto->imagen) }}"
                                     alt="{{ $producto->descripcion }}"
                                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-150">
                            @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <svg class="w-10 h-10 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                              d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9"/>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        {{-- Info --}}
                        <div class="flex flex-col gap-0.5 p-2.5 flex-1 w-full">
                            <span class="text-xs font-semibold text-gray-800 leading-snug line-clamp-2">
                                {{ $producto->descripcion }}
                            </span>
                            <span class="text-sm font-bold text-primary-700 tabular-nums mt-auto pt-1">
                                L.&nbsp;{{ number_format($producto->precio_unitario, 2) }}
                            </span>
                            @if($producto->codigo)
                            <span class="text-[10px] text-gray-400 font-mono truncate">{{ $producto->codigo }}</span>
                            @endif
                        </div>
                    </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Paginación --}}
        @if($totalPaginas > 1)
        <div class="border-t border-gray-100 px-3 py-2 flex items-center justify-between bg-white shrink-0">
            <button wire:click="paginaAnterior"
                    @class(['btn-ghost btn-sm gap-1', 'opacity-30 pointer-events-none' => $pagina === 0])>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                </svg>
                Anterior
            </button>
            <span class="text-xs text-gray-500">
                Página <strong>{{ $pagina + 1 }}</strong> de {{ $totalPaginas }}
                <span class="text-gray-400">&nbsp;({{ $totalProductos }} productos)</span>
            </span>
            <button wire:click="paginaSiguiente({{ $totalProductos }})"
                    @class(['btn-ghost btn-sm gap-1', 'opacity-30 pointer-events-none' => $pagina >= $totalPaginas - 1])>
                Siguiente
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </button>
        </div>
        @endif
    </div>

    {{-- ─────────────────── PANEL DERECHO — Carrito + Pago (38%) ─────────── --}}
    <div class="flex flex-col bg-gray-50 overflow-hidden" style="width:38%">

        {{-- Cliente --}}
        <div class="px-3 py-2 border-b border-gray-200 bg-white flex items-center justify-between gap-2 shrink-0">
            <div class="min-w-0 flex-1">
                <p class="text-[10px] uppercase tracking-widest text-gray-400 font-medium">Cliente</p>
                <p class="text-sm font-semibold text-gray-900 truncate leading-tight">{{ $nombreCliente }}</p>
                @if($rtnCliente)
                    <p class="text-[11px] text-gray-400 font-mono leading-none mt-0.5">RTN: {{ $rtnCliente }}</p>
                @endif
            </div>
            <div class="flex gap-1 shrink-0">
                @if($nombreCliente !== 'Consumidor Final')
                <button wire:click="consumidorFinal"
                        class="btn-ghost btn-sm text-xs text-gray-400 hover:text-gray-700">
                    CF
                </button>
                @endif
                <button wire:click="abrirClienteModal" class="btn-ghost btn-sm text-xs gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 15.803a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                    Buscar
                </button>
            </div>
        </div>

        {{-- Error inline --}}
        @if($error)
        <div class="mx-3 mt-2 shrink-0 flex items-start gap-2 rounded-lg bg-red-50 border border-red-200 px-3 py-2 text-xs text-red-700">
            <svg class="w-4 h-4 shrink-0 mt-0.5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                      d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
            </svg>
            {{ $error }}
        </div>
        @endif

        {{-- Líneas del carrito --}}
        <div class="flex-1 overflow-y-auto">
            @if(empty($carrito))
                <div class="flex flex-col items-center justify-center h-full gap-2 text-gray-300 select-none">
                    <svg class="w-14 h-14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.932-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/>
                    </svg>
                    <p class="text-sm text-gray-400">Carrito vacío</p>
                    <p class="text-xs text-gray-300 text-center px-4">Toca un producto para agregar<br>o escanea un código</p>
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($carrito as $i => $item)
                    @php
                        $tasa      = (float) ($item['tasa'] ?? 0);
                        $subtotal  = round($item['cantidad'] * $item['precio_unitario'] - ($item['descuento'] ?? 0), 2);
                        $isvLinea  = round($subtotal * $tasa / 100, 2);
                        $lineTotal = round($subtotal + $isvLinea, 2);
                    @endphp
                    <div class="px-3 py-2.5 bg-white hover:bg-gray-50 group transition-colors">
                        <div class="flex items-start gap-1.5">
                            <span class="text-xs font-semibold text-gray-800 leading-snug flex-1 min-w-0">
                                {{ $item['descripcion'] }}
                            </span>
                            <button wire:click="quitarLinea({{ $i }})"
                                    class="shrink-0 mt-0.5 text-gray-300 hover:text-red-500
                                           opacity-0 group-hover:opacity-100 transition-all">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                        <div class="flex items-center justify-between mt-2 gap-2">
                            {{-- Controles de cantidad --}}
                            <div class="flex items-center gap-1">
                                <button wire:click="cambiarCantidad({{ $i }}, -1)"
                                        class="w-7 h-7 rounded-lg border border-gray-200 flex items-center justify-center
                                               text-gray-500 hover:border-red-300 hover:bg-red-50 hover:text-red-600
                                               transition-colors active:scale-95">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/>
                                    </svg>
                                </button>
                                <input wire:change="setCantidad({{ $i }}, $event.target.value)"
                                       type="number" min="1" value="{{ $item['cantidad'] }}"
                                       class="w-10 h-7 text-center text-sm font-bold border border-gray-200 rounded-lg
                                              focus:outline-none focus:ring-1 focus:ring-primary-500 tabular-nums
                                              bg-white"/>
                                <button wire:click="cambiarCantidad({{ $i }}, 1)"
                                        class="w-7 h-7 rounded-lg border border-gray-200 flex items-center justify-center
                                               text-gray-500 hover:border-primary-400 hover:bg-primary-50 hover:text-primary-600
                                               transition-colors active:scale-95">
                                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                    </svg>
                                </button>
                            </div>
                            {{-- Precio --}}
                            <div class="text-right">
                                <div class="text-[10px] text-gray-400 tabular-nums">
                                    L.&nbsp;{{ number_format($item['precio_unitario'], 2) }}&nbsp;c/u
                                </div>
                                <div class="text-sm font-bold text-gray-900 tabular-nums">
                                    L.&nbsp;{{ number_format($lineTotal, 2) }}
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Totales + botón cobrar --}}
        <div class="border-t border-gray-200 bg-white shrink-0">
            {{-- Desglose --}}
            <div class="px-4 pt-3 pb-2 space-y-0.5">
                @if($subtotalExento > 0)
                <div class="flex justify-between text-xs text-gray-400">
                    <span>Exento</span>
                    <span class="tabular-nums">L. {{ number_format($subtotalExento, 2) }}</span>
                </div>
                @endif
                @if($subtotalGravado > 0)
                <div class="flex justify-between text-xs text-gray-400">
                    <span>Gravado</span>
                    <span class="tabular-nums">L. {{ number_format($subtotalGravado, 2) }}</span>
                </div>
                <div class="flex justify-between text-xs text-gray-400">
                    <span>ISV (15%)</span>
                    <span class="tabular-nums">L. {{ number_format($totalIsv, 2) }}</span>
                </div>
                @endif
                <div class="flex justify-between items-baseline pt-1.5 border-t border-gray-100 mt-1.5">
                    <span class="text-sm font-bold text-gray-700 uppercase tracking-wide">Total</span>
                    <span class="text-2xl font-extrabold text-gray-900 tabular-nums">
                        L. {{ number_format($total, 2) }}
                    </span>
                </div>
            </div>

            {{-- Botones de acción --}}
            <div class="px-3 pb-3 flex gap-2">
                <button wire:click="vaciarCarrito"
                        @class([
                            'btn-ghost btn-sm text-red-400 hover:text-red-600 hover:bg-red-50 gap-1',
                            'invisible pointer-events-none' => empty($carrito),
                        ])>
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                    </svg>
                    Vaciar
                </button>

                <button wire:click="abrirPago"
                        wire:loading.attr="disabled"
                        wire:target="abrirPago"
                        @disabled(empty($carrito) || ! $caiActivo)
                        @class([
                            'flex-1 py-3 rounded-xl font-extrabold text-base tracking-wide transition-all',
                            'bg-green-600 hover:bg-green-700 active:bg-green-800 text-white shadow-md hover:shadow-lg active:scale-[.98]'
                                => ! empty($carrito) && $caiActivo,
                            'bg-gray-200 text-gray-400 cursor-not-allowed'
                                => empty($carrito) || ! $caiActivo,
                        ])>
                    <svg wire:loading wire:target="abrirPago"
                         class="w-5 h-5 animate-spin mx-auto shrink-0" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <span wire:loading.remove wire:target="abrirPago">
                        @if(! $caiActivo)
                            SIN CAI
                        @elseif(empty($carrito))
                            COBRAR
                        @else
                            COBRAR — L. {{ number_format($total, 2) }}
                        @endif
                    </span>
                </button>
            </div>

            <p class="text-center text-[10px] text-gray-300 pb-2">F2 para cobrar rápido · ESC para cerrar modal</p>
        </div>
    </div>
</div>
@endif {{-- /POS principal --}}


{{-- ══════════════════════════ MODAL: PAGO ══════════════════════════════ --}}
@if($mostrarPago)
<div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
     x-data
     x-init="$nextTick(() => { $refs.montoPagado?.focus(); $refs.montoPagado?.select(); })">

    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xs overflow-hidden"
         @click.outside="$wire.set('mostrarPago', false)">

        {{-- Header --}}
        <div class="bg-[#1b3a5c] px-5 py-4 flex items-center justify-between">
            <div>
                <h2 class="text-white font-bold text-lg">Cobrar</h2>
                <p class="text-[#8bafc8] text-xs mt-0.5 truncate max-w-[200px]">{{ $nombreCliente }}</p>
            </div>
            <button wire:click="$set('mostrarPago', false)"
                    class="text-[#6d90ab] hover:text-white transition-colors rounded-lg p-1">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="p-5 space-y-4">

            {{-- Total --}}
            <div class="bg-gray-50 rounded-xl p-4 text-center">
                <p class="text-[10px] text-gray-400 uppercase tracking-widest mb-1">Total a cobrar</p>
                <p class="text-4xl font-extrabold text-gray-900 tabular-nums">
                    L. {{ number_format($total, 2) }}
                </p>
            </div>

            {{-- Tipo pago --}}
            <div class="grid grid-cols-2 gap-2">
                <button wire:click="$set('tipoPago', 'contado')"
                        @class([
                            'py-2.5 rounded-xl text-sm font-semibold border-2 transition-colors',
                            'border-primary-600 bg-primary-50 text-primary-700' => $tipoPago === 'contado',
                            'border-gray-200 text-gray-500 hover:border-gray-300' => $tipoPago !== 'contado',
                        ])>
                    Contado
                </button>
                <button wire:click="$set('tipoPago', 'credito')"
                        @class([
                            'py-2.5 rounded-xl text-sm font-semibold border-2 transition-colors',
                            'border-primary-600 bg-primary-50 text-primary-700' => $tipoPago === 'credito',
                            'border-gray-200 text-gray-500 hover:border-gray-300' => $tipoPago !== 'credito',
                        ])>
                    Crédito
                </button>
            </div>

            {{-- Monto recibido --}}
            <div>
                <label class="form-label text-xs">Monto recibido</label>
                <input wire:model.live="montoPagado"
                       x-ref="montoPagado"
                       type="number" step="0.01" min="0"
                       class="form-input text-2xl text-center font-bold tabular-nums py-3"/>
            </div>

            {{-- Montos rápidos --}}
            @php
                $t = $total;
                $rapidos = array_values(array_unique([
                    $t,
                    ceil($t / 20)  * 20,
                    ceil($t / 50)  * 50,
                    ceil($t / 100) * 100,
                ]));
                sort($rapidos);
                $rapidos = array_slice($rapidos, 0, 4);
            @endphp
            <div class="grid grid-cols-4 gap-1.5">
                @foreach($rapidos as $monto)
                <button wire:click="$set('montoPagado', '{{ number_format($monto, 2, '.', '') }}')"
                        @class([
                            'py-2 rounded-lg border text-xs font-semibold transition-colors tabular-nums',
                            'border-primary-500 bg-primary-50 text-primary-700' => (float)$montoPagado === (float)$monto,
                            'border-gray-200 text-gray-600 hover:border-primary-400 hover:bg-primary-50 hover:text-primary-700'
                                => (float)$montoPagado !== (float)$monto,
                        ])>
                    L.{{ $monto == floor($monto) ? number_format($monto, 0) : number_format($monto, 2) }}
                </button>
                @endforeach
            </div>

            {{-- Cambio --}}
            @php $cambio = max(0, round((float)$montoPagado - $total, 2)); @endphp
            <div @class([
                    'flex items-center justify-between rounded-xl px-4 py-3',
                    'bg-green-50 border border-green-200' => $cambio >= 0,
                    'bg-red-50 border border-red-200' => $cambio < 0,
                ])>
                <span @class([
                    'text-sm font-medium',
                    'text-green-700' => $cambio >= 0,
                    'text-red-600' => $cambio < 0,
                ])>Cambio</span>
                <span @class([
                    'text-2xl font-extrabold tabular-nums',
                    'text-green-700' => $cambio >= 0,
                    'text-red-600' => $cambio < 0,
                ])>L. {{ number_format($cambio, 2) }}</span>
            </div>

            {{-- Exoneración SAR (colapsable) --}}
            <div x-data="{ abierto: {{ ($ordenCompraExenta || $numConstanciaExonerado || $numRegistroSag) ? 'true' : 'false' }} }">
                <button type="button" @click="abierto = !abierto"
                        class="flex items-center gap-1.5 text-[11px] font-medium text-gray-400 hover:text-primary-600 transition-colors w-full text-left">
                    <svg :class="abierto ? 'rotate-90' : ''" class="w-3 h-3 transition-transform shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                    </svg>
                    Campos exentos / exonerados (SAR)
                </button>
                <div x-show="abierto" x-collapse class="mt-2 space-y-2">
                    <div>
                        <label class="form-label text-[10px]">No. Orden de compra exenta</label>
                        <input wire:model="ordenCompraExenta" type="text" maxlength="60"
                               placeholder="OCE-2024-001" class="form-input font-mono text-xs py-1.5"/>
                    </div>
                    <div>
                        <label class="form-label text-[10px]">No. Constancia Registro Exonerado</label>
                        <input wire:model="numConstanciaExonerado" type="text" maxlength="60"
                               placeholder="CRE-123456" class="form-input font-mono text-xs py-1.5"/>
                    </div>
                    <div>
                        <label class="form-label text-[10px]">No. Registro SAG</label>
                        <input wire:model="numRegistroSag" type="text" maxlength="60"
                               placeholder="SAG-2024-0001" class="form-input font-mono text-xs py-1.5"/>
                    </div>
                </div>
            </div>

            {{-- Error --}}
            @if($error)
            <p class="text-xs text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">{{ $error }}</p>
            @endif
        </div>

        {{-- Acciones --}}
        <div class="px-5 pb-5 flex gap-3">
            <button wire:click="$set('mostrarPago', false)"
                    class="btn-secondary px-5">
                Cancelar
            </button>
            <button wire:click="emitir"
                    wire:loading.attr="disabled"
                    wire:target="emitir"
                    class="flex-1 py-3 rounded-xl bg-green-600 hover:bg-green-700 active:bg-green-800
                           text-white font-extrabold text-base tracking-wide transition-colors
                           disabled:opacity-50 disabled:cursor-not-allowed">
                <svg wire:loading wire:target="emitir"
                     class="w-5 h-5 animate-spin mx-auto shrink-0" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                <span wire:loading.remove wire:target="emitir">EMITIR FACTURA</span>
            </button>
        </div>
    </div>
</div>
@endif

{{-- ════════════════════════ MODAL: BUSCAR CLIENTE ════════════════════════ --}}
@if($mostrarClienteModal)
<div class="fixed inset-0 z-50 flex items-start justify-center bg-black/60 pt-24 px-4"
     @click.self="$wire.set('mostrarClienteModal', false)">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden"
         x-data x-init="$nextTick(() => $el.querySelector('input[type=text]')?.focus())">

        <div class="flex items-center justify-between px-5 py-3.5 border-b border-gray-100">
            <h3 class="font-bold text-gray-900">Buscar cliente</h3>
            <button wire:click="$set('mostrarClienteModal', false)"
                    class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="p-4 space-y-2">
            <input wire:model.live.debounce.200ms="clienteSearch"
                   type="text" placeholder="Nombre o RTN…"
                   class="form-input w-full"/>

            {{-- Consumidor final --}}
            <button wire:click="consumidorFinal"
                    class="w-full text-left px-3 py-3 rounded-xl border border-dashed border-gray-300
                           text-sm text-gray-500 hover:border-primary-400 hover:bg-primary-50 hover:text-primary-700
                           transition-colors group">
                <span class="font-medium group-hover:font-semibold">Consumidor Final</span>
                <span class="text-xs text-gray-400 ml-2">sin RTN</span>
            </button>

            {{-- Resultados --}}
            @foreach($clientesSugeridos as $c)
            <button wire:click="seleccionarCliente({{ $c['id'] }})"
                    class="w-full text-left px-3 py-3 rounded-xl border border-gray-200
                           hover:border-primary-400 hover:bg-primary-50 transition-colors group">
                <p class="text-sm font-semibold text-gray-900 group-hover:text-primary-700">{{ $c['nombre'] }}</p>
                @if($c['rtn'])
                <p class="text-xs text-gray-400 font-mono mt-0.5">RTN: {{ $c['rtn'] }}</p>
                @endif
            </button>
            @endforeach

            @if(empty($clientesSugeridos) && strlen($clienteSearch) >= 2)
            <p class="text-center text-sm text-gray-400 py-3">No se encontraron clientes</p>
            @endif
        </div>
    </div>
</div>
@endif

</div>
