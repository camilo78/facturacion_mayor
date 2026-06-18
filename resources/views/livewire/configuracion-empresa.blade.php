<div class="space-y-6 max-w-5xl"
     x-data="{
         colorP: $wire.entangle('colorPrimario'),
         colorS: $wire.entangle('colorSecundario'),
         logoPreview: null,
         handleFile(e) {
             const f = e.target.files[0];
             if (!f) return;
             const reader = new FileReader();
             reader.onload = ev => this.logoPreview = ev.target.result;
             reader.readAsDataURL(f);
         }
     }">

    {{-- ── Encabezado ── --}}
    <div class="flex items-start justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Configuración de Empresa</h1>
            <p class="text-sm text-gray-500 mt-0.5">Información legal registrada ante el SAR Honduras</p>
        </div>
        <x-ui.badge color="blue">empresa.editar</x-ui.badge>
    </div>

    <form wire:submit="guardar">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- ════════════════════════════════════════════════════════
                 Columna izquierda — Campos del formulario (2/3)
            ═══════════════════════════════════════════════════════════ --}}
            <div class="lg:col-span-2 space-y-5">

                {{-- Card: Identidad legal --}}
                <div class="card">
                    <div class="px-5 py-3.5 border-b border-gray-100">
                        <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                            <x-icon name="identification" class="w-4 h-4 text-primary-600"/>
                            Identidad Legal
                        </h2>
                    </div>
                    <div class="p-5 space-y-4">

                        <div>
                            <label class="form-label">
                                Nombre legal / Razón social
                                <span class="text-red-500 ml-0.5">*</span>
                            </label>
                            <input wire:model="nombre" type="text" maxlength="255"
                                   placeholder="Nombre tal como aparece en la patente del comercio"
                                   class="form-input @error('nombre') error @enderror">
                            @error('nombre') <p class="form-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">
                                    Nombre comercial
                                    <span class="text-gray-400 font-normal ml-1">(opcional)</span>
                                </label>
                                <input wire:model="nombreComercial" type="text" maxlength="255"
                                       placeholder="Nombre visible al cliente"
                                       class="form-input @error('nombreComercial') error @enderror">
                                @error('nombreComercial') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">
                                    RTN <span class="text-red-500 ml-0.5">*</span>
                                </label>
                                <input wire:model="rtn" type="text" maxlength="20"
                                       placeholder="0000-0000-000000"
                                       class="form-input font-mono tracking-widest @error('rtn') error @enderror">
                                @error('rtn') <p class="form-error">{{ $message }}</p> @enderror
                                <p class="form-hint">Aparece en todas las facturas emitidas.</p>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Card: Contacto --}}
                <div class="card">
                    <div class="px-5 py-3.5 border-b border-gray-100">
                        <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                            <x-icon name="information-circle" class="w-4 h-4 text-primary-600"/>
                            Contacto
                        </h2>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label">
                                    Correo electrónico <span class="text-red-500 ml-0.5">*</span>
                                </label>
                                <input wire:model="email" type="email" maxlength="255"
                                       class="form-input @error('email') error @enderror">
                                @error('email') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="form-label">
                                    Teléfono
                                    <span class="text-gray-400 font-normal ml-1">(opcional)</span>
                                </label>
                                <input wire:model="telefono" type="text" maxlength="20"
                                       placeholder="+504 0000-0000"
                                       class="form-input @error('telefono') error @enderror">
                                @error('telefono') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Card: Colores de marca --}}
                <div class="card">
                    <div class="px-5 py-3.5 border-b border-gray-100">
                        <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                            <x-icon name="swatch" class="w-4 h-4 text-primary-600"/>
                            Colores de Marca
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">Se aplican en las facturas PDF generadas</p>
                    </div>
                    <div class="p-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">

                            {{-- Color primario --}}
                            <div>
                                <label class="form-label">Color primario</label>
                                <p class="text-xs text-gray-400 mb-2">Encabezados, textos y fondos oscuros</p>
                                <div class="flex items-center gap-3">
                                    <input type="color"
                                           x-model.lazy="colorP"
                                           class="w-12 h-10 rounded-lg border border-gray-300 cursor-pointer p-0.5 bg-white shrink-0">
                                    <div class="flex-1">
                                        <input type="text"
                                               :value="colorP"
                                               x-on:change="if(/^#[0-9A-Fa-f]{6}$/.test($event.target.value)) colorP = $event.target.value"
                                               maxlength="7"
                                               placeholder="#1b3a5c"
                                               class="form-input font-mono uppercase tracking-widest @error('colorPrimario') error @enderror">
                                    </div>
                                    <div class="w-8 h-8 rounded-full border border-gray-200 shrink-0"
                                         :style="'background:' + colorP"></div>
                                </div>
                                @error('colorPrimario') <p class="form-error mt-1">{{ $message }}</p> @enderror
                            </div>

                            {{-- Color secundario / acento --}}
                            <div>
                                <label class="form-label">Color de acento</label>
                                <p class="text-xs text-gray-400 mb-2">Botones, etiquetas y detalles</p>
                                <div class="flex items-center gap-3">
                                    <input type="color"
                                           x-model.lazy="colorS"
                                           class="w-12 h-10 rounded-lg border border-gray-300 cursor-pointer p-0.5 bg-white shrink-0">
                                    <div class="flex-1">
                                        <input type="text"
                                               :value="colorS"
                                               x-on:change="if(/^#[0-9A-Fa-f]{6}$/.test($event.target.value)) colorS = $event.target.value"
                                               maxlength="7"
                                               placeholder="#009898"
                                               class="form-input font-mono uppercase tracking-widest @error('colorSecundario') error @enderror">
                                    </div>
                                    <div class="w-8 h-8 rounded-full border border-gray-200 shrink-0"
                                         :style="'background:' + colorS"></div>
                                </div>
                                @error('colorSecundario') <p class="form-error mt-1">{{ $message }}</p> @enderror
                            </div>

                        </div>

                        {{-- ── Vista previa: mini factura ── --}}
                        <div class="mt-5 rounded-xl overflow-hidden border border-gray-200 shadow-sm select-none"
                             style="font-family: Arial, sans-serif; font-size: 8px; line-height: 1.3;">

                            {{-- Encabezado 3 columnas --}}
                            <div class="flex items-stretch bg-white"
                                 :style="'border-bottom: 3px solid ' + colorS">

                                {{-- Columna logo --}}
                                <div class="flex items-center justify-center bg-white shrink-0"
                                     style="width:18%; padding: 8px 6px;">
                                    <img x-show="logoPreview" :src="logoPreview"
                                         style="max-height:40px; max-width:100%; object-fit:contain; display:block;">
                                    @if($logoActual)
                                    <img x-show="!logoPreview"
                                         src="{{ Storage::disk('central_public')->url($logoActual) }}"
                                         style="max-height:40px; max-width:100%; object-fit:contain; display:block;">
                                    @else
                                    <div x-show="!logoPreview"
                                         class="flex items-center justify-center rounded text-gray-200 bg-gray-50"
                                         style="width:36px; height:36px; font-size:20px; border:1px dashed #d1d5db;">
                                        <svg style="width:18px;height:18px;fill:#d1d5db" viewBox="0 0 24 24"><path d="M4 16l4-4 3 3 4-5 5 6H4z"/><rect x="3" y="3" width="18" height="18" rx="2" ry="2" fill="none" stroke="#d1d5db" stroke-width="1.5"/></svg>
                                    </div>
                                    @endif
                                </div>

                                {{-- Columna empresa --}}
                                <div class="flex-1 py-2 px-2">
                                    <div class="font-bold" style="font-size:9.5px; text-transform:uppercase; line-height:1.2"
                                         :style="'color:' + colorP">
                                        {{ $nombre ?: 'NOMBRE DE LA EMPRESA' }}
                                    </div>
                                    @if($nombreComercial)
                                    <div style="font-size:7.5px; margin-top:1px" :style="'color:' + colorS">
                                        {{ $nombreComercial }}
                                    </div>
                                    @endif
                                    <div style="font-size:6.5px; color:#6b7280; margin-top:3px">
                                        Tel: {{ $telefono ?: '0000-0000' }} / Email: {{ $email ?: 'correo@empresa.com' }}<br>
                                        Honduras<br>
                                        RTN: {{ $rtn ?: '0000-0000-000000' }}
                                    </div>
                                </div>

                                {{-- Columna FACTURA + CAI --}}
                                <div class="text-right py-2 px-2 shrink-0" style="width:38%">
                                    <div class="font-black uppercase" style="font-size:13px; letter-spacing:2px"
                                         :style="'color:' + colorS">FACTURA</div>
                                    <div class="font-mono font-bold" style="font-size:7.5px; margin-top:1px"
                                         :style="'color:' + colorP">000-001-01-00000050</div>
                                    <div style="font-size:5.5px; color:#9ca3af; margin-top:3px; line-height:1.5">
                                        CAI: ABCDEF-123456-ABCDEF-01<br>
                                        <span style="font-size:5px" :style="'color:' + colorP">RANGO AUTORIZADO</span><br>
                                        000-001-01-00000001<br>
                                        al 000-001-01-00000100<br>
                                        <span :style="'color:' + colorS">FECHA LÍMITE DE EMISIÓN:</span> 26/05/2027
                                    </div>
                                </div>
                            </div>

                            {{-- Fila cabecera cliente --}}
                            <div class="flex" :style="'background:' + colorP">
                                <div class="flex-1 px-2 py-1 font-bold text-white uppercase"
                                     style="font-size:6.5px; letter-spacing:0.5px">Cliente</div>
                                <div class="px-2 py-1 font-bold text-white uppercase"
                                     style="font-size:6.5px; letter-spacing:0.5px; width:42%; border-left:1px solid rgba(255,255,255,0.25)">
                                    Datos de la factura
                                </div>
                            </div>

                            {{-- Fila 2: nombre | fecha+hora --}}
                            <div class="flex bg-white" style="border-bottom:1px solid #f3f4f6">
                                <div class="flex-1 px-2 py-1.5 font-bold" style="font-size:8px"
                                     :style="'color:' + colorP">Consumidor Final</div>
                                <div class="px-2 py-1.5" style="font-size:6.5px; color:#374151; width:42%; border-left:1px solid #f3f4f6">
                                    <span class="font-bold" :style="'color:' + colorS">Fecha de factura:</span> 31/05/2026<br>
                                    <span class="font-bold" :style="'color:' + colorS">Hora:</span> 10:00
                                </div>
                            </div>

                            {{-- Fila 3: RTN/dirección | pago+lugar --}}
                            <div class="flex bg-white">
                                <div class="flex-1 px-2 py-1.5" style="font-size:6.5px; color:#374151">
                                    <span class="font-bold" :style="'color:' + colorS">RTN:</span> 999-999-99-99999
                                </div>
                                <div class="px-2 py-1.5" style="font-size:6.5px; color:#374151; width:42%; border-left:1px solid #f3f4f6">
                                    <span class="font-bold" :style="'color:' + colorS">Término de pago:</span> Contado<br>
                                    <span class="font-bold" :style="'color:' + colorS">Lugar de emisión:</span> Casa Matriz
                                </div>
                            </div>

                            {{-- Cabecera tabla ítems --}}
                            <div class="flex" :style="'background:' + colorS">
                                <div class="flex-1 px-2 py-1 font-bold text-white uppercase"
                                     style="font-size:6px; letter-spacing:0.4px">Descripción</div>
                                <div class="px-2 py-1 font-bold text-white uppercase text-right"
                                     style="font-size:6px; white-space:nowrap">Cantidad</div>
                                <div class="px-2 py-1 font-bold text-white uppercase text-right"
                                     style="font-size:6px; white-space:nowrap">Precio Unit.</div>
                                <div class="px-2 py-1 font-bold text-white uppercase text-right"
                                     style="font-size:6px; white-space:nowrap">Descto.</div>
                                <div class="px-2 py-1 font-bold text-white uppercase text-right"
                                     style="font-size:6px; white-space:nowrap">Impuesto</div>
                                <div class="px-2 py-1 font-bold text-white uppercase text-right"
                                     style="font-size:6px; white-space:nowrap">Importe</div>
                            </div>

                            {{-- Barra de colores al pie --}}
                            <div class="flex">
                                <div class="flex-1 py-1.5 px-2 flex items-center gap-1.5"
                                     :style="'background:' + colorP">
                                    <span class="font-mono text-white/90 uppercase" style="font-size:7px"
                                          x-text="colorP"></span>
                                    <span class="text-white/50" style="font-size:6px">Primario</span>
                                </div>
                                <div class="flex-1 py-1.5 px-2 flex items-center gap-1.5"
                                     :style="'background:' + colorS">
                                    <span class="font-mono text-white/90 uppercase" style="font-size:7px"
                                          x-text="colorS"></span>
                                    <span class="text-white/50" style="font-size:6px">Acento</span>
                                </div>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2 text-center">Vista previa en tiempo real</p>

                    </div>
                </div>

                {{-- Botón guardar --}}
                <div class="flex justify-end">
                    <button type="submit"
                            wire:loading.attr="disabled"
                            wire:target="guardar"
                            class="btn-primary px-6">
                        <svg wire:loading wire:target="guardar"
                             class="w-4 h-4 animate-spin shrink-0" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <x-icon name="check" class="w-4 h-4" wire:loading.remove wire:target="guardar"/>
                        Guardar cambios
                    </button>
                </div>

            </div>

            {{-- ════════════════════════════════════════════════════════
                 Columna derecha — Logo (1/3)
            ═══════════════════════════════════════════════════════════ --}}
            <div class="lg:col-span-1">
                <div class="card sticky top-6">
                    <div class="px-5 py-3.5 border-b border-gray-100">
                        <h2 class="text-sm font-semibold text-gray-800 flex items-center gap-2">
                            <x-icon name="photo" class="w-4 h-4 text-primary-600"/>
                            Logo
                        </h2>
                    </div>
                    <div class="p-5 space-y-4">

                        {{-- Preview --}}
                        <div class="flex justify-center">
                            <div class="w-40 h-40 rounded-2xl border-2 border-dashed border-gray-200
                                        bg-gray-50 flex items-center justify-center overflow-hidden
                                        transition-colors hover:border-primary-300">
                                <img x-show="logoPreview" :src="logoPreview"
                                     class="w-full h-full object-contain p-2" alt="Nuevo logo">

                                @if($logoActual)
                                <img x-show="!logoPreview"
                                     src="{{ Storage::disk('central_public')->url($logoActual) }}"
                                     class="w-full h-full object-contain p-2" alt="Logo actual">
                                @else
                                <div x-show="!logoPreview" class="text-center p-4">
                                    <x-icon name="photo" class="w-10 h-10 text-gray-300 mx-auto mb-2"/>
                                    <p class="text-xs text-gray-400 leading-snug">Sin logo</p>
                                </div>
                                @endif
                            </div>
                        </div>

                        {{-- Input file --}}
                        <div>
                            <label class="form-label">Subir imagen</label>
                            <label class="relative flex flex-col items-center justify-center w-full h-20
                                          rounded-lg border-2 border-dashed border-gray-200
                                          bg-gray-50 cursor-pointer hover:bg-primary-50
                                          hover:border-primary-300 transition-colors">
                                {{-- Estado normal: siempre en el flujo, solo se oculta visualmente --}}
                                <div wire:loading.class="opacity-0" wire:target="logoFile"
                                     class="flex flex-col items-center gap-1 text-gray-500 transition-opacity">
                                    <x-icon name="arrow-down-tray" class="w-5 h-5 text-gray-400"/>
                                    <span class="text-xs font-medium">Haz clic o arrastra</span>
                                </div>
                                {{-- Spinner: overlay absoluto, no afecta el layout --}}
                                <div wire:loading wire:target="logoFile"
                                     class="absolute inset-0 flex items-center justify-center gap-2 text-sm text-primary-600">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                                    </svg>
                                    Subiendo...
                                </div>
                                <input wire:model="logoFile" type="file"
                                       accept="image/jpeg,image/png,image/gif,image/webp"
                                       @change="handleFile($event)"
                                       class="hidden">
                            </label>
                            @error('logoFile') <p class="form-error mt-1">{{ $message }}</p> @enderror
                            <p class="form-hint mt-1.5 text-center">JPG · PNG · GIF · WEBP &nbsp;·&nbsp; máx. 2 MB</p>
                        </div>

                        {{-- Eliminar logo --}}
                        @if($logoActual)
                        <button type="button"
                                wire:click="quitarLogo"
                                wire:confirm="¿Eliminar el logo actual de la empresa?"
                                class="btn-ghost btn-sm w-full justify-center text-red-600
                                       hover:text-red-700 hover:bg-red-50 border border-red-200">
                            <x-icon name="trash" class="w-3.5 h-3.5"/>
                            Eliminar logo
                        </button>
                        @endif

                    </div>
                </div>
            </div>

        </div>
    </form>

</div>
