@if (! $estadoBd)
  <x-ui.alert type="warning">No se pudo leer el estado de la base de datos.</x-ui.alert>
@else
  @if ($estadoBd['sistema_listo'])
    <x-ui.alert type="success" class="mb-6">Sistema listo. La base está conectada y todas las tablas están instaladas.</x-ui.alert>
  @elseif ($estadoBd['error'])
    <x-ui.alert type="danger" class="mb-6">{{ $estadoBd['error'] }}</x-ui.alert>
  @endif

  <div class="mb-6 grid gap-4 sm:grid-cols-3">
    <x-ui.card>
      <p class="text-xs text-muted">Servidor MySQL</p>
      <p class="mt-1 font-semibold {{ $estadoBd['servidor_conectado'] ? 'text-success' : 'text-danger' }}">
        {{ $estadoBd['servidor_conectado'] ? 'Conectado' : 'Sin conexión' }}
      </p>
    </x-ui.card>
    <x-ui.card>
      <p class="text-xs text-muted">Base de datos</p>
      <p class="mt-1 font-semibold {{ $estadoBd['bd_conectada'] ? 'text-success' : 'text-warning' }}">
        {{ $estadoBd['bd_conectada'] ? 'Conectada' : 'Pendiente' }}
      </p>
    </x-ui.card>
    <x-ui.card>
      <p class="text-xs text-muted">Tablas instaladas</p>
      <p class="mt-1 font-semibold">{{ $estadoBd['tablas_instaladas'] }}/{{ $estadoBd['total_tablas'] }}</p>
    </x-ui.card>
  </div>

  <div class="grid gap-5 lg:grid-cols-[20rem_minmax(0,1fr)]">
    <x-ui.card title="Conexión">
      <dl class="space-y-3 text-sm">
        <div>
          <dt class="text-xs text-muted">Host</dt>
          <dd class="font-medium">{{ $estadoBd['host'] }}{{ $estadoBd['puerto'] ? ':'.$estadoBd['puerto'] : '' }}</dd>
        </div>
        <div>
          <dt class="text-xs text-muted">Base de datos</dt>
          <dd class="font-medium">{{ $estadoBd['base_datos'] }}</dd>
        </div>
        <div>
          <dt class="text-xs text-muted">Usuario</dt>
          <dd class="font-medium">{{ $estadoBd['usuario'] }}</dd>
        </div>
        @if ($estadoBd['version_mysql'])
          <div>
            <dt class="text-xs text-muted">MySQL</dt>
            <dd>{{ $estadoBd['version_mysql'] }}</dd>
          </div>
        @endif
      </dl>
      <p class="mt-5 text-xs text-muted">El esquema se comparte con el sistema PHP. Aquí solo se consulta el estado; no se recrea ni se borra nada.</p>
    </x-ui.card>

    <x-ui.card title="Tablas" flush>
      <x-ui.table>
        <x-slot:head>
          <th>Tabla</th>
          <th>Estado</th>
          <th class="text-right">Registros</th>
        </x-slot:head>
        @foreach ($estadoBd['tablas'] as $tabla)
          <tr>
            <td class="font-medium">{{ $tabla['nombre'] }}</td>
            <td>
              <x-ui.badge :tone="$tabla['instalada'] ? 'success' : 'danger'">
                {{ $tabla['instalada'] ? 'OK' : 'Falta' }}
              </x-ui.badge>
            </td>
            <td class="text-right tabular-nums text-muted">{{ $tabla['registros'] !== null ? $tabla['registros'] : '—' }}</td>
          </tr>
        @endforeach
      </x-ui.table>
    </x-ui.card>
  </div>
@endif
