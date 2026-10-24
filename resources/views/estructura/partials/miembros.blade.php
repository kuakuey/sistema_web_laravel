<x-ui.filters>
<form method="GET" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_auto]">
  <div class="relative">
    <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
    <input class="input pl-9" type="search" name="buscar" value="{{ $buscar }}" placeholder="Nombre, cédula, celular o correo" aria-label="Buscar miembros">
  </div>
  <x-ui.button type="submit" variant="secondary" icon="funnel">Buscar</x-ui.button>
</form>
</x-ui.filters>

<x-ui.card flush>
  @if ($registros->count() === 0)
    <x-ui.empty icon="people" title="{{ $buscar !== '' ? 'No se encontraron miembros' : 'No hay miembros registrados' }}" text="{{ $buscar !== '' ? 'Prueba con otro nombre, cédula o celular.' : 'Agrega el primer miembro para armar la estructura.' }}">
      @if ($puedeAgregar && $buscar === '')
        <x-ui.button type="button" icon="plus-lg" data-modal-open="dialogNuevoMiembro">Nuevo miembro</x-ui.button>
      @endif
    </x-ui.empty>
  @else
    <x-ui.table>
      <x-slot:head>
        <th>Nombre</th>
        <th>Celular</th>
        <th class="text-right">Acciones</th>
      </x-slot:head>
      @foreach ($registros as $registro)
        <tr>
          <td class="font-medium">{{ $registro->nombreCompleto() }}</td>
          <td class="text-muted">{{ $registro->celular ?: '—' }}</td>
          <td>
            <div class="flex items-center justify-end gap-1">
              <x-ui.ver :target="'detalle-miembro-'.$registro->id" />
              @if ($puedeEditar)
                <form method="POST" action="{{ route('estructura.miembros.eliminar', $registro) }}" data-confirm="¿Eliminar este miembro? Esta acción no se puede deshacer.">
                  @csrf @method('DELETE')
                  <x-ui.button type="submit" size="sm" variant="danger" icon="trash" aria-label="Eliminar"></x-ui.button>
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
    :id="'detalle-miembro-'.$registro->id"
    :title="$registro->nombreCompleto()"
    :filas="\App\Support\DetalleRegistro::miembro($registro)"
    :campos="$puedeEditar ? \App\Support\DetalleRegistro::camposMiembro($registro) : []"
    :action="$puedeEditar ? route('estructura.miembros.actualizar', $registro) : null"
  />
@endforeach
