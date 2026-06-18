<div wire:poll.30s class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-gray-900">Instancias Auxiliares</h1>
            <p class="text-sm text-gray-500 mt-0.5">
                Actualizado <span class="tabular-nums">{{ now()->format('H:i:s') }}</span>
            </p>
        </div>
        <button wire:click="$refresh"
                wire:loading.attr="disabled"
                class="flex items-center gap-1.5 px-3 py-1.5 text-sm text-gray-600
                       border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            <x-icon name="arrow-path" class="w-4 h-4" wire:loading.class="animate-spin"/>
            Actualizar
        </button>
    </div>

    {{-- Tarjetas de resumen --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">

        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 shadow-sm">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Total</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $resumen['total'] }}</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 shadow-sm">
            <p class="text-xs text-green-600 uppercase tracking-wide">Conectadas</p>
            <p class="text-2xl font-bold text-green-700 mt-1">{{ $resumen['conectadas'] }}</p>
            <p class="text-[10px] text-gray-400 mt-0.5">Últimos 15 min</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 shadow-sm">
            <p class="text-xs text-yellow-600 uppercase tracking-wide">Inactivas</p>
            <p class="text-2xl font-bold text-yellow-700 mt-1">{{ $resumen['inactivas'] }}</p>
            <p class="text-[10px] text-gray-400 mt-0.5">15 min – 1 hora</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 px-4 py-3 shadow-sm">
            <p class="text-xs text-red-600 uppercase tracking-wide">Desconectadas</p>
            <p class="text-2xl font-bold text-red-700 mt-1">{{ $resumen['desconectadas'] }}</p>
            <p class="text-[10px] text-gray-400 mt-0.5">Más de 1 hora</p>
        </div>

    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

        @if($instancias->isEmpty())
            <div class="px-6 py-16 text-center">
                <x-icon name="server" class="w-10 h-10 text-gray-300 mx-auto mb-3"/>
                <p class="text-sm font-medium text-gray-500">No hay instancias Auxiliares registradas</p>
                <p class="text-xs text-gray-400 mt-1">Usa <code class="bg-gray-100 px-1 rounded">php artisan instance:registrar</code> para dar de alta una.</p>
            </div>
        @else
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Instancia</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Empresa</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Último contacto</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Estado</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Cola pendiente</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Activo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @foreach($instancias as $inst)
                    <tr class="{{ $inst['activo'] ? '' : 'opacity-50' }} hover:bg-gray-50 transition-colors">

                        {{-- Instancia --}}
                        <td class="px-4 py-3">
                            <p class="text-sm font-medium text-gray-900">{{ $inst['label'] }}</p>
                            <p class="text-[10px] text-gray-400 font-mono mt-0.5">{{ substr($inst['id'], 0, 18) }}…</p>
                        </td>

                        {{-- Empresa --}}
                        <td class="px-4 py-3">
                            <p class="text-sm text-gray-700">{{ $inst['tenant_nombre'] }}</p>
                            @if($inst['tenant_id'])
                                <p class="text-[10px] text-gray-400 mt-0.5 font-mono">{{ $inst['tenant_id'] }}</p>
                            @endif
                        </td>

                        {{-- Último contacto --}}
                        <td class="px-4 py-3">
                            @if($inst['last_seen_at'])
                                <p class="text-sm text-gray-700">{{ $inst['last_seen_at']->diffForHumans() }}</p>
                                <p class="text-[10px] text-gray-400 mt-0.5">{{ $inst['last_seen_at']->format('d/m/Y H:i:s') }}</p>
                            @else
                                <span class="text-xs text-gray-400 italic">Nunca</span>
                            @endif
                        </td>

                        {{-- Estado --}}
                        <td class="px-4 py-3">
                            @php
                                $badgeClass = match($inst['estado']) {
                                    'conectado'     => 'bg-green-100 text-green-700 ring-green-200',
                                    'inactivo'      => 'bg-yellow-100 text-yellow-700 ring-yellow-200',
                                    'desconectado'  => 'bg-red-100 text-red-700 ring-red-200',
                                    default         => 'bg-gray-100 text-gray-600 ring-gray-200',
                                };
                                $dotClass = match($inst['estado']) {
                                    'conectado'     => 'bg-green-500',
                                    'inactivo'      => 'bg-yellow-500',
                                    'desconectado'  => 'bg-red-500',
                                    default         => 'bg-gray-400',
                                };
                            @endphp
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ring-1 {{ $badgeClass }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $dotClass }} {{ $inst['estado'] === 'conectado' ? 'animate-pulse' : '' }}"></span>
                                {{ ucfirst($inst['estado']) }}
                            </span>
                        </td>

                        {{-- Cola pendiente --}}
                        <td class="px-4 py-3 text-center">
                            @if($inst['pendientes'] === -1)
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-600 ring-1 ring-red-200">
                                    <x-icon name="exclamation-triangle" class="w-3 h-3"/>
                                    Error
                                </span>
                            @elseif($inst['pendientes'] > 0)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-700 ring-1 ring-orange-200">
                                    {{ number_format($inst['pendientes']) }}
                                </span>
                            @else
                                <span class="text-sm text-gray-400">—</span>
                            @endif
                        </td>

                        {{-- Toggle activo --}}
                        <td class="px-4 py-3 text-center">
                            <button wire:click="toggleActivo('{{ $inst['id'] }}')"
                                    wire:loading.attr="disabled"
                                    wire:target="toggleActivo('{{ $inst['id'] }}')"
                                    title="{{ $inst['activo'] ? 'Desactivar instancia' : 'Activar instancia' }}"
                                    @class([
                                        'relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none',
                                        'bg-primary-600'  =>  $inst['activo'],
                                        'bg-gray-200'     => !$inst['activo'],
                                    ])>
                                <span @class([
                                        'pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                                        'translate-x-4' =>  $inst['activo'],
                                        'translate-x-0' => !$inst['activo'],
                                    ])></span>
                            </button>
                        </td>

                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

</div>
