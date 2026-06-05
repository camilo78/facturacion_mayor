{{-- <x-ui.empty-state icon="document-text" title="Sin facturas" description="Emite la primera factura." cta-label="Nueva factura" cta-href="..." /> --}}
@props([
    'icon'        => 'document-text',
    'title'       => 'Sin registros',
    'description' => null,
    'ctaLabel'    => null,
    'ctaHref'     => null,
    'ctaAction'   => null,
])
<div class="flex flex-col items-center justify-center py-16 text-center">
    <div class="w-14 h-14 rounded-full bg-primary-100 flex items-center justify-center mb-4">
        <x-icon :name="$icon" class="w-7 h-7 text-primary-600"/>
    </div>
    <p class="text-sm font-medium text-gray-900">{{ $title }}</p>
    @if($description)
        <p class="text-sm text-gray-500 mt-1 max-w-xs">{{ $description }}</p>
    @endif
    @if($ctaLabel)
        @if($ctaHref)
            <a href="{{ $ctaHref }}" class="btn-primary mt-4">{{ $ctaLabel }}</a>
        @elseif($ctaAction)
            <button wire:click="{{ $ctaAction }}" class="btn-primary mt-4">{{ $ctaLabel }}</button>
        @endif
    @endif
</div>
