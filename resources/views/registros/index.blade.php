@extends('layouts.app')

@section('content')
@php
  $etiquetas = \App\Support\Permisos::etiquetas();
  $esPresentaciones = $seccion === 'presentaciones';
  $itemActual = collect($menuSeccion ?? [])->firstWhere('clave', $pestaña);
  $tituloSeccion = $itemActual['etiqueta'] ?? $titulo;
  $subtitulo = match (true) {
      $seccion === 'generales' => 'Registros ingresados hoy, '.now()->locale('es')->isoFormat('dddd D [de] MMMM'),
      default => $itemActual['texto'] ?? 'Consulta de registros',
  };
  $abrirFormulario = $errors->any();
@endphp

<x-ui.page-header :title="$tituloSeccion" :subtitle="$subtitulo">
  <x-slot:actions>
    @if (! $errorBd)
      <x-ui.badge tone="accent">{{ $registros->total() }} resultado(s)</x-ui.badge>
    @endif
    @if ($puedeRegistrar && ! $errorBd)
      <x-ui.button type="button" icon="plus-lg" data-modal-open="dialogNuevo">
        {{ $esPresentaciones ? 'Nueva presentación' : 'Nuevo registro' }}
      </x-ui.button>
    @endif
  </x-slot:actions>
</x-ui.page-header>

@if (session('ok'))
  <x-ui.alert type="success" dismissible class="mb-6">{{ session('ok') }}</x-ui.alert>
@endif

@if ($errors->any())
  <x-ui.alert type="danger" dismissible class="mb-6">{{ $errors->first() }}</x-ui.alert>
@endif

@if ($errorBd)
  <x-ui.alert type="warning">{{ $errorBd }}</x-ui.alert>
@else
  @if ($seccion !== 'generales')
    <x-ui.filters>
    <form method="GET" class="grid gap-3 sm:grid-cols-[minmax(0,1fr)_10rem_10rem_auto]">
      <div class="relative">
        <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
        <input class="input pl-9" type="search" name="buscar" value="{{ $buscar }}" placeholder="Buscar por nombre, teléfono o correo" aria-label="Buscar">
      </div>
      <input class="input" type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" aria-label="Desde">
      <input class="input" type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" aria-label="Hasta">
      <x-ui.button type="submit" variant="secondary" icon="funnel">Filtrar</x-ui.button>
    </form>
    </x-ui.filters>
  @endif

  <x-ui.card flush>
    @if ($registros->count() === 0)
      <x-ui.empty icon="inbox" title="No hay registros para mostrar" text="Prueba con otros filtros o crea un registro nuevo.">
        @if ($puedeRegistrar)
          <x-ui.button type="button" icon="plus-lg" data-modal-open="dialogNuevo">
            {{ $esPresentaciones ? 'Nueva presentación' : 'Nuevo registro' }}
          </x-ui.button>
        @endif
      </x-ui.empty>
    @else
      <x-ui.table>
        <x-slot:head>
          @if ($seccion === 'generales')
            <th>Sección</th><th>Nombre</th><th>Contacto</th><th>Hora</th><th class="text-right">Acciones</th>
          @elseif ($esPresentaciones)
            <th>Niño</th><th>Contactos</th><th>Progreso</th><th>Fecha</th><th class="text-right">Acciones</th>
          @else
            <th>Nombre</th><th>Teléfono</th>
            @if ($seccion === 'conexion')<th>Zona</th><th>Estado</th>@endif
            @if ($seccion === 'bautismo')<th>Estado</th>@endif
            <th>Registrado</th><th class="text-right">Acciones</th>
          @endif
        </x-slot:head>

        @foreach ($registros as $registro)
          @if ($seccion === 'generales')
            <tr>
              <td><x-ui.badge>{{ $etiquetas[$registro['seccion']] ?? $registro['seccion'] }}</x-ui.badge></td>
              <td class="font-medium">{{ $registro['nombre'] }}</td>
              <td @class(['celda-wrap' => ($registro['origen'] ?? '') === 'presentacion'])>
                @if (($registro['origen'] ?? '') === 'presentacion' && isset($registro['modelo']))
                  {!! \App\Support\DetalleRegistro::contactosPresentacion($registro['modelo']) !!}
                @else
                  <span class="text-muted">{{ $registro['detalle'] }}</span>
                @endif
              </td>
              <td class="text-muted tabular-nums">{{ optional($registro['creado_en'])->format('H:i') }}</td>
              <td>
                <div class="flex items-center justify-end">
                  <x-ui.ver :target="'detalle-general-'.$registro['origen'].'-'.$registro['id']" />
                </div>
              </td>
            </tr>
          @elseif ($esPresentaciones)
            <tr>
              <td>
                <p class="font-medium">{{ $registro->nombre_presentado }}</p>
                @php $edadNino = \App\Support\DetalleRegistro::edad($registro->fecha_nacimiento); @endphp
                @if ($edadNino !== '—')
                  <p class="mt-0.5 text-xs text-muted">{{ $edadNino }}</p>
                @endif
              </td>
              <td class="celda-wrap">{!! \App\Support\DetalleRegistro::contactosPresentacion($registro) !!}</td>
              <td>
                <x-ui.progreso-presentacion
                  :registro="$registro"
                  :editable="$registro->estado !== 'presentado'"
                />
              </td>
              <td class="text-muted tabular-nums">{{ optional($registro->creado_en)->format('d/m/Y') }}</td>
              <td>
                <div class="flex items-center justify-end gap-1">
                  @if ($registro->estado === 'presentado' && $puedeEditar)
                    <form method="POST" action="{{ route('presentaciones.estado', $registro) }}" data-confirm="¿Restablecer el estado de esta presentación?">
                      @csrf
                      <input type="hidden" name="accion" value="restablecer">
                      <x-ui.button type="submit" size="sm" variant="ghost" icon="arrow-counterclockwise" title="Restablecer" aria-label="Restablecer" />
                    </form>
                  @endif
                  <x-ui.ver :target="'detalle-presentacion-'.$registro->id" />
                  @if ($puedeEditar)
                    <form method="POST" action="{{ route('presentaciones.eliminar', $registro) }}" data-confirm="¿Eliminar esta presentación? Esta acción no se puede deshacer.">
                      @csrf @method('DELETE')
                      <x-ui.button type="submit" size="sm" variant="danger" icon="trash" aria-label="Eliminar"></x-ui.button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @else
            <tr>
              <td>
                <p class="font-medium">{{ $registro->nombre }} {{ $registro->apellido }}</p>
                @if ($registro->email)
                  <p class="mt-0.5 text-xs text-muted">{{ $registro->email }}</p>
                @endif
              </td>
              <td class="text-muted tabular-nums">{{ $registro->celular }}</td>
              @if ($seccion === 'conexion')
                <td>{{ $zonas[$registro->zona] ?? ($registro->zona ?: '—') }}</td>
                <td>
                  <x-ui.badge :tone="$registro->contactado ? 'success' : 'warning'">
                    {{ $registro->contactado ? 'Contactado' : 'Pendiente' }}
                  </x-ui.badge>
                </td>
              @endif
              @if ($seccion === 'bautismo')
                <td>
                  <x-ui.badge :tone="$registro->estado_bautismo === 'bautizado' ? 'success' : 'neutral'">
                    {{ $registro->estado_bautismo === 'bautizado' ? 'Bautizado' : 'Ingresado' }}
                  </x-ui.badge>
                </td>
              @endif
              <td class="text-muted tabular-nums">{{ optional($registro->creado_en)->format('d/m/Y H:i') }}</td>
              <td>
                <div class="flex items-center justify-end gap-1">
                  @if ($seccion === 'conexion' && ! $registro->contactado && $puedeEstadoConexion)
                    <form method="POST" action="{{ route('registros.estado', $registro) }}">
                      @csrf
                      <input type="hidden" name="accion" value="contactado">
                      <x-ui.button type="submit" size="sm" variant="success" icon="check2">Contactado</x-ui.button>
                    </form>
                  @endif
                  @if ($seccion === 'bautismo' && $registro->estado_bautismo !== 'bautizado')
                    <form method="POST" action="{{ route('registros.estado', $registro) }}">
                      @csrf
                      <input type="hidden" name="accion" value="bautizado">
                      <x-ui.button type="submit" size="sm" variant="success" icon="check2">Bautizado</x-ui.button>
                    </form>
                  @endif
                  @if ($puedeEditar && (($seccion === 'bautismo' && $registro->estado_bautismo === 'bautizado') || ($seccion === 'conexion' && $registro->contactado)))
                    <form method="POST" action="{{ route('registros.estado', $registro) }}" data-confirm="¿Restablecer el estado de este registro?">
                      @csrf
                      <input type="hidden" name="accion" value="restablecer">
                      <x-ui.button type="submit" size="sm" variant="ghost" icon="arrow-counterclockwise">Restablecer</x-ui.button>
                    </form>
                  @endif
                  <x-ui.ver :target="'detalle-inscripcion-'.$registro->id" />
                  @if ($puedeEditar)
                    <form method="POST" action="{{ route('registros.eliminar', $registro) }}" data-confirm="¿Eliminar este registro? Esta acción no se puede deshacer.">
                      @csrf @method('DELETE')
                      <x-ui.button type="submit" size="sm" variant="danger" icon="trash" aria-label="Eliminar"></x-ui.button>
                    </form>
                  @endif
                </div>
              </td>
            </tr>
          @endif
        @endforeach
      </x-ui.table>
    @endif
  </x-ui.card>

  <div class="mt-5">{{ $registros->links() }}</div>

  @foreach ($registros as $registro)
    @if ($seccion === 'generales')
      <x-ui.detalle
        :id="'detalle-general-'.$registro['origen'].'-'.$registro['id']"
        :title="\App\Support\DetalleRegistro::tituloGeneral($registro)"
        :filas="\App\Support\DetalleRegistro::general($registro)"
        :campos="$puedeEditar ? \App\Support\DetalleRegistro::camposGeneral($registro) : []"
        :action="$puedeEditar && ($registro['origen'] ?? '') === 'inscripcion' && isset($registro['modelo'])
            ? route('registros.actualizar', $registro['modelo'])
            : ($puedeEditar && ($registro['origen'] ?? '') === 'presentacion' && isset($registro['modelo'])
                ? route('presentaciones.actualizar', $registro['modelo'])
                : null)"
      />
    @elseif ($esPresentaciones)
      <x-ui.detalle
        :id="'detalle-presentacion-'.$registro->id"
        :title="'Presentación #'.$registro->id"
        :filas="\App\Support\DetalleRegistro::presentacion($registro)"
        :campos="$puedeEditar ? \App\Support\DetalleRegistro::camposPresentacion($registro) : []"
        :action="$puedeEditar ? route('presentaciones.actualizar', $registro) : null"
      />
    @else
      <x-ui.detalle
        :id="'detalle-inscripcion-'.$registro->id"
        :title="'Inscripción #'.$registro->id"
        :filas="\App\Support\DetalleRegistro::inscripcion($registro)"
        :campos="$puedeEditar ? \App\Support\DetalleRegistro::camposInscripcion($registro) : []"
        :action="$puedeEditar ? route('registros.actualizar', $registro) : null"
      />
    @endif
  @endforeach

  @if ($puedeRegistrar)
    <x-ui.modal
      id="dialogNuevo"
      :title="$esPresentaciones ? 'Nueva presentación' : 'Nuevo registro'"
      description="Los campos con * son obligatorios."
      :open="$abrirFormulario"
      :size="$esPresentaciones ? 'xl' : 'lg'"
    >
      @if ($esPresentaciones)
        <form id="formNuevoPresentacion" method="POST" action="{{ route('presentaciones.guardar') }}" class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
          @csrf
          <x-ui.field label="Parentesco" name="parentesco_representante_1" required>
            <select class="input" id="parentesco_representante_1" name="parentesco_representante_1" required>
              <option value="">Seleccionar…</option>
              @foreach (\App\Support\Permisos::parentescos() as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('parentesco_representante_1') === $valor)>{{ $etiqueta }}</option>
              @endforeach
            </select>
          </x-ui.field>
          <x-ui.field label="Representante legal" name="nombre_padre" required>
            <input class="input" id="nombre_padre" name="nombre_padre" value="{{ old('nombre_padre') }}" required maxlength="100" autofocus>
          </x-ui.field>
          <x-ui.field label="Teléfono" name="telefono_papa" required>
            <input class="input" id="telefono_papa" name="telefono_papa" value="{{ old('telefono_papa') }}" required maxlength="30" inputmode="tel">
          </x-ui.field>

          <x-ui.field label="Segundo parentesco" name="parentesco_representante_2">
            <select class="input" id="parentesco_representante_2" name="parentesco_representante_2">
              <option value="">Seleccionar…</option>
              @foreach (\App\Support\Permisos::parentescos() as $valor => $etiqueta)
                <option value="{{ $valor }}" @selected(old('parentesco_representante_2') === $valor)>{{ $etiqueta }}</option>
              @endforeach
            </select>
          </x-ui.field>
          <x-ui.field label="Segundo representante" name="nombre_madre">
            <input class="input" id="nombre_madre" name="nombre_madre" value="{{ old('nombre_madre') }}" maxlength="100">
          </x-ui.field>
          <x-ui.field label="Teléfono" name="telefono_mama">
            <input class="input" id="telefono_mama" name="telefono_mama" value="{{ old('telefono_mama') }}" maxlength="30" inputmode="tel">
          </x-ui.field>

          <x-ui.field label="Niño presentado" name="nombre_presentado" required class="sm:col-span-2">
            <input class="input" id="nombre_presentado" name="nombre_presentado" value="{{ old('nombre_presentado') }}" required maxlength="100">
          </x-ui.field>
          <x-ui.field label="Fecha de nacimiento" name="fecha_nacimiento">
            <input type="date" class="input" id="fecha_nacimiento" name="fecha_nacimiento" value="{{ old('fecha_nacimiento') }}">
          </x-ui.field>
        </form>
      @else
        <form id="formNuevoRegistro" method="POST" action="{{ route('registros.guardar') }}" class="grid gap-5 sm:grid-cols-2">
          @csrf
          <input type="hidden" name="seccion" value="{{ $seccion }}">
          <input type="hidden" name="tipo_formulario" value="{{ $seccion }}">

          <x-ui.field label="Nombre" name="nombre" required>
            <input class="input" id="nombre" name="nombre" value="{{ old('nombre') }}" required maxlength="100" autocomplete="off" autofocus>
          </x-ui.field>
          <x-ui.field label="Apellido" name="apellido" required>
            <input class="input" id="apellido" name="apellido" value="{{ old('apellido') }}" required maxlength="100" autocomplete="off">
          </x-ui.field>
          <x-ui.field label="Teléfono" name="celular" required>
            <input class="input" id="celular" name="celular" value="{{ old('celular') }}" required maxlength="30" inputmode="tel">
          </x-ui.field>
          <x-ui.field label="Correo electrónico" name="email">
            <input type="email" class="input" id="email" name="email" value="{{ old('email') }}" maxlength="100">
          </x-ui.field>

          @if ($seccion === 'conexion')
            <x-ui.field label="Zona" name="zona" required>
              <select class="input" id="zona" name="zona" required>
                <option value="">Seleccionar…</option>
                @foreach ($zonas as $slug => $etiqueta)
                  <option value="{{ $slug }}" @selected(old('zona') === $slug)>{{ $etiqueta }}</option>
                @endforeach
              </select>
            </x-ui.field>
            <x-ui.field label="Dirección" name="direccion" required class="sm:col-span-2">
              <textarea class="input" id="direccion" name="direccion" rows="2" maxlength="255" required>{{ old('direccion') }}</textarea>
            </x-ui.field>
          @endif
        </form>
      @endif
      <x-slot:footer>
        <x-ui.button type="button" variant="secondary" data-modal-close>Cancelar</x-ui.button>
        <x-ui.button type="submit" form="{{ $esPresentaciones ? 'formNuevoPresentacion' : 'formNuevoRegistro' }}" icon="check2">
          {{ $esPresentaciones ? 'Guardar presentación' : 'Guardar registro' }}
        </x-ui.button>
      </x-slot:footer>
    </x-ui.modal>
  @endif
@endif
@endsection
