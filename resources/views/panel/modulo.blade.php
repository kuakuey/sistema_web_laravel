@extends('layouts.app')

@section('content')
@php
  $conFormulario = in_array($seccion, ['ofrendas', 'consejeria', 'valores_adicionales'], true);
  $soloLectura = in_array($seccion, ['eventos', 'calendario', 'networking'], true);
  $dinero = fn ($valor) => '$ '.number_format((float) $valor, 2);
  $abrirFormulario = $errors->any();
  $etiquetaNuevo = match ($seccion) {
      'ofrendas' => 'Nueva ofrenda',
      'consejeria' => 'Nueva solicitud',
      'valores_adicionales' => 'Nuevo valor',
      default => 'Nuevo registro',
  };
@endphp

<x-ui.page-header :title="$titulo" :subtitle="$soloLectura ? 'Consulta de los datos actuales del sistema' : 'Consulta y registro de información'">
  <x-slot:actions>
    @if ($registros)
      <x-ui.badge tone="accent">{{ $registros->total() }} resultado(s)</x-ui.badge>
    @endif
    @if ($puedeRegistrar && ! $errorBd)
      <x-ui.button type="button" icon="plus-lg" data-modal-open="dialogNuevo">{{ $etiquetaNuevo }}</x-ui.button>
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
  <x-ui.alert type="warning" class="mb-6">{{ $errorBd }}</x-ui.alert>
@endif

@if ($registros)
  <x-ui.filters>
  <form method="GET" class="grid gap-3 {{ $seccion === 'valores_adicionales' ? 'sm:grid-cols-[minmax(0,1fr)_10rem_10rem_auto]' : 'sm:grid-cols-[minmax(0,1fr)_auto]' }}">
    <div class="relative">
      <x-ui.icon name="search" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
      <input class="input pl-9" type="search" name="buscar" value="{{ $buscar }}" placeholder="Buscar" aria-label="Buscar">
    </div>
    @if ($seccion === 'valores_adicionales')
      <input class="input" type="date" name="fecha_desde" value="{{ request('fecha_desde') }}" aria-label="Desde">
      <input class="input" type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}" aria-label="Hasta">
    @endif
    <x-ui.button type="submit" variant="secondary" icon="funnel">Filtrar</x-ui.button>
  </form>
  </x-ui.filters>

  <x-ui.card flush>
    @if ($registros->count() === 0)
      <x-ui.empty icon="inbox" title="No hay registros" text="Aún no hay información que coincida con tu búsqueda.">
        @if ($puedeRegistrar)
          <x-ui.button type="button" icon="plus-lg" data-modal-open="dialogNuevo">{{ $etiquetaNuevo }}</x-ui.button>
        @endif
      </x-ui.empty>
    @else
      <x-ui.table>
        <x-slot:head>
          @if ($seccion === 'ofrendas')
            <th>Fecha</th><th>Casa de vida</th><th>Líder</th><th class="text-right">Monto</th><th class="text-right">Acciones</th>
          @elseif ($seccion === 'consejeria')
            <th>Nombre</th><th>Teléfono</th><th>Tipo</th><th>Cita</th><th>Registrado</th><th class="text-right">Acciones</th>
          @elseif ($seccion === 'valores_adicionales')
            <th>Fecha</th><th>Tipo</th><th>Nombre</th><th>Teléfono</th><th class="text-right">Valor</th><th class="text-right">Acciones</th>
          @elseif ($seccion === 'networking')
            <th>Nombre</th><th>Empresa</th><th>Categoría</th><th>WhatsApp</th><th>Visible</th><th class="text-right">Acciones</th>
          @elseif ($seccion === 'eventos')
            <th>Nombre</th><th>Fecha</th><th class="text-right">Valor</th><th>Estado</th><th class="text-right">Acciones</th>
          @elseif ($seccion === 'calendario')
            <th>Título</th><th>Inicio</th><th>Fin</th><th>Estado</th><th class="text-right">Acciones</th>
          @endif
        </x-slot:head>

        @foreach ($registros as $registro)
          <tr>
            @if ($seccion === 'ofrendas')
              <td class="text-muted tabular-nums">{{ optional($registro->fecha_ofrenda)->format('d/m/Y') }}</td>
              <td class="font-medium">{{ $registro->casa_vida ?: '—' }}</td>
              <td>{{ $registro->lider ?: '—' }}</td>
              <td class="text-right font-medium tabular-nums">{{ $dinero($registro->monto) }}</td>
              <td>
                <div class="flex items-center justify-end gap-1">
                  <x-ui.ver :target="'detalle-ofrenda-'.$registro->id" />
                  @if ($puedeEditar)
                    <form method="POST" action="{{ route('ofrendas.eliminar', $registro) }}" data-confirm="¿Eliminar esta ofrenda? Esta acción no se puede deshacer.">
                      @csrf @method('DELETE')
                      <x-ui.button type="submit" size="sm" variant="danger" icon="trash" aria-label="Eliminar"></x-ui.button>
                    </form>
                  @endif
                </div>
              </td>
            @elseif ($seccion === 'consejeria')
              <td class="font-medium">{{ $registro->nombre_completo }}</td>
              <td class="text-muted tabular-nums">{{ $registro->telefono }}</td>
              <td><x-ui.badge tone="accent">{{ $tiposConsejeria[$registro->tipo_consejeria] ?? $registro->tipo_consejeria }}</x-ui.badge></td>
              <td class="text-muted tabular-nums">
                @if ($registro->cita_fecha)
                  {{ $registro->cita_fecha->format('d/m/Y') }} {{ substr((string) $registro->cita_hora, 0, 5) }}
                @else
                  <x-ui.badge tone="warning">Sin cita</x-ui.badge>
                @endif
              </td>
              <td class="text-muted tabular-nums">{{ optional($registro->creado_en)->format('d/m/Y') }}</td>
              <td>
                <div class="flex items-center justify-end gap-1">
                  <x-ui.ver :target="'detalle-consejeria-'.$registro->id" />
                  @if ($puedeEditar)
                    <form method="POST" action="{{ route('consejeria.eliminar', $registro) }}" data-confirm="¿Eliminar esta consejería? Esta acción no se puede deshacer.">
                      @csrf @method('DELETE')
                      <x-ui.button type="submit" size="sm" variant="danger" icon="trash" aria-label="Eliminar"></x-ui.button>
                    </form>
                  @endif
                </div>
              </td>
            @elseif ($seccion === 'valores_adicionales')
              <td class="text-muted tabular-nums">{{ optional($registro->fecha)->format('d/m/Y') }}</td>
              <td><x-ui.badge>{{ $registro->tipo }}</x-ui.badge></td>
              <td class="font-medium">{{ $registro->nombre }}</td>
              <td class="text-muted tabular-nums">{{ $registro->telefono }}</td>
              <td class="text-right font-medium tabular-nums">{{ $dinero($registro->valor) }}</td>
              <td>
                <div class="flex items-center justify-end">
                  <x-ui.ver :target="'detalle-valor-'.$registro->id" />
                </div>
              </td>
            @elseif ($seccion === 'networking')
              <td class="font-medium">{{ $registro->nombre }}</td>
              <td>{{ $registro->empresa }}</td>
              <td><x-ui.badge>{{ $registro->categoria }}</x-ui.badge></td>
              <td class="text-muted tabular-nums">{{ $registro->whatsapp }}</td>
              <td><x-ui.badge :tone="$registro->visible ? 'success' : 'neutral'">{{ $registro->visible ? 'Visible' : 'Oculto' }}</x-ui.badge></td>
              <td>
                <div class="flex items-center justify-end">
                  <x-ui.ver :target="'detalle-networking-'.$registro->id" />
                </div>
              </td>
            @elseif ($seccion === 'eventos')
              <td class="font-medium">{{ $registro->nombre }}</td>
              <td class="text-muted tabular-nums">{{ optional($registro->fecha)->format('d/m/Y') ?: '—' }}</td>
              <td class="text-right tabular-nums">{{ $dinero($registro->valor) }}</td>
              <td><x-ui.badge :tone="$registro->habilitado ? 'success' : 'neutral'">{{ $registro->habilitado ? 'Habilitado' : 'Deshabilitado' }}</x-ui.badge></td>
              <td>
                <div class="flex items-center justify-end">
                  <x-ui.ver :target="'detalle-evento-'.$registro->id" />
                </div>
              </td>
            @elseif ($seccion === 'calendario')
              <td class="font-medium">{{ $registro->titulo }}</td>
              <td class="text-muted tabular-nums">{{ optional($registro->fecha)->format('d/m/Y') }}</td>
              <td class="text-muted tabular-nums">{{ optional($registro->fecha_fin)->format('d/m/Y') ?: '—' }}</td>
              <td><x-ui.badge :tone="$registro->activo ? 'success' : 'neutral'">{{ $registro->activo ? 'Activo' : 'Inactivo' }}</x-ui.badge></td>
              <td>
                <div class="flex items-center justify-end">
                  <x-ui.ver :target="'detalle-calendario-'.$registro->id" />
                </div>
              </td>
            @endif
          </tr>
        @endforeach
      </x-ui.table>
    @endif
  </x-ui.card>

  <div class="mt-5">{{ $registros->links() }}</div>

  @foreach ($registros as $registro)
    @if ($seccion === 'ofrendas')
      <x-ui.detalle
        :id="'detalle-ofrenda-'.$registro->id"
        :title="'Ofrenda #'.$registro->id"
        :filas="\App\Support\DetalleRegistro::ofrenda($registro)"
        :campos="$puedeEditar ? \App\Support\DetalleRegistro::camposOfrenda($registro, $opcionesCasasOfrenda ?? []) : []"
        :action="$puedeEditar ? route('ofrendas.actualizar', $registro) : null"
      />
    @elseif ($seccion === 'consejeria')
      <x-ui.detalle
        :id="'detalle-consejeria-'.$registro->id"
        :title="'Consejería #'.$registro->id"
        :filas="\App\Support\DetalleRegistro::consejeria($registro)"
        :campos="$puedeEditar ? \App\Support\DetalleRegistro::camposConsejeria($registro) : []"
        :action="$puedeEditar ? route('consejeria.actualizar', $registro) : null"
      />
    @elseif ($seccion === 'valores_adicionales')
      <x-ui.detalle
        :id="'detalle-valor-'.$registro->id"
        :title="'Valor adicional #'.$registro->id"
        :filas="\App\Support\DetalleRegistro::valor($registro)"
        :campos="$puedeEditar ? \App\Support\DetalleRegistro::camposValor($registro, $tiposValor ?? []) : []"
        :action="$puedeEditar ? route('valores.actualizar', $registro) : null"
      />
    @elseif ($seccion === 'networking')
      <x-ui.detalle :id="'detalle-networking-'.$registro->id" :title="$registro->empresa ?: 'Negocio #'.$registro->id" :filas="\App\Support\DetalleRegistro::networking($registro)" />
    @elseif ($seccion === 'eventos')
      <x-ui.detalle :id="'detalle-evento-'.$registro->id" :title="'Evento #'.$registro->id" :filas="\App\Support\DetalleRegistro::evento($registro)" />
    @elseif ($seccion === 'calendario')
      <x-ui.detalle :id="'detalle-calendario-'.$registro->id" :title="'Calendario #'.$registro->id" :filas="\App\Support\DetalleRegistro::calendario($registro)" />
    @endif
  @endforeach

  @if ($soloLectura)
    <p class="mt-6 flex items-start gap-2 text-xs text-muted">
      <x-ui.icon name="info-circle" class="mt-0.5" />
      El alta, la edición y los informes de este módulo siguen en el sistema PHP mientras se completa su traslado. Los datos son los de la base actual.
    </p>
  @endif
@endif

@if ($puedeRegistrar && $conFormulario)
  <x-ui.modal id="dialogNuevo" :title="$etiquetaNuevo" description="Los campos con * son obligatorios." :open="$abrirFormulario">
    @if ($seccion === 'ofrendas')
      @if (($casasOfrenda ?? collect())->isEmpty())
        <x-ui.alert type="warning">
          No hay casas de vida registradas. Configura la estructura CDA antes de registrar ofrendas.
        </x-ui.alert>
      @else
        <form id="formNuevoModulo" method="POST" action="{{ route('ofrendas.guardar') }}" class="grid gap-5 sm:grid-cols-2">
          @csrf
          <x-ui.field label="Casa de vida" name="casa_id" required>
            <select class="input" id="casa_id" name="casa_id" required autofocus>
              <option value="">Seleccionar…</option>
              @foreach ($casasOfrenda as $casa)
                <option
                  value="{{ $casa->id }}"
                  data-lider="{{ $casa->lider?->nombreCompleto() }}"
                  @selected((string) old('casa_id') === (string) $casa->id)
                >{{ $casa->nombre }}</option>
              @endforeach
            </select>
          </x-ui.field>
          <x-ui.field label="Líder" name="lider">
            <input class="input bg-surface-2" id="lider" name="lider" value="{{ old('lider') }}" readonly placeholder="—" tabindex="-1">
          </x-ui.field>
          <x-ui.field label="Fecha" name="fecha_ofrenda" required>
            <input type="date" class="input" id="fecha_ofrenda" name="fecha_ofrenda" value="{{ old('fecha_ofrenda', now()->toDateString()) }}" required>
          </x-ui.field>
          <x-ui.field label="Monto" name="monto" required>
            <input type="number" step="0.01" min="0" class="input" id="monto" name="monto" value="{{ old('monto') }}" inputmode="decimal" required>
          </x-ui.field>
        </form>
      @endif
    @elseif ($seccion === 'consejeria')
      <form id="formNuevoModulo" method="POST" action="{{ route('consejeria.guardar') }}" class="grid gap-5 sm:grid-cols-2">
        @csrf
        <x-ui.field label="Nombre completo" name="nombre_completo" required>
          <input class="input" id="nombre_completo" name="nombre_completo" value="{{ old('nombre_completo') }}" required maxlength="200" autofocus>
        </x-ui.field>
        <x-ui.field label="Teléfono" name="telefono" required>
          <input class="input" id="telefono" name="telefono" value="{{ old('telefono') }}" required maxlength="30" inputmode="tel">
        </x-ui.field>
        <x-ui.field label="Tipo de consejería" name="tipo_consejeria" required>
          <select class="input" id="tipo_consejeria" name="tipo_consejeria" required>
            @foreach ($tiposConsejeria as $valor => $etiqueta)
              <option value="{{ $valor }}" @selected(old('tipo_consejeria') === $valor)>{{ $etiqueta }}</option>
            @endforeach
          </select>
        </x-ui.field>
        <x-ui.field label="Años en CDA" name="anio_en_cda" required>
          <input type="number" min="0" class="input" id="anio_en_cda" name="anio_en_cda" value="{{ old('anio_en_cda', 0) }}" required>
        </x-ui.field>
        <label class="flex items-center gap-2.5 text-sm sm:col-span-2">
          <input type="checkbox" name="primera_consejeria" value="1" class="size-4 rounded border-line accent-[var(--accent)]" @checked(old('primera_consejeria', true))>
          Es su primera consejería
        </label>
      </form>
    @elseif ($seccion === 'valores_adicionales')
      <form id="formNuevoModulo" method="POST" action="{{ route('valores.guardar') }}" class="grid gap-5 sm:grid-cols-2">
        @csrf
        <x-ui.field label="Tipo" name="tipo" required>
          @if (!empty($tiposValor) && count($tiposValor))
            <select class="input" id="tipo" name="tipo" required>
              @foreach ($tiposValor as $tipo)
                <option value="{{ $tipo->clave }}" @selected(old('tipo') === $tipo->clave)>{{ $tipo->etiqueta }}</option>
              @endforeach
            </select>
          @else
            <input class="input" id="tipo" name="tipo" value="{{ old('tipo') }}" required maxlength="50">
          @endif
        </x-ui.field>
        <x-ui.field label="Nombre" name="nombre" required>
          <input class="input" id="nombre" name="nombre" value="{{ old('nombre') }}" required maxlength="100">
        </x-ui.field>
        <x-ui.field label="Teléfono" name="telefono" required>
          <input class="input" id="telefono" name="telefono" value="{{ old('telefono') }}" required maxlength="30" inputmode="tel">
        </x-ui.field>
        <x-ui.field label="Fecha" name="fecha" required>
          <input type="date" class="input" id="fecha" name="fecha" value="{{ old('fecha', now()->toDateString()) }}" required>
        </x-ui.field>
        <x-ui.field label="Valor" name="valor" required>
          <input type="number" step="0.01" min="0" class="input" id="valor" name="valor" value="{{ old('valor') }}" inputmode="decimal" required>
        </x-ui.field>
        <x-ui.field label="Observación" name="observacion" class="sm:col-span-2">
          <textarea class="input" id="observacion" name="observacion" rows="2">{{ old('observacion') }}</textarea>
        </x-ui.field>
      </form>
    @endif
    <x-slot:footer>
      <x-ui.button type="button" variant="secondary" data-modal-close>Cancelar</x-ui.button>
      @if ($seccion !== 'ofrendas' || ($casasOfrenda ?? collect())->isNotEmpty())
        <x-ui.button type="submit" form="formNuevoModulo" icon="check2">
          {{ $seccion === 'ofrendas' ? 'Guardar ofrenda' : ($seccion === 'consejeria' ? 'Guardar solicitud' : 'Guardar valor') }}
        </x-ui.button>
      @endif
    </x-slot:footer>
  </x-ui.modal>
@endif

@if ($seccion === 'ofrendas')
  <script type="application/json" id="ofrendas-casas">@json(($casasOfrenda ?? collect())->map(fn ($casa) => ['id' => $casa->id, 'lider' => $casa->lider?->nombreCompleto() ?: ''])->values())</script>
  <script>
    (function () {
      function liderDe(select) {
        var opcion = select.selectedOptions[0];
        if (opcion && opcion.getAttribute('data-lider')) {
          return opcion.getAttribute('data-lider');
        }
        var raw = document.getElementById('ofrendas-casas');
        if (!raw) return '';
        try {
          var casa = JSON.parse(raw.textContent).find(function (fila) { return String(fila.id) === String(select.value); });
          return casa && casa.lider ? casa.lider : '';
        } catch (e) {
          return '';
        }
      }

      document.querySelectorAll('select[name="casa_id"]').forEach(function (select) {
        var lider = select.closest('form') && select.closest('form').querySelector('[name="lider"]');
        function sincronizar() {
          if (lider) lider.value = liderDe(select);
        }
        select.addEventListener('change', sincronizar);
        sincronizar();
      });
    })();
  </script>
@endif
@endsection
