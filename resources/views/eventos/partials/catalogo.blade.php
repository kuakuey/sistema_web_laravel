<x-ui.card flush>
  @if ($catalogo->isEmpty())
    <x-ui.empty icon="calendar-event" title="No hay eventos" text="Agrega el primer evento al catálogo.">
      @if ($puedeAgregar)
        <x-ui.button type="button" icon="plus-lg" data-modal-open="dialogNuevoEvento">Agregar evento</x-ui.button>
      @endif
    </x-ui.empty>
  @else
    <x-ui.table>
      <x-slot:head>
        <th class="col-prio">Nombre</th>
        <th>Fecha</th>
        <th>Tipos de entrada</th>
        <th>Numeración</th>
        <th>Estado</th>
        <th>Registros</th>
        <th class="col-acciones text-right">Acciones</th>
      </x-slot:head>
      @foreach ($catalogo as $evento)
        <tr>
          <td class="col-prio font-medium">
            @if ($puedeVerParticipantes)
              <a class="text-accent hover:underline" href="{{ route('eventos.participantes', $evento->slug()) }}">{{ $evento->nombre }}</a>
            @else
              {{ $evento->nombre }}
            @endif
          </td>
          <td class="tabular-nums">{{ optional($evento->fecha)->format('d/m/Y') ?: '—' }}</td>
          <td class="celda-wrap">{{ \App\Support\Eventos::formatearTipos($evento) }}</td>
          <td>{{ $evento->requiere_numeracion ? 'Sí' : 'No' }}</td>
          <td>
            <x-ui.badge :tone="$evento->habilitado ? 'success' : 'neutral'">
              {{ $evento->habilitado ? 'Habilitado' : 'Deshabilitado' }}
            </x-ui.badge>
          </td>
          <td class="tabular-nums">{{ $evento->registros_count }}</td>
          <td class="col-acciones">
            <div class="flex items-center justify-end gap-1">
              @if ($puedeVerInforme)
                <x-ui.button href="{{ route('eventos.informe.pdf', $evento) }}" size="sm" variant="ghost" icon="file-earmark-pdf" aria-label="Descargar PDF"></x-ui.button>
                <x-ui.button href="{{ route('eventos.informe.excel', $evento) }}" size="sm" variant="ghost" icon="file-earmark-excel" aria-label="Descargar Excel"></x-ui.button>
                <x-ui.button href="{{ route('eventos.informe', $evento) }}" size="sm" variant="ghost" icon="file-earmark-text" aria-label="Informe"></x-ui.button>
              @endif
              @if ($puedeAgregar)
                <x-ui.button type="button" size="sm" variant="ghost" icon="pencil" :data-modal-open="'editar-evento-'.$evento->id" aria-label="Editar"></x-ui.button>
              @endif
              @if ($puedeEditar)
                <form method="POST" action="{{ route('eventos.vaciar', $evento) }}" data-confirm="¿Eliminar todos los registros de este evento? El evento se conserva.">
                  @csrf @method('DELETE')
                  <x-ui.button type="submit" size="sm" variant="ghost" icon="eraser" aria-label="Vaciar registros"></x-ui.button>
                </form>
                <form method="POST" action="{{ route('eventos.eliminar', $evento) }}" data-ajax-remove-row data-confirm="¿Eliminar este evento y todos sus registros?">
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

@if ($puedeAgregar)
  @foreach ($catalogo as $evento)
    <x-ui.modal :id="'editar-evento-'.$evento->id" :title="'Editar '.$evento->nombre" size="xl">
      <form id="form-editar-evento-{{ $evento->id }}" method="POST" action="{{ route('eventos.actualizar', $evento) }}" class="space-y-6" data-catalogo-evento>
        @csrf
        @method('PUT')
        @include('eventos.partials.form-catalogo', ['evento' => $evento])
      </form>
      <x-slot:footer>
        <x-ui.button type="button" variant="secondary" data-modal-close>Cerrar</x-ui.button>
        <x-ui.button type="submit" icon="check2" :form="'form-editar-evento-'.$evento->id">Guardar</x-ui.button>
      </x-slot:footer>
    </x-ui.modal>
  @endforeach
@endif
