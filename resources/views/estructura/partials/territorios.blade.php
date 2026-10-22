<x-ui.filters>
<form method="GET" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
  <div class="relative">
    <i class="bi bi-search pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" aria-hidden="true"></i>
    <input class="input pl-9" type="search" name="buscar" value="{{ $buscar }}" placeholder="Categoría o ministerio" aria-label="Buscar ministerios">
  </div>
  <x-ui.button type="submit" variant="secondary" icon="bi-funnel">Buscar</x-ui.button>
</form>
</x-ui.filters>

<x-ui.card flush>
  @if ($registros->count() === 0)
    <x-ui.empty icon="bi-map" title="{{ $buscar !== '' ? 'No se encontraron ministerios' : 'No hay ministerios registrados' }}" text="{{ $buscar !== '' ? 'Prueba con otro nombre.' : 'Crea un ministerio para agrupar casas de vida.' }}">
      @if ($puedeAgregar && $buscar === '')
        <x-ui.button type="button" icon="bi-plus-lg" data-modal-open="dialogNuevoTerritorio">Nuevo ministerio</x-ui.button>
      @endif
    </x-ui.empty>
  @else
    <x-ui.table>
      <x-slot:head>
        <th>Categoría</th>
        <th>Ministerio</th>
        <th>Coordinadores</th>
        <th>Encargados</th>
        <th class="text-right">Acciones</th>
      </x-slot:head>
      @foreach ($registros as $registro)
        @php
          $coordinadores = $registro->nombresRol('coordinador');
          $encargados = $registro->nombresRol('encargado');
        @endphp
        <tr>
          <td class="text-muted">{{ $registro->nombreCategoria() }}</td>
          <td class="font-medium">{{ $registro->nombre }}</td>
          <td class="text-muted">{{ $coordinadores ? implode(', ', $coordinadores) : 'Sin asignar' }}</td>
          <td class="text-muted">{{ $encargados ? implode(', ', $encargados) : 'Sin asignar' }}</td>
          <td>
            <div class="flex items-center justify-end gap-1">
              <x-ui.ver :target="'detalle-territorio-'.$registro->id" />
              @if ($puedeEditar)
                <form method="POST" action="{{ route('estructura.territorios.eliminar', $registro) }}" data-confirm="¿Eliminar este ministerio? Esta acción no se puede deshacer.">
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
  @php
    $detalleId = 'detalle-territorio-'.$registro->id;
    $usarOld = old('_detalle') === $detalleId;
  @endphp
  <x-ui.detalle
    :id="$detalleId"
    :title="$registro->nombre"
    :filas="\App\Support\DetalleRegistro::territorio($registro)"
    :campos="$puedeEditar ? \App\Support\DetalleRegistro::camposTerritorio($registro, $opcionesCategorias ?? []) : []"
    :action="$puedeEditar ? route('estructura.territorios.actualizar', $registro) : null"
  >
    @if ($puedeEditar && $miembrosAsignacion->isNotEmpty())
      <x-slot:extra>
        @include('estructura.partials.asignar-miembros', [
          'coordIds' => $usarOld ? old('coordinador_ids', []) : $registro->idsRol('coordinador'),
          'encIds' => $usarOld ? old('encargado_ids', []) : $registro->idsRol('encargado'),
        ])
      </x-slot:extra>
    @endif
  </x-ui.detalle>
@endforeach
