@props(['type' => 'info', 'dismissible' => false])

@php
    $tonos = [
        'success' => ['border-success/25 bg-success-soft text-success', 'check-circle'],
        'danger' => ['border-danger/25 bg-danger-soft text-danger', 'alert'],
        'warning' => ['border-warning/25 bg-warning-soft text-warning', 'warning'],
        'info' => ['border-accent/25 bg-accent-soft text-accent', 'info'],
    ];

    [$clases, $icono] = $tonos[$type] ?? $tonos['info'];
@endphp

<div
    data-alert
    role="{{ $type === 'danger' ? 'alert' : 'status' }}"
    {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-lg border px-4 py-3 text-sm '.$clases]) }}
>
    <x-ui.icon :name="$icono" class="mt-0.5 shrink-0 text-base leading-none" />
    <div class="min-w-0 flex-1">{{ $slot }}</div>
    @if ($dismissible)
        <button type="button" data-dismiss class="-mr-1 rounded p-1 opacity-70 transition hover:opacity-100" aria-label="Cerrar aviso">
            <x-ui.icon name="x" class="text-xs" />
        </button>
    @endif
</div>
