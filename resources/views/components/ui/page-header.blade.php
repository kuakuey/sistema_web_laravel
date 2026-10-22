@props(['title', 'subtitle' => null])

<div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <div class="min-w-0">
        <h1 class="text-2xl font-semibold tracking-tight text-ink">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1 text-sm text-muted">{{ $subtitle }}</p>
        @endif
    </div>

    @if (isset($actions) && $actions->isNotEmpty())
        <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
    @endif
</div>
