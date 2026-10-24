@props(['variant' => 'primary', 'size' => 'md', 'href' => null, 'type' => 'button', 'icon' => null])

@php
    $base = 'inline-flex shrink-0 items-center justify-center gap-2 rounded-lg font-medium whitespace-nowrap transition disabled:pointer-events-none disabled:opacity-50';

    $tamanos = [
        'sm' => 'h-8 px-3 text-xs',
        'md' => 'h-10 px-4 text-sm',
    ];

    $variantes = [
        'primary' => 'bg-accent text-accent-ink shadow-sm hover:bg-accent-strong',
        'secondary' => 'border border-line bg-surface text-ink hover:bg-surface-2',
        'ghost' => 'text-muted hover:bg-surface-2 hover:text-ink',
        'success' => 'bg-success-soft text-success hover:brightness-95',
        'danger' => 'text-danger hover:bg-danger-soft',
    ];

    $clases = $base.' '.($tamanos[$size] ?? $tamanos['md']).' '.($variantes[$variant] ?? $variantes['primary']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $clases]) }}>
        @if ($icon)<x-ui.icon :name="$icon" class="shrink-0 text-[1.05em]" />@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $clases]) }}>
        @if ($icon)<x-ui.icon :name="$icon" class="shrink-0 text-[1.05em]" />@endif
        {{ $slot }}
    </button>
@endif
