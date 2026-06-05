{{-- Ctrl+Enter emite, Alt+L agrega línea --}}
<div
    x-data="{}"
    @keydown.ctrl.enter.window="$wire.emitir()"
    @keydown.alt.l.window.prevent="$wire.agregarLinea()">

{{-- ── POST-EMISIÓN ─────────────────────────────────────────── --}}
@if($facturaEmitida)
<div class="max-w-2xl mx-auto">
    <div class="card p-8 text-center space-y-1">
        <div class="w-14 h-14 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
            <x-icon name="check" class="w-7 h-7 text-green-600"/>
        </div>
        <h2 class="text-lg font-semibold text-gray-900">Factura emitida</h2>
        <p class="font-mono text-2xl font-bold text-primary-700 mt-1">{{ $facturaEmitida['numero'] }}</p>
        <p class="text-sm text-gray-500">{{ $facturaEmitida['cliente'] }} &mdash; {{ ucfirst($facturaEmitida['tipo_pago']) }}</p>

        <div class="border border-gray-100 rounded-lg p-4 mt-4 text-sm">
            <div class="grid grid-cols-2 gap-2 text-left">
                <span class="text-gray-500">Subtotal exento</span>
                <span class="text-right tabular-nums font-medium">L. {{ number_format($facturaEmitida['exento'], 2) }}</span>
                <span class="text-gray-500">Subtotal gravado</span>
                <span class="text-right tabular-nums font-medium">L. {{ number_format($facturaEmitida['gravado'], 2) }}</span>
                <span class="text-gray-500">ISV</span>
                <span class="text-right tabular-nums font-medium">L. {{ number_format($facturaEmitida['isv'], 2) }}</span>
                <span class="text-gray-900 font-semibold border-t border-gray-100 pt-2">Total</span>
                <span class="text-right tabular-nums font-bold text-primary-700 border-t border-gray-100 pt-2">L. {{ number_format($facturaEmitida['total'], 2) }}</span>
            </div>
        </div>

        <p class="font-mono text-xs text-gray-400 pt-2">{{ $facturaEmitida['cai'] }}</p>
        <p class="text-xs text-gray-400">{{ $facturaEmitida['fecha'] }}</p>

        <div class="flex justify-center gap-3 pt-4">
            <button wire:click="nuevaFactura" class="btn-primary">
                <x-icon name="plus" class="w-4 h-4"/>
                Nueva factura
            </button>
            <a href="{{ route('tenant.facturas', tenant('id')) }}" class="btn-secondary">
                Ver listado
            </a>
        </div>
    </div>
</div>

{{-- ── FORMULARIO ───────────────────────────────────────────── --}}
@else

{{-- Header + error: ancho completo, fuera del flex --}}
<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-xl font-semibold text-gray-900">Nueva factura</h1>
        <p class="text-sm text-gray-500 mt-0.5">
            <kbd class="px-1.5 py-0.5 bg-gray-100 rounded text-gray-500 font-mono text-xs">Ctrl+Enter</kbd> emite &nbsp;
            <kbd class="px-1.5 py-0.5 bg-gray-100 rounded text-gray-500 font-mono text-xs">Alt+L</kbd> agrega línea
        </p>
    </div>
</div>

@if($error)
<div class="flex items-start gap-2.5 rounded-md bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700 mb-5">
    <x-icon name="x-circle" class="w-4 h-4 shrink-0 mt-0.5 text-red-500"/>
    {{ $error }}
</div>
@endif

<div class="flex gap-6 items-start">

    {{-- Columna principal --}}
    <div class="flex-1 min-w-0 space-y-4">

        {{-- Cabecera: caja + cliente --}}
        <div class="card p-5 space-y-4">
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Datos de la factura</h2>

            <div class="grid grid-cols-3 gap-4">
                {{-- Caja --}}
                <div>
                    <label class="form-label">Caja / punto de emisión</label>
                    <select wire:model.live="puntoEmisionId" class="form-select">
                        <option value="">Seleccionar…</option>
                        @foreach($puntosEmision as $pe)
                            <option value="{{ $pe->id }}">{{ $pe->establecimiento->codigo ?? '?' }}-{{ $pe->codigo }} — {{ $pe->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Tipo documento --}}
                <div>
                    <label class="form-label">Tipo documento</label>
                    <select wire:model="tipoDocumento" class="form-select">
                        <option value="01">01 — Factura</option>
                        <option value="03">03 — Nota crédito</option>
                        <option value="04">04 — Nota débito</option>
                    </select>
                </div>

                {{-- Tipo pago --}}
                <div>
                    <label class="form-label">Tipo de pago</label>
                    <select wire:model="tipoPago" class="form-select">
                        <option value="contado">Contado</option>
                        <option value="credito">Crédito</option>
                    </select>
                </div>
            </div>

            {{-- Cliente con autocomplete --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="relative" x-data @click.outside="$wire.set('mostrarClienteDropdown', false)">
                    <label class="form-label">Cliente — buscar por nombre o RTN</label>
                    <input wire:model.live.debounce.250ms="clienteSearch"
                           type="text" placeholder="Nombre o RTN del cliente…"
                           class="form-input"
                           autocomplete="off">

                    {{-- Dropdown sugerencias --}}
                    @if($mostrarClienteDropdown && count($clientesSugeridos))
                    <div class="absolute left-0 right-0 top-full mt-1 z-30 bg-white border border-gray-200 rounded-md shadow-lg divide-y divide-gray-100">
                        @foreach($clientesSugeridos as $cli)
                        <button type="button" wire:click="seleccionarCliente({{ $cli['id'] }})"
                                class="w-full text-left px-4 py-2.5 text-sm hover:bg-gray-50 transition-colors">
                            <span class="font-medium text-gray-900">{{ $cli['nombre'] }}</span>
                            @if($cli['rtn'])
                            <span class="font-mono text-xs text-gray-400 ml-2">{{ $cli['rtn'] }}</span>
                            @endif
                        </button>
                        @endforeach
                    </div>
                    @endif
                </div>

                <div>
                    <label class="form-label">RTN</label>
                    <div class="flex gap-2">
                        <input wire:model="rtnCliente" type="text" maxlength="14"
                               placeholder="00000000000000"
                               class="form-input font-mono flex-1">
                        <button type="button" wire:click="consumidorFinal"
                                class="btn-secondary btn-sm shrink-0 text-xs">
                            Consumidor Final
                        </button>
                    </div>
                </div>
            </div>

            {{-- Nombre cliente (read-only si viene del autocomplete) --}}
            <div>
                <label class="form-label">Nombre del cliente</label>
                <input wire:model="nombreCliente" type="text"
                       class="form-input">
            </div>

            {{-- Campos SAR para ventas exentas / exoneradas (opcionales) --}}
            <div x-data="{ abierto: {{ ($ordenCompraExenta || $numConstanciaExonerado || $numRegistroSag) ? 'true' : 'false' }} }">
                <button type="button" @click="abierto = !abierto"
                        class="flex items-center gap-1.5 text-xs font-medium text-gray-400 hover:text-primary-600 transition-colors mb-2">
                    <svg :class="abierto ? 'rotate-90' : ''" class="w-3.5 h-3.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                    </svg>
                    Campos exentos / exonerados (opcional)
                </button>
                <div x-show="abierto" x-collapse class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="form-label">No. Orden de compra exenta</label>
                        <input wire:model="ordenCompraExenta" type="text" maxlength="60"
                               placeholder="Ej: OCE-2024-001"
                               class="form-input font-mono">
                    </div>
                    <div>
                        <label class="form-label">No. Constancia de Registro Exonerado</label>
                        <input wire:model="numConstanciaExonerado" type="text" maxlength="60"
                               placeholder="Ej: CRE-123456"
                               class="form-input font-mono">
                    </div>
                    <div>
                        <label class="form-label">No. Registro SAG</label>
                        <input wire:model="numRegistroSag" type="text" maxlength="60"
                               placeholder="Ej: SAG-2024-0001"
                               class="form-input font-mono">
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabla de líneas --}}
        <div class="card" padding="false">
            <div class="px-5 py-3 border-b border-primary-100/60 bg-primary-50/40 flex items-center justify-between">
                <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Detalle</h2>
                <button type="button" wire:click="agregarLinea" class="btn-ghost btn-sm text-primary-600">
                    <x-icon name="plus" class="w-3.5 h-3.5"/>
                    Agregar línea
                </button>
            </div>

            {{-- Cabecera de columnas --}}
            <div class="grid gap-2 px-4 py-2 bg-gray-50 border-b border-gray-100 text-xs font-semibold text-gray-500 uppercase tracking-wide"
                 style="grid-template-columns: 2fr 80px 80px 100px 80px 80px 80px 24px">
                <span>Descripción</span>
                <span>Cant.</span>
                <span>Unidad</span>
                <span>Precio unit.</span>
                <span>Descuento</span>
                <span>ISV</span>
                <span class="text-right">Subtotal</span>
                <span></span>
            </div>

            {{-- Líneas --}}
            @php
                $productosJson = $productos->map(fn($p) => [
                    'id' => $p->id, 'codigo' => $p->codigo, 'descripcion' => $p->descripcion,
                    'precio_unitario' => $p->precio_unitario, 'impuesto_id' => $p->impuesto_id,
                    'unidad_medida' => $p->unidad_medida, 'editable' => $p->precio_editable_en_emision,
                ])->values()->toJson();
                $impuestosJson = $impuestos->map(fn($i) => ['id' => $i->id, 'codigo' => $i->codigo, 'tasa' => $i->tasa])->values()->toJson();
            @endphp

            <div class="divide-y divide-gray-100">
                @foreach($lineas as $i => $linea)
                @php
                    $tasa = (float) ($impuestosIndex[(string)($linea['impuesto_id'] ?? '')] ['tasa'] ?? 0);
                    $cant = max(0.001, (float)($linea['cantidad'] ?? 1));
                    $prec = (float)($linea['precio_unitario'] ?? 0);
                    $desc = (float)($linea['descuento'] ?? 0);
                    $sub  = max(0, round($cant * $prec - $desc, 2));
                    $subtotalConIsv = round($sub * (1 + $tasa / 100), 2);
                @endphp
                <div class="grid gap-2 px-4 py-2.5 items-center"
                     style="grid-template-columns: 2fr 80px 80px 100px 80px 80px 80px 24px"
                     x-data="{
                        productos: {{ $productosJson }},
                        impuestos: {{ $impuestosJson }},
                        busq: '',
                        abierto: false,
                        get filtrados() {
                            if (!this.busq) return this.productos.slice(0, 8);
                            const b = this.busq.toLowerCase();
                            return this.productos.filter(p =>
                                p.descripcion.toLowerCase().includes(b) ||
                                (p.codigo && p.codigo.toLowerCase().includes(b))
                            ).slice(0, 8);
                        }
                     }">

                    {{-- Descripción + selector de producto --}}
                    <div class="relative">
                        <input wire:model="lineas.{{ $i }}.descripcion"
                               type="text" placeholder="Descripción del bien o servicio"
                               @focus="abierto = true"
                               @input="busq = $event.target.value; abierto = true"
                               @blur="setTimeout(() => abierto = false, 200)"
                               class="form-input text-xs w-full">
                        <div x-show="abierto && filtrados.length"
                             class="absolute left-0 top-full mt-0.5 z-20 bg-white border border-gray-200 rounded-md shadow-lg w-80 max-h-48 overflow-y-auto">
                            <template x-for="p in filtrados" :key="p.id">
                                <button type="button"
                                        @click="$wire.seleccionarProducto({{ $i }}, p.id); busq = p.descripcion; abierto = false"
                                        class="w-full text-left px-3 py-2 text-xs hover:bg-gray-50 flex items-center gap-2">
                                    <span class="font-mono text-gray-400 shrink-0 w-16 truncate" x-text="p.codigo"></span>
                                    <span class="text-gray-800 truncate" x-text="p.descripcion"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <input wire:model.live.debounce.300ms="lineas.{{ $i }}.cantidad"
                           type="number" step="0.001" min="0.001"
                           class="form-input text-xs text-right tabular-nums">

                    <input wire:model="lineas.{{ $i }}.unidad_medida"
                           type="text" class="form-input text-xs">

                    <input wire:model.live.debounce.300ms="lineas.{{ $i }}.precio_unitario"
                           type="number" step="0.01" min="0"
                           class="form-input text-xs text-right tabular-nums">

                    <input wire:model.live.debounce.300ms="lineas.{{ $i }}.descuento"
                           type="number" step="0.01" min="0"
                           class="form-input text-xs text-right tabular-nums">

                    <select wire:model.live="lineas.{{ $i }}.impuesto_id"
                            class="form-select text-xs">
                        @foreach($impuestos as $imp)
                            <option value="{{ $imp->id }}">{{ $imp->codigo }} {{ $imp->tasa }}%</option>
                        @endforeach
                    </select>

                    <span class="text-right tabular-nums text-sm font-medium text-gray-700">
                        L. {{ number_format($subtotalConIsv, 2) }}
                    </span>

                    <button type="button" wire:click="quitarLinea({{ $i }})"
                            @class(['invisible' => count($lineas) <= 1])
                            class="text-gray-300 hover:text-red-400 transition-colors">
                        <x-icon name="x-mark" class="w-4 h-4"/>
                    </button>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── PANEL LATERAL: totales + CAI ─────────────────── --}}
    <div class="w-72 shrink-0">
        <div class="sticky top-20 space-y-4">

            {{-- Totales --}}
            <div class="card p-5">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Resumen</h3>
                <dl class="space-y-1.5 text-sm">
                    <div class="flex justify-between text-gray-500">
                        <dt>Subtotal exento</dt>
                        <dd class="tabular-nums">L. {{ number_format($subtotalExento, 2) }}</dd>
                    </div>
                    <div class="flex justify-between text-gray-500">
                        <dt>Subtotal gravado</dt>
                        <dd class="tabular-nums">L. {{ number_format($subtotalGravado, 2) }}</dd>
                    </div>
                    <div class="flex justify-between text-gray-500">
                        <dt>ISV</dt>
                        <dd class="tabular-nums">L. {{ number_format($totalIsv, 2) }}</dd>
                    </div>
                    <div class="flex justify-between text-base font-bold text-gray-900 pt-2 border-t border-gray-100 mt-2">
                        <dt>Total</dt>
                        <dd class="tabular-nums text-primary-700">L. {{ number_format($total, 2) }}</dd>
                    </div>
                </dl>

                <button wire:click="emitir"
                        wire:loading.attr="disabled"
                        wire:target="emitir"
                        class="btn-primary w-full mt-4">
                    <svg wire:loading wire:target="emitir"
                         class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    <x-icon wire:loading.remove wire:target="emitir"
                            name="document-text" class="w-4 h-4 shrink-0"/>
                    Emitir factura
                </button>
            </div>

            {{-- Estado del CAI --}}
            <div class="card p-5">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">CAI activo</h3>
                @if($caiActivo)
                    <p class="font-mono text-xs text-gray-500 break-all leading-relaxed mb-3">{{ $caiActivo['cai'] }}</p>
                    <dl class="space-y-1.5 text-xs text-gray-600">
                        <div class="flex justify-between">
                            <dt class="text-gray-400">Siguiente correlativo</dt>
                            <dd class="font-mono font-semibold text-gray-800">{{ number_format($caiActivo['siguiente']) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-400">Disponibles</dt>
                            <dd class="tabular-nums">{{ number_format($caiActivo['disponibles']) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-400">Vence</dt>
                            <dd>{{ $caiActivo['fecha_limite'] }} <span class="text-gray-400">({{ $caiActivo['dias_restantes'] }}d)</span></dd>
                        </div>
                    </dl>
                    <div class="mt-3">
                        <div class="flex justify-between text-xs text-gray-400 mb-1">
                            <span>Uso</span>
                            <span class="tabular-nums">{{ $caiActivo['porcentaje'] }}%</span>
                        </div>
                        <div class="bg-gray-100 rounded-full h-1.5">
                            <div class="h-1.5 rounded-full {{ $caiActivo['porcentaje'] >= 80 ? 'bg-red-500' : 'bg-primary-500' }}"
                                 style="width: {{ min($caiActivo['porcentaje'], 100) }}%"></div>
                        </div>
                    </div>
                    <div class="mt-2 flex gap-1 flex-wrap">
                        @if($caiActivo['por_agotarse'])
                            <x-ui.badge color="orange">Por agotar</x-ui.badge>
                        @endif
                        @if($caiActivo['por_vencer'])
                            <x-ui.badge color="amber">Por vencer</x-ui.badge>
                        @endif
                    </div>
                @else
                    <div class="text-center py-4">
                        <x-icon name="exclamation-triangle" class="w-6 h-6 text-amber-400 mx-auto mb-2"/>
                        <p class="text-xs text-gray-500">No hay CAI vigente para esta caja.</p>
                        <a href="{{ route('tenant.cai', tenant('id')) }}"
                           class="text-xs text-primary-600 hover:underline mt-1 block">Registrar CAI →</a>
                    </div>
                @endif
            </div>

        </div>
    </div>

</div>
@endif
</div>
