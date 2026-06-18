<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Factunet Mayor' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans antialiased bg-gray-50" x-data>

<div class="min-h-screen flex flex-col">

    <header class="bg-[#1b3a5c] h-14 flex items-center px-6 gap-4 shadow-md">
        <div class="flex items-center gap-2.5">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary-600 shadow-sm">
                <x-icon name="clipboard-list" class="w-5 h-5 text-white"/>
            </div>
            <span class="font-semibold text-sm text-white">Factunet</span>
            <span class="text-[10px] text-[#8bafc8] uppercase tracking-wide ml-1">Mayor</span>
        </div>

        <div class="h-5 w-px bg-[#142d48] mx-2"></div>

        <nav class="flex gap-1">
            <a href="{{ route('mayor.instancias') }}"
               @class([
                   'flex items-center gap-1.5 px-3 py-1.5 rounded-md text-sm transition-colors',
                   'bg-primary-600 text-white'                              => request()->routeIs('mayor.instancias'),
                   'text-[#a8c4d8] hover:bg-[#142d48] hover:text-white'    => ! request()->routeIs('mayor.instancias'),
               ])>
                <x-icon name="server" class="w-4 h-4"/>
                Instancias
            </a>
        </nav>

        <div class="ml-auto text-xs text-[#8bafc8]">{{ now()->format('d/m/Y H:i') }}</div>
    </header>

    <main class="flex-1 p-6">
        {{ $slot }}
    </main>

    <footer class="px-6 py-3 text-center text-xs text-gray-300 border-t border-gray-100">
        Factunet &mdash; {{ config('instance.label', 'Nodo Mayor') }}
    </footer>

</div>

@livewireScripts
</body>
</html>
