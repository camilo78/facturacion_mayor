@if ($paginator->hasPages())
<nav class="flex items-center gap-0.5" aria-label="Paginación">

    {{-- Anterior --}}
    @if ($paginator->onFirstPage())
        <span class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs text-gray-300 cursor-not-allowed rounded select-none">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Ant.
        </span>
    @else
        <button wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Ant.
        </button>
    @endif

    {{-- Páginas --}}
    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="inline-flex items-center justify-center w-7 h-7 text-xs text-gray-400 select-none">…</span>
        @endif

        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span aria-current="page"
                          class="inline-flex items-center justify-center w-7 h-7 text-xs font-semibold bg-primary-600 text-white rounded select-none">
                        {{ $page }}
                    </span>
                @else
                    <button wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')" wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center w-7 h-7 text-xs text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded transition-colors">
                        {{ $page }}
                    </button>
                @endif
            @endforeach
        @endif
    @endforeach

    {{-- Siguiente --}}
    @if ($paginator->hasMorePages())
        <button wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled"
                class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded transition-colors">
            Sig.
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    @else
        <span class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs text-gray-300 cursor-not-allowed rounded select-none">
            Sig.
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </span>
    @endif

</nav>
@endif
