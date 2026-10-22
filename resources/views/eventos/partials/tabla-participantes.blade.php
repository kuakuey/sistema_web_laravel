@php
  $dinero = fn ($valor) => '$ '.number_format((float) $valor, 2);
  $esParticipantes = $pestaña === 'participantes';
  $mostrarNumeracion = $esParticipantes && ($eventoParticipantes?->requiere_numeracion ?? false);
  $actionFiltro = $esParticipantes ? route('eventos.participantes', $eventoParticipantes->slug()) : route('eventos.registros');
@endphp

@if ($esParticipantes && $eventoParticipantes)
  <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div>
      <p class="text-sm text-muted">
        <a href="{{ route('eventos.catalogo') }}" class="text-accent hover:underline">Eventos</a>
        <span class="text-muted"> / </span>
        {{ $eventoParticipantes->nombre }}
      </p>
      <p class="mt-1 text-sm text-muted">
        {{ optional($eventoParticipantes->fecha)->format('d/m/Y') ?: 'Sin fecha' }}
        @unless ($eventoParticipantes->habilitado)
          <x-ui.badge>Deshabilitado</x-ui.badge>
        @endunless
      </p>
    </div>
    <div class="flex gap-2">
      @if ($puedeVerInforme)
        <x-ui.button href="{{ route('eventos.informe.pdf', $eventoParticipantes) }}" variant="secondary" icon="bi-file-earmark-pdf">PDF</x-ui.button>
        <x-ui.button href="{{ route('eventos.informe.excel', $eventoParticipantes) }}" variant="secondary" icon="bi-file-earmark-excel">Excel</x-ui.button>
        <x-ui.button href="{{ route('eventos.informe', $eventoParticipantes) }}" variant="secondary" icon="bi-file-earmark-text">Informe</x-ui.button>
      @endif
      <x-ui.button href="{{ route('eventos.catalogo') }}" variant="secondary">Volver</x-ui.button>
    </div>
  </div>
@endif

<x-ui.filters>
<form method="GET" action="{{ $actionFiltro }}" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_10rem_10rem_auto]">
  <input class="input" type="search" name="buscar" value="{{ $buscar }}" placeholder="Buscar nombre, teléfono o numeración">
  <input class="input" type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" aria-label="Desde">
  <input class="input" type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" aria-label="Hasta">
  <x-ui.button type="submit" variant="secondary" icon="bi-funnel">Filtrar</x-ui.button>
</form>
</x-ui.filters>

<x-ui.card flush>
  @if (! $registros || $registros->count() === 0)
    <x-ui.empty icon="bi-inbox" title="No hay registros" text="Aún no hay participantes que coincidan con la búsqueda.">
      @if ($puedeRegistrar && $pestaña === 'registros')
        <x-ui.button type="button" icon="bi-plus-lg" data-modal-open="dialogNuevo">Nuevo registro</x-ui.button>
      @endif
    </x-ui.empty>
  @else
    <x-ui.table>
      <x-slot:head>
        <th>Nombre</th>
        @unless ($esParticipantes)<th>Evento</th>@endunless
        @if ($mostrarNumeracion)<th>Numeración</th>@endif
        <th>Tipo entrada</th>
        <th>Valor</th>
        <th>Pago</th>
        <th>Estado</th>
        @if ($esParticipantes)<th>Asistencia</th>@endif
        @unless ($esParticipantes)<th>Registrado</th>@endunless
        <th class="text-right">Acciones</th>
      </x-slot:head>
      @foreach ($registros as $registro)
        <tr>
          <td class="font-medium">{{ $registro->nombre }}</td>
          @unless ($esParticipantes)
            <td>
              @if ($registro->evento)
                <a class="text-accent hover:underline" href="{{ route('eventos.participantes', $registro->evento->slug()) }}">{{ $registro->evento->nombre }}</a>
              @else
                —
              @endif
            </td>
          @endunless
          @if ($mostrarNumeracion)
            <td class="tabular-nums">{{ $registro->numeracion ?: '—' }}</td>
          @endif
          <td>{{ $registro->tipo_entrada ?: '—' }}</td>
          <td class="tabular-nums">{{ $dinero($registro->valor) }}</td>
          <td>{{ \App\Support\Eventos::etiquetaForma($registro->forma_pago) }}</td>
          <td>
            <x-ui.badge :tone="$registro->estado_pago === 'pagado' ? 'success' : 'warning'">
              {{ \App\Support\Eventos::etiquetaPago($registro->estado_pago) }}
            </x-ui.badge>
          </td>
          @if ($esParticipantes)
            <td>
              <x-ui.badge :tone="$registro->asistio ? 'success' : 'neutral'">
                {{ $registro->asistio ? 'Asistió' : 'No asistió' }}
              </x-ui.badge>
            </td>
          @endif
          @unless ($esParticipantes)
            <td class="text-muted tabular-nums">{{ optional($registro->creado_en)->format('d/m/Y') }}</td>
          @endunless
          <td>
            <div class="flex items-center justify-end gap-1">
              <x-ui.ver :target="'detalle-participante-'.$registro->id" />
              @if ($puedeEditar)
                <form method="POST" action="{{ route('eventos.participantes.eliminar', $registro) }}" data-confirm="¿Eliminar este registro?">
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

@if ($registros)
  <div class="mt-5">{{ $registros->links() }}</div>

  @foreach ($registros as $registro)
    <x-ui.detalle
      :id="'detalle-participante-'.$registro->id"
      :title="'Registro #'.$registro->id"
      :filas="\App\Support\DetalleRegistro::participante($registro)"
      :campos="$puedeEditar ? \App\Support\DetalleRegistro::camposParticipante($registro) : []"
      :action="$puedeEditar ? route('eventos.participantes.actualizar', $registro) : null"
    />
  @endforeach
@endif
