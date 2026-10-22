<x-ui.filters>
<form method="GET" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
  <div class="relative">
    <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" aria-hidden="true"></i>
    <input class="input pl-9" type="search" name="buscar" value="{{ $buscar }}" placeholder="Casa, ministerio, líder o dirección" aria-label="Buscar casas de vida">
  </div>
  <x-ui.button type="submit" variant="secondary" icon="bi-funnel">Buscar</x-ui.button>
</form>
</x-ui.filters>

@if ($territorios->isEmpty() || $miembros->isEmpty())
  <x-ui.alert type="warning" class="mb-5">
    Para agregar una casa de vida primero registra
    @if ($miembros->isEmpty())
      <a href="{{ route('estructura.miembros') }}" class="font-medium underline">miembros</a>
    @endif
    @if ($territorios->isEmpty() && $miembros->isEmpty()) y @endif
    @if ($territorios->isEmpty())
      <a href="{{ route('estructura.ministerios') }}" class="font-medium underline">ministerios de la categoría Territorio</a>
    @endif.
  </x-ui.alert>
@endif

<x-ui.card flush>
  @if ($registros->count() === 0)
    <x-ui.empty icon="bi-house-heart" title="{{ $buscar !== '' ? 'No se encontraron casas de vida' : 'No hay casas de vida registradas' }}" text="{{ $buscar !== '' ? 'Prueba con otro nombre, ministerio o líder.' : 'Crea una casa cuando ya existan miembros y ministerios de la categoría Territorio.' }}">
      @if ($puedeAgregar && $buscar === '' && $territorios->isNotEmpty() && $miembros->isNotEmpty())
        <x-ui.button type="button" icon="bi-plus-lg" data-modal-open="dialogNuevaCasa">Nueva casa</x-ui.button>
      @endif
    </x-ui.empty>
  @else
    <x-ui.table>
      <x-slot:head>
        <th>Casa</th>
        <th>Ministerio</th>
        <th>Líder</th>
        <th>Colaborador</th>
        <th>Anfitrión</th>
        <th>Dirección</th>
        <th class="text-right">Acciones</th>
      </x-slot:head>
      @foreach ($registros as $registro)
        <tr>
          <td class="font-medium">{{ $registro->nombre }}</td>
          <td>{{ $registro->territorio?->nombre ?: '—' }}</td>
          <td>{{ $registro->lider?->nombreCompleto() ?: '—' }}</td>
          <td class="text-muted">{{ $registro->colaborador?->nombreCompleto() ?: '—' }}</td>
          <td class="text-muted">{{ $registro->anfitrion?->nombreCompleto() ?: '—' }}</td>
          <td class="text-muted">{{ $registro->direccion }}</td>
          <td>
            <div class="flex items-center justify-end gap-1">
              <x-ui.ver :target="'detalle-casa-'.$registro->id" />
              @if ($puedeEditar)
                <form method="POST" action="{{ route('estructura.casas.eliminar', $registro) }}" data-confirm="¿Eliminar esta casa de vida? Esta acción no se puede deshacer.">
                  @csrf @method('DELETE')
                  <x-ui.button type="submit" size="sm" variant="danger" icon="bi-trash" aria-label="Eliminar"></x-ui.button>
                </form>
              @endif
            </div>
          </td>
        </tr>
      @endforeach
    </x-ui.table>
  @endif
</x-ui.card>

<div class="mt-5">{{ $registros->links() }}</div>

@foreach ($registros as $registro)
  <x-ui.detalle
    :id="'detalle-casa-'.$registro->id"
    :title="$registro->nombre"
    :filas="\App\Support\DetalleRegistro::casa($registro)"
    :campos="$puedeEditar ? \App\Support\DetalleRegistro::camposCasa($registro, $opcionesTerritorios, $opcionesMiembros) : []"
    :action="$puedeEditar ? route('estructura.casas.actualizar', $registro) : null"
  />
@endforeach
