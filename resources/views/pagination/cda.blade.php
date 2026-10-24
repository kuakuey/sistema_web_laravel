@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Paginación" class="flex flex-col items-center justify-between gap-3 sm:flex-row">
        <p class="text-xs text-muted">
            Mostrando <span class="font-medium text-ink">{{ $paginator->firstItem() }}–{{ $paginator->lastItem() }}</span>
            de <span class="font-medium text-ink">{{ $paginator->total() }}</span>
        </p>

        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="grid size-9 place-items-center rounded-lg text-muted/50" aria-disabled="true"><x-ui.icon name="chevron-left" /></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Anterior" class="grid size-9 place-items-center rounded-lg text-muted transition hover:bg-surface-2 hover:text-ink"><x-ui.icon name="chevron-left" /></a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-1 text-sm text-muted">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="grid size-9 place-items-center rounded-lg bg-accent-soft text-sm font-medium text-accent">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="grid size-9 place-items-center rounded-lg text-sm text-muted transition hover:bg-surface-2 hover:text-ink" aria-label="Ir a la página {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Siguiente" class="grid size-9 place-items-center rounded-lg text-muted transition hover:bg-surface-2 hover:text-ink"><x-ui.icon name="chevron-right" /></a>
            @else
                <span class="grid size-9 place-items-center rounded-lg text-muted/50" aria-disabled="true"><x-ui.icon name="chevron-right" /></span>
            @endif
        </div>
    </nav>
@endif
