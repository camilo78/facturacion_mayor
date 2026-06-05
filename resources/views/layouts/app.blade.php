<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Factunet' }} — {{ tenant('nombre') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans antialiased bg-gray-50" x-data>

{{-- ═══ BARRA DE PROGRESO GLOBAL ═══════════════════════════════════
     Se muestra en cualquier request de Livewire (navegación o update).
     wire:loading.delay evita el parpadeo en requests muy rápidos (<150ms).
 ──────────────────────────────────────────────────────────────────── --}}
<div wire:loading.delay
     class="fixed top-0 inset-x-0 z-[9999] h-[2px] pointer-events-none overflow-hidden">
    <div class="h-full w-1/3 bg-primary-600 [animation:loadbar_0.9s_linear_infinite] will-change-transform"
         style="box-shadow: 0 0 8px 0 var(--color-primary-500)"></div>
</div>

{{-- ═══ SIDEBAR ══════════════════════════════════════════════════ --}}
<aside
    :class="$store.sidebar.open ? 'w-64' : 'w-16'"
    x-init="$nextTick(() => {
        $el.style.transition = 'width 280ms cubic-bezier(0.4, 0, 0.2, 1)';
        $el.style.willChange = 'width';
    })"
    class="fixed inset-y-0 left-0 z-40 flex flex-col
           bg-[#1b3a5c] border-r border-[#142d48] overflow-hidden">

    {{-- Marca --}}
    <div class="flex h-14 shrink-0 items-center border-b border-[#142d48] px-3">
        <a href="{{ route('tenant.dashboard', $tid ?? tenant('id')) }}" wire:navigate
           class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary-600 shadow-sm">
            <x-icon name="clipboard-list" class="w-5 h-5 text-white"/>
        </a>
        <div x-show="$store.sidebar.open"
             x-transition:enter="transition-opacity duration-[180ms] delay-[80ms]"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-[60ms]"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="min-w-0 ml-2.5 overflow-hidden">
            <p class="truncate text-sm font-semibold text-white leading-tight">
                {{ tenant('nombre_comercial') ?? tenant('nombre') }}
            </p>
            <p class="text-[10px] text-[#8bafc8] uppercase tracking-wide">Factunet</p>
        </div>
    </div>

    {{-- Navegación --}}
    @php
        $tid    = tenant('id');
        $active = fn(string $name) => request()->routeIs('tenant.' . $name);

        $user = auth()->user();
        $sections = [
            'Principal' => array_filter([
                ['name' => 'dashboard',     'icon' => 'home',          'label' => 'Dashboard',    'href' => route('tenant.dashboard', $tid)],
                $user?->can('facturas.emitir')
                    ? ['name' => 'factura.nueva', 'icon' => 'plus',          'label' => 'Nueva factura','href' => route('tenant.factura.nueva', $tid)]
                    : null,
                $user?->can('facturas.emitir')
                    ? ['name' => 'pos',           'icon' => 'computer-desktop', 'label' => 'POS',      'href' => route('tenant.pos', $tid)]
                    : null,
                $user?->can('facturas.ver')
                    ? ['name' => 'facturas',      'icon' => 'document-text', 'label' => 'Facturas',     'href' => route('tenant.facturas', $tid)]
                    : null,
            ]),
            'Catálogos' => array_filter([
                $user?->can('clientes.ver')
                    ? ['name' => 'clientes',  'icon' => 'users', 'label' => 'Clientes', 'href' => route('tenant.clientes', $tid)]
                    : null,
                $user?->can('productos.ver')
                    ? ['name' => 'productos', 'icon' => 'cube',  'label' => 'Productos','href' => route('tenant.productos', $tid)]
                    : null,
            ]),
            'Configuración' => array_filter([
                $user?->can('cai.gestionar')
                    ? ['name' => 'cai',              'icon' => 'key',             'label' => 'CAI',              'href' => route('tenant.cai',              $tid)]
                    : null,
                $user?->can('establecimientos.gestionar')
                    ? ['name' => 'establecimientos', 'icon' => 'building-office', 'label' => 'Establecimientos', 'href' => route('tenant.establecimientos', $tid)]
                    : null,
                $user?->can('puntos-emision.gestionar')
                    ? ['name' => 'cajas',            'icon' => 'computer-desktop','label' => 'Cajas',            'href' => route('tenant.cajas',            $tid)]
                    : null,
                $user?->can('usuarios.ver')
                    ? ['name' => 'usuarios',         'icon' => 'users',           'label' => 'Usuarios',         'href' => route('tenant.usuarios',         $tid)]
                    : null,
                $user?->can('roles.gestionar')
                    ? ['name' => 'roles',            'icon' => 'key',             'label' => 'Roles',            'href' => route('tenant.roles',            $tid)]
                    : null,
                $user?->can('auditoria.ver')
                    ? ['name' => 'auditoria',        'icon' => 'clipboard-list',  'label' => 'Auditoría',        'href' => route('tenant.auditoria',        $tid)]
                    : null,
            ]),
        ];
        $sections = array_filter($sections, fn ($items) => ! empty($items));
    @endphp

    <nav class="flex-1 overflow-y-auto overflow-x-hidden py-3 space-y-0.5">
        @foreach($sections as $sectionLabel => $items)

        <div x-show="$store.sidebar.open"
             x-transition:enter="transition-opacity duration-[180ms] delay-[80ms]"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-[60ms]"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="px-3 pt-3 pb-1">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-[#6d90ab]">{{ $sectionLabel }}</p>
        </div>
        <div x-show="!$store.sidebar.open"
             x-transition:enter="transition-opacity duration-[120ms] delay-[160ms]"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-[60ms]"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="py-1.5 mx-3 border-t border-[#142d48]"></div>

        @foreach($items as $item)
        <div class="relative"
             x-data="{ hover: false }"
             @mouseenter="hover = true"
             @mouseleave="hover = false">

            {{-- wire:navigate → SPA: no recarga página, solo intercambia contenido --}}
            <a href="{{ $item['href'] }}" wire:navigate
               :class="$store.sidebar.open ? 'gap-3 px-3' : 'justify-center'"
               @class([
                   'flex items-center py-2.5 text-sm transition-colors duration-100',
                   'bg-primary-600 text-white font-semibold'  => $active($item['name']),
                   'text-[#a8c4d8] hover:bg-[#142d48] hover:text-white' => ! $active($item['name']),
               ])>
                <x-icon name="{{ $item['icon'] }}" class="w-[18px] h-[18px] shrink-0"/>
                <span x-show="$store.sidebar.open"
                      x-transition:enter="transition-opacity duration-[180ms] delay-[80ms]"
                      x-transition:enter-start="opacity-0"
                      x-transition:enter-end="opacity-100"
                      x-transition:leave="transition-opacity duration-[60ms]"
                      x-transition:leave-start="opacity-100"
                      x-transition:leave-end="opacity-0"
                      class="truncate whitespace-nowrap">{{ $item['label'] }}</span>
            </a>

            {{-- Tooltip colapsado --}}
            <div x-show="hover && !$store.sidebar.open"
                 x-transition:enter="transition-opacity duration-100"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity duration-75"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="absolute left-full top-1/2 -translate-y-1/2 ml-3 z-50 pointer-events-none">
                <div class="bg-gray-900 text-white text-xs font-medium rounded-md px-2.5 py-1.5 shadow-lg whitespace-nowrap">
                    {{ $item['label'] }}
                </div>
            </div>
        </div>
        @endforeach
        @endforeach
    </nav>

    {{-- Usuario --}}
    <div class="border-t border-[#142d48] p-2 shrink-0">

        {{-- Perfil rápido (expandido) --}}
        <a x-show="$store.sidebar.open"
           href="{{ route('tenant.perfil', $tid) }}" wire:navigate
           x-transition:enter="transition-opacity duration-[180ms] delay-[80ms]"
           x-transition:enter-start="opacity-0"
           x-transition:enter-end="opacity-100"
           x-transition:leave="transition-opacity duration-[60ms]"
           x-transition:leave-start="opacity-100"
           x-transition:leave-end="opacity-0"
           class="flex items-center gap-2 px-2 py-1.5 text-xs text-[#8bafc8] hover:bg-[#142d48] hover:text-white transition-colors mb-1">
            <x-icon name="user-circle" class="w-3.5 h-3.5 shrink-0 text-[#8bafc8]"/>
            <span class="truncate">Mi perfil</span>
        </a>

        {{-- Expandido --}}
        <div x-show="$store.sidebar.open"
             x-transition:enter="transition-opacity duration-[180ms] delay-[80ms]"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-[60ms]"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="flex items-center gap-2.5 rounded-md px-2 py-2">
            <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#142d48] text-primary-400 text-xs font-bold shadow-sm">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </div>
            <div class="min-w-0 flex-1">
                <p class="truncate text-xs font-semibold text-white">{{ auth()->user()->name }}</p>
                <p class="truncate text-[10px] text-[#8bafc8]">{{ auth()->user()->email }}</p>
            </div>
            <form method="POST" action="{{ route('tenant.logout', $tid) }}">
                @csrf
                <button type="submit" title="Salir"
                        class="text-[#8bafc8] hover:text-red-400 transition-colors p-1 rounded">
                    <x-icon name="logout" class="w-4 h-4"/>
                </button>
            </form>
        </div>

        {{-- Colapsado --}}
        <div x-show="!$store.sidebar.open"
             x-transition:enter="transition-opacity duration-[120ms] delay-[160ms]"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-[60ms]"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="space-y-1">
            <div class="flex justify-center py-1">
                <div class="flex h-7 w-7 items-center justify-center rounded-full bg-[#142d48] text-primary-400 text-xs font-bold">
                    {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
                </div>
            </div>
            <form method="POST" action="{{ route('tenant.logout', $tid) }}">
                @csrf
                <button type="submit" title="Salir"
                        class="w-full flex justify-center p-1.5 text-[#8bafc8] hover:text-red-400 hover:bg-[#142d48] rounded-md transition-colors">
                    <x-icon name="logout" class="w-4 h-4"/>
                </button>
            </form>
        </div>
    </div>
</aside>

{{-- ═══ MAIN ════════════════════════════════════════════════════ --}}
<div :class="$store.sidebar.open ? 'pl-64' : 'pl-16'"
     x-init="$nextTick(() => {
         $el.style.transition = 'padding-left 280ms cubic-bezier(0.4, 0, 0.2, 1)';
     })"
     class="flex flex-col min-h-screen">

    {{-- Topbar --}}
    <header class="sticky top-0 z-30 flex h-14 items-center justify-between gap-3
                   border-b border-primary-600/30 bg-white/95 backdrop-blur-sm
                   px-4 shadow-sm" style="border-bottom-width: 2px">
        <div class="flex items-center gap-3">
            <button @click="$store.sidebar.toggle()"
                    class="p-1.5 rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-700 transition-colors"
                    :title="$store.sidebar.open ? 'Contraer menú' : 'Expandir menú'">
                <x-icon name="bars-3" class="w-5 h-5"/>
            </button>
            <div class="h-5 w-px bg-gray-200"></div>
            @livewire('caja-selector')
        </div>
        <div class="text-xs text-gray-400">{{ now()->format('d/m/Y') }}</div>
    </header>

    <main class="flex-1 p-6">
        {{ $slot }}
    </main>

    <footer class="px-6 py-3 text-center text-xs text-gray-300 border-t border-gray-100">
        Factunet &mdash; Facturación electrónica Honduras
    </footer>
</div>

<x-toast/>
@livewireScripts
</body>
</html>
