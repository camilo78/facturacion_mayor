<div class="flex items-center gap-2">
    <x-icon name="computer-desktop" class="w-4 h-4 text-gray-400 shrink-0"/>
    <select wire:model.live="puntoEmisionId"
            class="text-sm border-0 bg-transparent text-gray-700 font-medium focus:ring-0 focus:outline-none cursor-pointer py-0 pr-6 pl-0">
        @foreach($cajas as $caja)
            <option value="{{ $caja->id }}">
                {{ $caja->establecimiento?->codigo ?? '?' }}-{{ $caja->codigo }} — {{ $caja->nombre }}
            </option>
        @endforeach
    </select>
</div>
