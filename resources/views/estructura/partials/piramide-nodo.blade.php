@php
  $ministeriosEquipo = $nodo->miembros
    ->flatMap(fn ($asignacion) => $asignacion->miembro?->nombresMinisterios() ?? [])
    ->unique()
    ->values();
@endphp

<div class="flex flex-col items-center">
  <article class="w-60 rounded-xl border border-line bg-surface p-3 shadow-[0_1px_2px_0_rgb(15_23_42/0.04)]">
    <p class="mb-2 truncate text-sm font-semibold text-ink" title="{{ $nodo->nombre }}">{{ $nodo->nombre }}</p>

    <ul class="space-y-1.5">
      @forelse ($nodo->miembros as $asignacion)
        @php $persona = $asignacion->miembro; @endphp
        <li class="flex items-center gap-2 text-sm text-ink">
          <span class="grid size-6 shrink-0 place-items-center rounded-full bg-surface-2 text-[0.65rem] font-semibold text-muted">
            {{ $persona?->iniciales() ?: '—' }}
          </span>
          <span class="truncate">{{ $persona?->nombreCompleto() ?: 'Miembro #'.$asignacion->lider_id }}</span>
        </li>
      @empty
        <li class="text-xs text-muted">Sin miembros</li>
      @endforelse
    </ul>

    @if ($ministeriosEquipo->isNotEmpty())
      <div class="mt-3 border-t border-line pt-2 text-xs">
        <p class="font-medium text-ink">Ministerios</p>
        <ul class="mt-1 space-y-0.5 text-muted">
          @foreach ($ministeriosEquipo as $ministerio)
            <li>{{ $ministerio }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div class="mt-3 flex justify-end gap-1" data-piramide-acciones @if (! $abrirPiramide) hidden @endif>
      @if ($puedeAgregar)
        <x-ui.button
          type="button"
          size="sm"
          variant="ghost"
          icon="plus-lg"
          data-modal-open="dialogPiramideNodo"
          data-piramide-padre="{{ $nodo->id }}"
          data-piramide-nombre="{{ $nodo->nombre }}"
          title="Agregar equipo debajo"
          aria-label="Agregar equipo debajo"
        />
      @endif
      @if ($puedeEditar)
        <x-ui.button
          type="button"
          size="sm"
          variant="ghost"
          icon="pencil"
          data-modal-open="dialogPiramideNodo"
          data-piramide-id="{{ $nodo->id }}"
          data-piramide-padre="{{ $nodo->padre_id }}"
          data-piramide-nombre="{{ $nodo->nombre }}"
          data-piramide-miembros="{{ implode(',', $nodo->idsMiembros()) }}"
          title="Editar equipo"
          aria-label="Editar equipo"
        />
        <form method="POST" action="{{ route('estructura.piramide.eliminar', $nodo) }}" data-confirm="¿Quitar el equipo «{{ $nodo->nombre }}»? También se quitan los equipos que dependen de él.">
          @csrf @method('DELETE')
          <x-ui.button type="submit" size="sm" variant="danger" icon="trash" aria-label="Quitar"></x-ui.button>
        </form>
      @endif
    </div>
  </article>

  @if ($nodo->hijos->isNotEmpty())
    <div class="h-5 w-px bg-line" aria-hidden="true"></div>
    <div class="flex items-start justify-center gap-4">
      @foreach ($nodo->hijos as $hijo)
        @include('estructura.partials.piramide-nodo', ['nodo' => $hijo])
      @endforeach
    </div>
  @elseif ($puedeAgregar)
    <div data-piramide-acciones @if (! $abrirPiramide) hidden @endif class="flex flex-col items-center">
      <div class="h-5 w-px bg-line" aria-hidden="true"></div>
      <button
        type="button"
        class="rounded-lg border border-dashed border-line px-3 py-2 text-xs text-muted transition hover:border-accent hover:text-accent"
        data-modal-open="dialogPiramideNodo"
        data-piramide-padre="{{ $nodo->id }}"
        data-piramide-nombre="{{ $nodo->nombre }}"
      >
        Agregar equipo
      </button>
    </div>
  @endif
</div>
