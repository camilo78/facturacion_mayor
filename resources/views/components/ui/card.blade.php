{{-- <x-ui.card> <x-slot:header>Título</x-slot:header> Contenido </x-ui.card> --}}
@props(['padding' => true])
<div {{ $attributes->merge(['class' => 'card']) }}>
    @isset($header)
        <div class="px-5 py-4 border-b border-primary-100/60 bg-primary-50/40 flex items-center justify-between gap-4">
            {{ $header }}
        </div>
    @endisset
    <div @class(['p-5' => $padding])>
        {{ $slot }}
    </div>
    @isset($footer)
        <div class="px-5 py-3 border-t border-primary-100/60 bg-primary-50/30 text-sm text-gray-500">
            {{ $footer }}
        </div>
    @endisset
</div>
