<div {{ $attributes->merge(['class' => 'filters-panel mb-5']) }}>
    <button type="button" class="filters-toggle" data-filters-toggle aria-expanded="false">
        <i class="bi bi-funnel" aria-hidden="true"></i>
        <span>Filtros</span>
        <i class="bi bi-chevron-down filters-chevron" aria-hidden="true"></i>
    </button>
    <div class="filters-body" data-filters-body>
        {{ $slot }}
    </div>
</div>
