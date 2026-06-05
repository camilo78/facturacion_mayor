<!DOCTYPE html>
<html lang="es" class="h-full bg-[#f0f9f9]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Iniciar sesión' }} — {{ tenant('nombre') ?? 'Factunet' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full flex items-center justify-center p-4">

    <div class="w-full max-w-sm">
        {{-- Logotipo / Marca --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl bg-primary-600 mb-4">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z"/>
                </svg>
            </div>
            <h1 class="text-lg font-semibold text-gray-900">{{ tenant('nombre') ?? 'Factunet' }}</h1>
            @if(tenant('nombre_comercial'))
                <p class="text-sm text-gray-500">{{ tenant('nombre_comercial') }}</p>
            @endif
        </div>

        {{ $slot }}

        <p class="text-center text-xs text-gray-400 mt-6">
            Factunet &mdash; Facturación electrónica Honduras
        </p>
    </div>

    @livewireScripts
</body>
</html>
