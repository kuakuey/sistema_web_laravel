<div class="grid gap-5 lg:grid-cols-2">
  <x-ui.card title="Plantilla">
    <p class="text-sm text-muted">Descarga el CSV con las columnas que usa el importador. Completa una fila por participante.</p>
    <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-muted">
      <li>Copia el nombre del evento y el tipo de entrada tal como están en Eventos.</li>
      <li>Si el tipo tiene prefijo, escribe solo el número en Numeración.</li>
      <li>Estado: <code>pagado</code> o <code>por_cancelar</code>.</li>
      <li>Forma de pago: <code>efectivo</code>, <code>transferencia</code> o vacío si queda pendiente.</li>
    </ul>
    <div class="mt-5">
      <x-ui.button href="{{ route('avanzado.importar.plantilla') }}" variant="secondary" icon="download">Descargar plantilla CSV</x-ui.button>
    </div>
  </x-ui.card>

  <x-ui.card title="Subir registros">
    <p class="text-sm text-muted">Importa participantes de eventos desde la plantilla. Se leen archivos <code>.csv</code>.</p>
    <form method="POST" action="{{ route('avanzado.importar') }}" enctype="multipart/form-data" class="mt-4 space-y-4" data-confirm="¿Importar los registros del archivo seleccionado?">
      @csrf
      <x-ui.field label="Archivo" name="archivo" required>
        <input class="input pt-2" type="file" id="archivo" name="archivo" accept=".csv,text/csv" required>
      </x-ui.field>
      <x-ui.button type="submit" icon="cloud-upload">Importar registros</x-ui.button>
    </form>
  </x-ui.card>
</div>

@if ($resultadoImport)
  <x-ui.card title="Resultado de la importación" class="mt-5">
    <div class="mb-4 flex flex-wrap gap-2">
      <x-ui.badge tone="success">{{ $resultadoImport['importados'] ?? 0 }} importado(s)</x-ui.badge>
      @if (($resultadoImport['omitidos'] ?? 0) > 0)
        <x-ui.badge tone="warning">{{ $resultadoImport['omitidos'] }} con error</x-ui.badge>
      @endif
    </div>

    @if (! empty($resultadoImport['errores']))
      <x-ui.table>
        <x-slot:head>
          <th>Fila</th>
          <th>Error</th>
        </x-slot:head>
        @foreach ($resultadoImport['errores'] as $error)
          <tr>
            <td class="tabular-nums">{{ $error['fila'] }}</td>
            <td>{{ $error['mensaje'] }}</td>
          </tr>
        @endforeach
      </x-ui.table>
    @elseif (($resultadoImport['importados'] ?? 0) > 0)
      <p class="text-sm text-muted">Todos los registros del archivo se importaron correctamente.</p>
    @endif
  </x-ui.card>
@endif
