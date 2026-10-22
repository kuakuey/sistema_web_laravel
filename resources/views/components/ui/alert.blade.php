@props(['type' => 'info', 'dismissible' => false])

@php
    $tonos = [
        'success' => ['border-success/25 bg-success-soft text-success', 'bi-check-circle'],
        'danger' => ['border-danger/25 bg-danger-soft text-danger', 'bi-exclamation-circle'],
        'warning' => ['border-warning/25 bg-warning-soft text-warning', 'bi-exclamation-triangle'],
        'info' => ['border-accent/25 bg-accent-soft text-accent', 'bi-info-circle'],
    ];

    [$clases, $icono] = $tonos[$type] ?? $tonos['info'];
@endphp

<div
    data-alert
    role="{{ $type === 'danger' ? 'alert' : 'status' }}"
    {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-lg border px-4 py-3 text-sm '.$clases]) }}
>
    <i class="bi {{ $icono }} mt-0.5 text-base leading-none" aria-hidden="true"></i>
    <div class="min-w-0 flex-1">{{ $slot }}</div>
    @if ($dismissible)
        <button type="button" data-dismiss class="-mr-1 rounded p-1 opacity-70 transition hover:opacity-100" aria-label="Cerrar aviso">
            <i class="bi bi-x-lg text-xs" aria-hidden="true"></i>
        </button>
    @endif
</div>
