<div data-piramide-vista data-piramide-editar="{{ $abrirPiramide ? '1' : '0' }}">
  <p class="mb-6 text-sm text-muted">Cada equipo tiene nombre y puede tener varios miembros. Debajo de cualquier equipo puedes agregar otro, sin límite de niveles.</p>

  @if ($piramide->isEmpty())
    <x-ui.card>
      <x-ui.empty icon="bi-diagram-3" title="Aún no hay equipos" text="Activa «Agregar equipo» para crear el primero y marcar a sus miembros.">
        @if ($puedeAgregar && $miembrosAsignacion->isNotEmpty())
          <div data-piramide-acciones @if (! $abrirPiramide) hidden @endif>
            <x-ui.button type="button" icon="bi-plus-lg" data-modal-open="dialogPiramideNodo" data-piramide-padre="" data-piramide-nombre="">Nuevo equipo</x-ui.button>
          </div>
        @elseif ($puedeAgregar)
          <x-ui.button href="{{ route('estructura.miembros') }}" variant="secondary">Ir a miembros</x-ui.button>
        @endif
      </x-ui.empty>
    </x-ui.card>
  @else
    <div class="overflow-auto rounded-xl border border-line bg-surface px-6 py-8">
      <div data-piramide-escala class="flex min-w-max origin-top items-start justify-center gap-10">
        @foreach ($piramide as $equipo)
          @include('estructura.partials.piramide-nodo', ['nodo' => $equipo])
        @endforeach
        @if ($puedeAgregar)
          <button
            type="button"
            data-piramide-acciones
            @if (! $abrirPiramide) hidden @endif
            class="self-start rounded-xl border border-dashed border-line px-4 py-8 text-sm text-muted transition hover:border-accent hover:text-accent"
            data-modal-open="dialogPiramideNodo"
            data-piramide-padre=""
            data-piramide-nombre=""
          >
            Nuevo equipo
          </button>
        @endif
      </div>
    </div>

    <div class="fixed bottom-6 right-6 z-20 flex flex-col gap-2">
      <x-ui.button type="button" variant="secondary" icon="bi-zoom-in" data-piramide-zoom="in" title="Acercar" aria-label="Acercar" />
      <x-ui.button type="button" variant="secondary" icon="bi-zoom-out" data-piramide-zoom="out" title="Alejar" aria-label="Alejar" />
    </div>
  @endif
</div>
