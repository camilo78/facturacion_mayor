<div class="space-y-6">

    <div>
        <h1 class="text-xl font-semibold text-gray-900">Dashboard</h1>
        <p class="text-sm text-gray-500 mt-0.5">{{ now()->format('l, d/m/Y') }}</p>
    </div>

    {{-- Stats ─────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        @php
        $stats = [
            ['label' => 'Facturas hoy',        'value' => number_format($cntHoy),           'sub' => 'vigentes',    'color' => 'text-primary-600'],
            ['label' => 'Monto hoy',            'value' => 'L. ' . number_format($totalHoy, 2), 'sub' => 'vigentes', 'color' => 'text-primary-600'],
            ['label' => 'Total vigentes',       'value' => number_format($cntVigentes),      'sub' => 'acumuladas',  'color' => 'text-gray-900'],
            ['label' => 'Anuladas hoy',         'value' => number_format($cntAnuladasHoy),   'sub' => 'facturas',    'color' => 'text-red-600'],
        ];
        @endphp

        @foreach($stats as $s)
        <div class="card p-5">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $s['label'] }}</p>
            <p class="mt-2 text-2xl font-bold tabular-nums {{ $s['color'] }}">{{ $s['value'] }}</p>
            <p class="text-xs text-gray-400 mt-0.5">{{ $s['sub'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- Alertas CAI ─────────────────────────────────── --}}
    @if($alertasCai->isNotEmpty())
    <div class="card">
        <div class="px-5 py-4 border-b border-primary-100/60 bg-primary-50/40">
            <h2 class="text-sm font-semibold text-gray-900 flex items-center gap-2">
                <x-icon name="exclamation-triangle" class="w-4 h-4 text-amber-500"/>
                Alertas de CAI
            </h2>
        </div>
        <div class="divide-y divide-gray-100">
            @foreach($alertasCai as $cai)
            <div class="flex items-center justify-between px-5 py-3 text-sm">
                <div>
                    <span class="font-mono text-gray-700">{{ $cai->cai }}</span>
                    <span class="text-gray-500 ml-2">
                        ({{ $cai->puntoEmision->establecimiento->codigo ?? '?' }}-{{ $cai->puntoEmision->codigo ?? '?' }})
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    @if($cai->por_vencer)
                        <x-ui.badge color="amber">Por vencer {{ $cai->fecha_limite_emision->diffInDays(today()) }}d</x-ui.badge>
                    @endif
                    @if($cai->por_agotarse)
                        <x-ui.badge color="orange">{{ round($cai->porcentaje_usado * 100) }}% usado</x-ui.badge>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Últimas facturas ────────────────────────────── --}}
    <div class="card" padding="false">
        <div class="px-5 py-4 border-b border-primary-100/60 bg-primary-50/40 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900">Últimas facturas</h2>
            <a href="{{ route('tenant.facturas', tenant('id')) }}" class="text-xs text-primary-600 hover:underline">
                Ver todas
            </a>
        </div>
        @if($ultimasFacturas->isEmpty())
            <x-ui.empty-state icon="document-text" title="Sin facturas aún"
                description="Emite la primera factura del día."
                cta-label="Nueva factura"
                :cta-href="route('tenant.factura.nueva', tenant('id'))"/>
        @else
        <table class="min-w-full text-sm">
            <thead class="bg-primary-600">
                <tr>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Número</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Cliente</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Fecha</th>
                    <th class="px-5 py-3 text-right text-xs font-semibold text-white/90 uppercase tracking-wide">Total</th>
                    <th class="px-5 py-3 text-left text-xs font-semibold text-white/90 uppercase tracking-wide">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($ultimasFacturas as $f)
                <tr class="hover:bg-primary-50/60 transition-colors">
                    <td class="px-5 py-3 font-mono text-xs font-semibold text-primary-700">{{ $f->numero_completo }}</td>
                    <td class="px-5 py-3 text-gray-700 max-w-[200px] truncate">{{ $f->nombre_cliente }}</td>
                    <td class="px-5 py-3 text-gray-500">{{ $f->fecha_emision->format('d/m/Y H:i') }}</td>
                    <td class="px-5 py-3 text-right tabular-nums font-medium text-gray-900">L. {{ number_format($f->total, 2) }}</td>
                    <td class="px-5 py-3">
                        <x-ui.badge :color="$f->estado === 'VIGENTE' ? 'green' : 'red'">
                            {{ $f->estado }}
                        </x-ui.badge>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
