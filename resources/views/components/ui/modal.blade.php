@props([
    'id' => 'dialogNuevo',
    'title',
    'description' => null,
    'open' => false,
    'size' => 'lg',
])

@php
    $anchos = [
        'md' => 'max-w-lg',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-3xl',
    ];
    $ancho = $anchos[$size] ?? $anchos['lg'];
@endphp

<dialog
    id="{{ $id }}"
    @if ($open) data-open-on-load @endif
    {{ $attributes->class('m-auto max-h-[90vh] w-[calc(100%-2rem)] '.$ancho.' rounded-xl border border-line bg-surface p-0 text-left text-ink shadow-2xl backdrop:bg-ink/40 backdrop:backdrop-blur-sm') }}
>
    <div class="flex max-h-[90vh] flex-col overflow-hidden">
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-line px-6 py-4">
            <div class="min-w-0">
                <h2 class="text-base font-semibold" id="{{ $id }}-titulo">{{ $title }}</h2>
                @if ($description)
                    <p class="mt-0.5 text-sm text-muted">{{ $description }}</p>
                @endif
            </div>
            <button type="button" data-modal-close class="grid size-8 shrink-0 place-items-center rounded-lg text-muted transition hover:bg-surface-2 hover:text-ink" aria-label="Cerrar">
                <i class="bi bi-x-lg text-sm" aria-hidden="true"></i>
            </button>
        </div>
        <div class="min-h-0 flex-1 overflow-y-auto px-6 py-5 text-left">
            {{ $slot }}
        </div>
        @if (isset($footer) && $footer->isNotEmpty())
            <div class="flex shrink-0 justify-end gap-2 border-t border-line px-6 py-4">
                {{ $footer }}
            </div>
        @endif
    </div>
</dialog>
