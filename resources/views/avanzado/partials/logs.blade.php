@php
  $hayFiltros = collect($filtrosLog)->filter()->isNotEmpty();
  $puedeLimpiarTodo = $logs && ($logs->total() > 0 || $hayFiltros);
@endphp

<div class="mb-5 flex flex-wrap justify-end gap-2">
  @if ($hayFiltros && $logs && $logs->total() > 0)
    <form method="POST" action="{{ route('avanzado.logs.limpiar') }}" data-confirm="¿Eliminar los {{ $logs->total() }} registro(s) que coinciden con los filtros?">
      @csrf
      <input type="hidden" name="modo" value="filtrados">
      @foreach ($filtrosLog as $clave => $valor)
        <input type="hidden" name="{{ $clave }}" value="{{ $valor }}">
      @endforeach
      <x-ui.button type="submit" variant="secondary" icon="bi-funnel">Limpiar filtrados</x-ui.button>
    </form>
  @endif
  <form method="POST" action="{{ route('avanzado.logs.limpiar') }}" data-confirm="¿Eliminar TODO el log de actividad? Esta acción no se puede deshacer.">
    @csrf
    <input type="hidden" name="modo" value="todo">
    <x-ui.button type="submit" variant="danger" icon="bi-trash" :disabled="! $puedeLimpiarTodo">Limpiar todo</x-ui.button>
  </form>
</div>

<form method="GET" action="{{ route('avanzado.logs') }}" class="mb-5 grid gap-3 sm:grid-cols-[minmax(0,1fr)_12rem_10rem_10rem_10rem_auto]">
  <input class="input" type="search" name="buscar" value="{{ $filtrosLog['buscar'] }}" placeholder="Usuario, detalle…">
  <select class="input" name="accion" aria-label="Acción">
    <option value="">Todas las acciones</option>
    @foreach ($etiquetasAcciones as $clave => $etiqueta)
      <option value="{{ $clave }}" @selected($filtrosLog['accion'] === $clave)>{{ $etiqueta }}</option>
    @endforeach
  </select>
  <select class="input" name="seccion" aria-label="Sección">
    <option value="">Todas las secciones</option>
    @foreach ($etiquetasSeccionesLog as $clave => $etiqueta)
      <option value="{{ $clave }}" @selected($filtrosLog['seccion'] === $clave)>{{ $etiqueta }}</option>
    @endforeach
  </select>
  <input class="input" type="date" name="fecha_desde" value="{{ $filtrosLog['fecha_desde'] }}" aria-label="Desde">
  <input class="input" type="date" name="fecha_hasta" value="{{ $filtrosLog['fecha_hasta'] }}" aria-label="Hasta">
  <x-ui.button type="submit" variant="secondary" icon="bi-funnel">Filtrar</x-ui.button>
</form>

<x-ui.card flush>
  @if (! $logs || $logs->count() === 0)
    <x-ui.empty icon="bi-journal-text" title="Sin actividad" text="No hay registros con estos filtros." />
  @else
    <x-ui.table>
      <x-slot:head>
        <th>Fecha y hora</th>
        <th>Usuario</th>
        <th>Acción</th>
        <th>Sección</th>
        <th class="text-right">Detalle</th>
      </x-slot:head>
      @foreach ($logs as $fila)
        <tr>
          <td class="tabular-nums text-muted">{{ \Illuminate\Support\Carbon::parse($fila->creado_en)->format('d/m/Y H:i') }}</td>
          <td>{{ $fila->usuario_nombre ?: ($fila->usuario_login ?: '—') }}</td>
          <td><x-ui.badge>{{ \App\Support\Actividad::etiquetaAccion((string) $fila->accion) }}</x-ui.badge></td>
          <td>{{ $etiquetasSeccionesLog[$fila->seccion] ?? ($fila->seccion ?: '—') }}</td>
          <td>
            <div class="flex justify-end">
              <x-ui.ver :target="'detalle-actividad-'.$fila->id" />
            </div>
          </td>
        </tr>
      @endforeach
    </x-ui.table>
  @endif
</x-ui.card>

@if ($logs)
  <div class="mt-5">{{ $logs->links() }}</div>
  @foreach ($logs as $fila)
    <x-ui.detalle :id="'detalle-actividad-'.$fila->id" title="Detalle de actividad" :filas="\App\Support\DetalleRegistro::actividad($fila)" />
  @endforeach
@endif
