@props(['tone' => 'neutral'])

@php
    $tonos = [
        'neutral' => 'bg-surface-2 text-muted',
        'accent' => 'bg-accent-soft text-accent',
        'success' => 'bg-success-soft text-success',
        'warning' => 'bg-warning-soft text-warning',
        'danger' => 'bg-danger-soft text-danger',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium '.($tonos[$tone] ?? $tonos['neutral'])]) }}>
    {{ $slot }}
</span>
