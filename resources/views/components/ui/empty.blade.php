@props(['icon' => 'bi-inbox', 'title' => 'Sin resultados', 'text' => null])

<div class="flex flex-col items-center justify-center px-6 py-16 text-center">
    <span class="mb-4 grid size-12 place-items-center rounded-full bg-surface-2 text-muted">
        <i class="bi {{ $icon }} text-xl" aria-hidden="true"></i>
    </span>
    <p class="text-sm font-medium text-ink">{{ $title }}</p>
    @if ($text)
        <p class="mt-1 max-w-sm text-sm text-muted">{{ $text }}</p>
    @endif
    @if ($slot->isNotEmpty())
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
