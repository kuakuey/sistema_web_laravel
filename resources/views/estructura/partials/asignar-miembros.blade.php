@php
  $coordIds = collect($coordIds ?? [])->map(fn ($id) => (int) $id)->filter()->values();
  $encIds = collect($encIds ?? [])->map(fn ($id) => (int) $id)->filter()->values();
@endphp

<div
  data-asignar-miembros
  data-coordinador="{{ $coordIds->implode(',') }}"
  data-encargado="{{ $encIds->implode(',') }}"
  class="space-y-4"
>
  <div class="grid gap-4 sm:grid-cols-2">
    <div>
      <p class="mb-2 text-sm font-medium text-ink">Coordinadores</p>
      <div data-chips="coordinador" class="flex min-h-10 flex-wrap content-start gap-1.5 rounded-lg border border-line bg-surface-2/60 px-2 py-2"></div>
    </div>
    <div>
      <p class="mb-2 text-sm font-medium text-ink">Encargados</p>
      <div data-chips="encargado" class="flex min-h-10 flex-wrap content-start gap-1.5 rounded-lg border border-line bg-surface-2/60 px-2 py-2"></div>
    </div>
  </div>

  <div data-inputs="coordinador"></div>
  <div data-inputs="encargado"></div>

  <div class="relative">
    <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
    <input
      type="search"
      class="input pl-9"
      data-asignar-buscar
      placeholder="Buscar miembro por nombre o cédula"
      autocomplete="off"
    >
  </div>

  <div data-asignar-lista class="max-h-64 overflow-y-auto rounded-lg border border-line"></div>
</div>
