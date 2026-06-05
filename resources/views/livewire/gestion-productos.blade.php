<div class="space-y-5">

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Productos</h1>
            <p class="text-sm text-gray-500 mt-0.5">Catálogo de bienes y servicios</p>
        </div>
        <button wire:click="abrirCrear" class="btn-primary">
            <x-icon name="plus" class="w-4 h-4"/>
            Nuevo producto
        </button>
    </div>

    {{-- Filtros --}}
    <div class="card p-4">
        <div class="flex flex-wrap gap-3 items-center">
            <div class="relative flex-1 min-w-[220px] max-w-sm">
                <x-icon name="magnifying-glass" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"/>
                <input wire:model.live.debounce.300ms="busqueda"
                       type="text" placeholder="Código, descripción o código de barras…"
                       class="form-input pl-9">
            </div>
            <select wire:model.live="filtroTipo" class="form-select w-auto">
                <option value="">Todos los tipos</option>
                <option value="bien">Bienes</option>
                <option value="servicio">Servicios</option>
            </select>
            <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-600 select-none">
                <input wire:model.live="soloActivos" type="checkbox"
                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                Solo activos
            </label>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card">
        @if($productos->isEmpty())
            <x-ui.empty-state icon="cube" title="Sin productos"
                description="Crea el primer producto o servicio del catálogo."
                cta-label="Nuevo producto" cta-action="abrirCrear"/>
        @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-primary-600">
                    <tr>
                        <th class="w-12 pl-4"></th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Código</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Descripción</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide hidden sm:table-cell">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide hidden md:table-cell">ISV</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold text-white/90 uppercase tracking-wide">Precio</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Estado</th>
                        <th class="px-4 py-3 w-14"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($productos as $p)
                    <tr class="hover:bg-primary-50/60 transition-colors {{ !$p->activo ? 'opacity-60' : '' }}">
                        <td class="pl-4 py-2.5 w-12">
                            @if($p->imagen && file_exists(public_path($p->imagen)))
                                <img src="/{{ $p->imagen }}" alt="{{ $p->descripcion }}"
                                     class="w-9 h-9 rounded object-contain bg-gray-50 border border-gray-100">
                            @else
                                <div class="w-9 h-9 rounded bg-gray-100 border border-gray-100 flex items-center justify-center">
                                    <x-icon name="cube" class="w-4 h-4 text-gray-300"/>
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-2.5">
                            <span class="font-mono text-xs font-semibold text-primary-700">{{ $p->codigo }}</span>
                            @if($p->codigo_barras)
                            <span class="block font-mono text-[10px] text-gray-400">{{ $p->codigo_barras }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5">
                            <p class="text-gray-900 font-medium">{{ $p->descripcion }}</p>
                            @if($p->categoria)
                            <p class="text-xs text-gray-400">{{ $p->categoria }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 hidden sm:table-cell">
                            <x-ui.badge :color="$p->tipo === 'servicio' ? 'blue' : 'gray'">
                                {{ $p->tipo }}
                            </x-ui.badge>
                        </td>
                        <td class="px-4 py-2.5 hidden md:table-cell">
                            @if($p->impuesto)
                                <span class="text-xs text-gray-600 font-mono">{{ $p->impuesto->codigo }} {{ $p->impuesto->tasa }}%</span>
                            @else
                                <span class="text-xs text-gray-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-gray-900 whitespace-nowrap">
                            L. {{ number_format((float) $p->precio_unitario, 2) }}
                        </td>
                        <td class="px-4 py-2.5">
                            <button wire:click="toggleActivo({{ $p->id }})" wire:loading.attr="disabled">
                                <x-ui.badge :color="$p->activo ? 'green' : 'gray'">
                                    {{ $p->activo ? 'Activo' : 'Inactivo' }}
                                </x-ui.badge>
                            </button>
                        </td>
                        <td class="px-4 py-2.5 text-right">
                            <button wire:click="abrirEditar({{ $p->id }})" class="btn-ghost btn-sm">
                                <x-icon name="pencil" class="w-3.5 h-3.5"/>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($productos->hasPages())
        <div class="px-5 py-3 border-t border-gray-100 flex items-center justify-between text-sm text-gray-500">
            <span class="text-xs">Mostrando {{ $productos->firstItem() }}–{{ $productos->lastItem() }} de {{ $productos->total() }}</span>
            {{ $productos->links() }}
        </div>
        @else
        <div class="px-5 py-3 border-t border-gray-100 text-xs text-gray-400">
            {{ $productos->total() }} producto(s)
        </div>
        @endif
        @endif
    </div>

    {{-- ═══ MODAL ════════════════════════════════════════════════════
         x-data centraliza tipo y tab.
         - tipo usa @entangle: el toggle es instantáneo en Alpine y se sincroniza
           con Livewire en background (sin round-trip bloqueante para la UI).
         - impuestosFiltrados filtra client-side según tipo, sin re-render.
         - Backdrop cierra el modal; @click.stop en la card evita propagación.
     ──────────────────────────────────────────────────────────────────── --}}
    @if($mostrarModal)
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-data="{
             tab:  $wire.entangle('tabActiva').live,
             tipo: $wire.entangle('tipo').live,
             impuestos: {{ $impuestos->map(fn($i) => ['id' => $i->id, 'codigo' => $i->codigo, 'tasa' => (float)$i->tasa])->toJson() }},
             get impuestosFiltrados() {
                 return this.tipo === 'servicio'
                     ? this.impuestos.filter(i => i.tasa <= 15)
                     : this.impuestos;
             }
         }"
         x-init="$nextTick(() => $el.querySelector('[data-focus]')?.focus())">

        {{-- Backdrop: clic cierra el modal de forma confiable --}}
        <div class="absolute inset-0 bg-black/40" @click="$wire.set('mostrarModal', false)"></div>

        {{-- Card: @click.stop evita que el clic en la card llegue al backdrop --}}
        <div class="relative card w-full max-w-2xl shadow-xl flex flex-col"
             style="max-height: min(92vh, 760px)"
             @click.stop>

            {{-- Cabecera --}}
            <div class="px-5 py-3.5 border-b border-gray-100 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-3">
                    <h2 class="text-sm font-semibold text-gray-900">
                        {{ $editandoId ? 'Editar producto' : 'Nuevo producto' }}
                    </h2>
                    {{-- Badge reactivo sin re-render de Livewire --}}
                    <span x-show="tipo === 'bien'"
                          class="badge bg-gray-100 text-gray-700">Bien</span>
                    <span x-show="tipo === 'servicio'"
                          class="badge bg-blue-100 text-blue-800">Servicio</span>
                </div>
                <button wire:click="$set('mostrarModal', false)"
                        class="text-gray-400 hover:text-gray-600 transition-colors p-1 -mr-1 rounded">
                    <x-icon name="x-mark" class="w-4 h-4"/>
                </button>
            </div>

            {{-- Tabs --}}
            <div class="flex border-b border-gray-100 shrink-0 px-5">
                <button type="button" @click="tab = 'principal'"
                        :class="tab === 'principal'
                            ? 'border-primary-600 text-primary-700 font-medium'
                            : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="py-2.5 px-1 mr-5 text-sm border-b-2 transition-colors">
                    Principal
                    @if($errors->hasAny(['codigo','descripcion','precioUnitario','unidadMedida','tipo','impuestoId','imagenTemporal']))
                        <span class="ml-1.5 inline-block w-1.5 h-1.5 rounded-full bg-red-500 align-middle"></span>
                    @endif
                </button>
                <button type="button" @click="tab = 'avanzado'"
                        :class="tab === 'avanzado'
                            ? 'border-primary-600 text-primary-700 font-medium'
                            : 'border-transparent text-gray-500 hover:text-gray-700'"
                        class="py-2.5 px-1 text-sm border-b-2 transition-colors">
                    Avanzado
                    @if($errors->hasAny(['descripcionLarga','precioCompra','notas']))
                        <span class="ml-1.5 inline-block w-1.5 h-1.5 rounded-full bg-red-500 align-middle"></span>
                    @endif
                </button>
            </div>

            {{-- Cuerpo --}}
            <form wire:submit="guardar" class="overflow-y-auto flex-1 min-h-0">

                {{-- ══ TAB PRINCIPAL ══════════════════════════════ --}}
                <div x-show="tab === 'principal'" class="p-5 space-y-4">

                    {{-- Toggle tipo — Alpine puro, sin round-trip al servidor --}}
                    <div class="flex items-center gap-3">
                        <span class="text-xs font-medium text-gray-500 shrink-0">Tipo:</span>
                        <div class="flex rounded-lg border border-gray-200 overflow-hidden">
                            <button type="button"
                                    @click="tipo = 'bien'"
                                    :class="tipo === 'bien'
                                        ? 'bg-primary-600 text-white'
                                        : 'bg-white text-gray-500 hover:bg-gray-50'"
                                    class="px-4 py-1.5 text-sm font-medium transition-colors">
                                Bien
                            </button>
                            <button type="button"
                                    @click="tipo = 'servicio'"
                                    :class="tipo === 'servicio'
                                        ? 'bg-primary-600 text-white'
                                        : 'bg-white text-gray-500 hover:bg-gray-50'"
                                    class="px-4 py-1.5 text-sm font-medium transition-colors border-l border-gray-200">
                                Servicio
                            </button>
                        </div>
                    </div>

                    {{-- Imagen + código --}}
                    <div class="flex gap-4 items-start">

                        {{-- Thumbnail / upload --}}
                        <div class="shrink-0 flex flex-col items-center gap-2">
                            <div class="w-16 h-16 rounded-lg border border-gray-200 bg-gray-50 overflow-hidden flex items-center justify-center">
                                @if($imagenTemporal)
                                    <img src="{{ $imagenTemporal->temporaryUrl() }}" class="w-full h-full object-contain">
                                @elseif($imagenActual && !$quitarImagen && file_exists(public_path($imagenActual)))
                                    <img src="/{{ $imagenActual }}" class="w-full h-full object-contain">
                                @else
                                    <x-icon name="{{ $tipo === 'servicio' ? 'tag' : 'cube' }}" class="w-7 h-7 text-gray-200"/>
                                @endif
                            </div>
                            <label class="btn-secondary btn-sm cursor-pointer text-xs px-2 py-1">
                                {{ ($imagenActual && !$quitarImagen) || $imagenTemporal ? 'Cambiar' : 'Imagen' }}
                                <input type="file" wire:model="imagenTemporal"
                                       accept="image/jpeg,image/png,image/webp,image/gif"
                                       class="hidden">
                            </label>
                            @if($imagenTemporal || ($imagenActual && !$quitarImagen))
                            <button type="button" wire:click="eliminarImagen"
                                    class="text-[11px] text-red-400 hover:text-red-600 transition-colors">
                                Quitar
                            </button>
                            @endif
                            <div wire:loading wire:target="imagenTemporal"
                                 class="text-[10px] text-primary-600 flex items-center gap-1">
                                <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                </svg>
                                Subiendo
                            </div>
                            @error('imagenTemporal')
                                <p class="text-[10px] text-red-600 text-center max-w-[72px] leading-tight">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Código + barras (solo bienes) + descripción --}}
                        <div class="flex-1 min-w-0 space-y-3">
                            <div class="grid gap-3"
                                 :style="tipo === 'bien' ? 'grid-template-columns:1fr 1fr' : 'grid-template-columns:1fr'">
                                <div>
                                    <label class="form-label">SKU <span class="text-red-500">*</span></label>
                                    <input wire:model="codigo" data-focus type="text" maxlength="50"
                                           :placeholder="tipo === 'bien' ? 'B001' : 'S001'"
                                           class="form-input font-mono @error('codigo') error @enderror">
                                    @error('codigo') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                                {{-- Código de barras: solo bienes --}}
                                <div x-show="tipo === 'bien'" x-cloak>
                                    <label class="form-label">Cód. barras</label>
                                    <input wire:model="codigoBarras" type="text" maxlength="30"
                                           class="form-input font-mono">
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Descripción <span class="text-red-500">*</span></label>
                                <input wire:model="descripcion" type="text" maxlength="255"
                                       :placeholder="tipo === 'bien' ? 'Ej: Teclado mecánico USB' : 'Ej: Consultoría técnica por hora'"
                                       class="form-input @error('descripcion') error @enderror">
                                @error('descripcion') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Precio + unidad + ISV --}}
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="form-label">Precio venta <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">L.</span>
                                <input wire:model="precioUnitario" type="number" step="0.01" min="0"
                                       class="form-input pl-7 text-right tabular-nums @error('precioUnitario') error @enderror">
                            </div>
                            @error('precioUnitario') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="form-label">Unidad <span class="text-red-500">*</span></label>
                            <input wire:model="unidadMedida" type="text" maxlength="20"
                                   :placeholder="tipo === 'bien' ? 'unidad, kg, litro…' : 'hora, mes, proyecto…'"
                                   class="form-input @error('unidadMedida') error @enderror">
                        </div>
                        <div>
                            <label class="form-label">ISV</label>
                            {{-- x-for filtra client-side: servicios no pueden tener >15% (ley hondureña ISV) --}}
                            <select wire:model="impuestoId" class="form-select">
                                <option value="">Exento (0%)</option>
                                <template x-for="imp in impuestosFiltrados" :key="imp.id">
                                    <option :value="imp.id" x-text="imp.codigo + ' ' + imp.tasa + '%'"></option>
                                </template>
                            </select>
                            <p x-show="tipo === 'servicio'" class="form-hint">
                                Servicios: máx. ISV 15% (ley hondureña).
                            </p>
                        </div>
                    </div>

                    {{-- Categoría + marca (marca solo bienes) --}}
                    <div class="grid gap-3"
                         :style="tipo === 'bien' ? 'grid-template-columns:1fr 1fr' : 'grid-template-columns:1fr'">
                        <div>
                            <label class="form-label">Categoría</label>
                            <input wire:model="categoria" type="text"
                                   :placeholder="tipo === 'bien' ? 'Electrónica, Alimentos…' : 'Consultoría, Soporte…'"
                                   class="form-input">
                        </div>
                        <div x-show="tipo === 'bien'" x-cloak>
                            <label class="form-label">Marca</label>
                            <input wire:model="marca" type="text" placeholder="Samsung, Nestlé…"
                                   class="form-input">
                        </div>
                    </div>

                    {{-- Estado --}}
                    <label class="flex items-center gap-2 cursor-pointer select-none">
                        <input wire:model="activo" type="checkbox"
                               class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                        <span class="text-sm text-gray-700">Activo — visible en la lista de emisión</span>
                    </label>

                </div>

                {{-- ══ TAB AVANZADO ═══════════════════════════════ --}}
                <div x-show="tab === 'avanzado'" class="p-5 space-y-4">

                    <div>
                        <label class="form-label">Descripción larga</label>
                        <textarea wire:model="descripcionLarga" rows="3"
                                  class="form-input resize-none"
                                  placeholder="{{ $tipo === 'bien' ? 'Especificaciones técnicas, dimensiones…' : 'Alcance del servicio, condiciones…' }}"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="form-label">Precio de costo</label>
                            <div class="relative">
                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none">L.</span>
                                <input wire:model="precioCompra" type="number" step="0.01" min="0"
                                       class="form-input pl-7 text-right tabular-nums">
                            </div>
                            <p class="form-hint">Uso interno. No aparece en facturas.</p>
                        </div>
                        <div class="flex flex-col justify-start gap-2.5 pt-6">
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700 select-none">
                                <input wire:model="incluyeIsv" type="checkbox"
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                Precio incluye ISV
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer text-sm text-gray-700 select-none">
                                <input wire:model="precioEditableEmision" type="checkbox"
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                Precio editable al emitir
                            </label>
                            <label x-show="tipo === 'bien'"
                                   class="flex items-center gap-2 cursor-pointer text-sm text-gray-700 select-none">
                                <input wire:model="controlaInventario" type="checkbox"
                                       class="rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                Controla inventario
                            </label>
                            <p x-show="tipo === 'servicio'"
                               class="text-xs text-gray-400 flex items-center gap-1.5">
                                <x-icon name="information-circle" class="w-3.5 h-3.5 shrink-0"/>
                                Los servicios no manejan inventario.
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Notas internas</label>
                        <textarea wire:model="notas" rows="3" class="form-input resize-none"
                                  placeholder="Notas visibles solo en la administración…"></textarea>
                    </div>

                </div>

            </form>

            {{-- Pie --}}
            <div class="px-5 py-3.5 border-t border-gray-100 bg-gray-50/50 flex justify-end gap-3 shrink-0">
                <button type="button" wire:click="$set('mostrarModal', false)" class="btn-secondary">
                    Cancelar
                </button>
                <button wire:click="guardar" wire:loading.attr="disabled" wire:target="guardar" class="btn-primary">
                    <svg wire:loading wire:target="guardar"
                         class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Guardar producto
                </button>
            </div>

        </div>
    </div>
    @endif

</div>
