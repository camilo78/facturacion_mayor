{{-- <x-ui.badge color="green">VIGENTE</x-ui.badge> --}}
@props(['color' => 'gray'])
@php
$cls = match($color) {
    'green'  => 'bg-green-100 text-green-800',
    'red'    => 'bg-red-100 text-red-800',
    'amber'  => 'bg-amber-100 text-amber-800',
    'blue'   => 'bg-blue-100 text-blue-800',
    'orange' => 'bg-orange-100 text-orange-800',
    'purple' => 'bg-purple-100 text-purple-800',
    default  => 'bg-gray-100 text-gray-700',
};
@endphp
<span {{ $attributes->merge(['class' => "badge $cls"]) }}>{{ $slot }}</span>
