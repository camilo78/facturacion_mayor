<!DOCTYPE html>
<html lang="es" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>{{ $title ?? 'POS' }} — {{ tenant('nombre') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans antialiased bg-gray-100 overflow-hidden" x-data>

<div wire:loading.delay
     class="fixed top-0 inset-x-0 z-[9999] h-[2px] pointer-events-none overflow-hidden">
    <div class="h-full w-1/3 bg-primary-500 [animation:loadbar_0.9s_linear_infinite] will-change-transform"
         style="box-shadow: 0 0 8px 0 var(--color-primary-500)"></div>
</div>

<div class="h-full flex flex-col">
    {{ $slot }}
</div>

<x-toast/>
@livewireScripts
</body>
</html>
