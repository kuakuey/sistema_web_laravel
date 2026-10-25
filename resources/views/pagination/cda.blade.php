@if ($paginator->hasPages())
    @php
        $actual = $paginator->currentPage();
        $total = $paginator->lastPage();
        $ventana = 3;

        if ($total <= $ventana) {
            $inicio = 1;
            $fin = $total;
        } else {
            $inicio = min($actual, $total - $ventana + 1);
            $inicio = max(1, $inicio);
            $fin = $inicio + $ventana - 1;
        }
    @endphp

    <nav role="navigation" aria-label="Paginación" class="flex flex-col items-center justify-between gap-3 sm:flex-row">
        <p class="text-xs text-muted">
            Mostrando <span class="font-medium text-ink">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
            de <span class="font-medium text-ink">{{ $paginator->total() }}</span>
        </p>

        <div class="flex items-center gap-1">
            @if (! $paginator->onFirstPage())
                <a
                    href="{{ $paginator->previousPageUrl() }}"
                    rel="prev"
                    aria-label="Anterior"
                    class="grid size-9 place-items-center rounded-lg text-muted transition hover:bg-surface-2 hover:text-ink"
                >
                    <x-ui.icon name="chevron-left" />
                </a>
            @endif

            @for ($pagina = $inicio; $pagina <= $fin; $pagina++)
                @if ($pagina === $actual)
                    <span aria-current="page" class="grid size-9 place-items-center rounded-lg bg-accent-soft text-sm font-medium text-accent">{{ $pagina }}</span>
                @else
                    <a
                        href="{{ $paginator->url($pagina) }}"
                        class="grid size-9 place-items-center rounded-lg text-sm text-muted transition hover:bg-surface-2 hover:text-ink"
                        aria-label="Ir a la página {{ $pagina }}"
                    >{{ $pagina }}</a>
                @endif
            @endfor

            @if ($paginator->hasMorePages())
                <a
                    href="{{ $paginator->nextPageUrl() }}"
                    rel="next"
                    aria-label="Siguiente"
                    class="grid size-9 place-items-center rounded-lg text-muted transition hover:bg-surface-2 hover:text-ink"
                >
                    <x-ui.icon name="chevron-right" />
                </a>
            @endif
        </div>
    </nav>
@endif
