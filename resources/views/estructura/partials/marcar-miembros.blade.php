<div
  data-marcar-miembros
  data-name="{{ $name ?? 'lider_ids[]' }}"
  data-seleccion="{{ collect($seleccion ?? [])->implode(',') }}"
  data-excluidos="{{ collect($excluidos ?? [])->implode(',') }}"
  class="space-y-4"
>
  <div>
    <p class="mb-2 text-sm font-medium text-ink">Seleccionados</p>
    <div data-marcar-chips class="flex min-h-10 flex-wrap content-start gap-1.5 rounded-lg border border-line bg-surface-2/60 px-2 py-2"></div>
  </div>
  <div data-marcar-inputs></div>
  <div class="relative">
    <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" aria-hidden="true"></i>
    <input type="search" class="input pl-9" data-marcar-buscar placeholder="Buscar miembro por nombre o cédula" autocomplete="off">
  </div>
  <div data-marcar-lista class="max-h-64 overflow-y-auto rounded-lg border border-line"></div>
</div>
