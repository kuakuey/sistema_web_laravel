<div {{ $attributes->merge(['class' => 'filters-panel mb-5']) }}>
    <button type="button" class="filters-toggle" data-filters-toggle aria-expanded="false">
        <x-ui.icon name="funnel" />
        <span>Filtros</span>
        <x-ui.icon name="chevron-down" class="filters-chevron" />
    </button>
    <div class="filters-body" data-filters-body>
        {{ $slot }}
    </div>
</div>
