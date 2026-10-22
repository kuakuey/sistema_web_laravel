@extends('layouts.app')

@section('content')
<x-ui.page-header title="Generar informe" subtitle="Elige el informe, aplica los filtros y descárgalo en PDF o Excel" />

@if (session('error'))
  <x-ui.alert type="danger" class="mb-6">{{ session('error') }}</x-ui.alert>
@endif

<x-ui.card title="Descargar informe">
  <p class="mb-5 text-sm text-muted">
    El informe filtra por la fecha en que se registró cada ofrenda, evento, valor adicional o persona,
    no por la fecha de la ofrenda, del evento o del bautizo. Si dejas las fechas vacías, se incluyen todos los registros.
  </p>

  <form method="GET" action="{{ route('informes.pdf') }}" id="formInforme" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-ui.field label="Desde" name="fecha_desde" class="sm:col-span-1">
      <input class="input" type="date" id="fecha_desde" name="fecha_desde" value="{{ $fechaDesde }}">
    </x-ui.field>

    <x-ui.field label="Hasta" name="fecha_hasta">
      <input class="input" type="date" id="fecha_hasta" name="fecha_hasta" value="{{ $fechaHasta }}">
    </x-ui.field>

    <x-ui.field label="Jornada" name="turno">
      <select class="input" id="turno" name="turno">
        @foreach ($turnos as $clave => $etiqueta)
          <option value="{{ $clave }}" @selected($turno === $clave)>{{ $etiqueta }}</option>
        @endforeach
      </select>
    </x-ui.field>

    <x-ui.field label="Informe" name="seccion">
      @if ($soloEventos)
        <input type="hidden" name="seccion" value="eventos">
        <input class="input" id="seccion" value="Eventos" readonly>
      @else
        <select class="input" id="seccion" name="seccion">
          @foreach ($secciones as $clave => $etiqueta)
            <option value="{{ $clave }}" @selected($seccion === $clave)>{{ $etiqueta }}</option>
          @endforeach
        </select>
      @endif
    </x-ui.field>

    <div class="sm:col-span-2 xl:col-span-4 {{ $seccion === 'ofrendas' ? '' : 'hidden' }}" data-campo="ofrendas">
      <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" id="mostrar_sin_entregar" name="mostrar_sin_entregar" value="1" class="size-4 rounded border-line accent-[var(--accent)]" @checked($mostrarSinEntregar)>
        Incluir casas de vida que no entregaron
      </label>
    </div>

    <div class="sm:col-span-2 {{ $seccion === 'eventos' ? '' : 'hidden' }}" data-campo="eventos">
      <x-ui.field label="Evento" name="evento_id" required hint="Obligatorio para el informe de eventos. Solo se muestran eventos habilitados.">
        <select class="input" id="evento_id" name="evento_id">
          <option value="" @selected($eventoId <= 0)>Selecciona un evento</option>
          @foreach ($eventos as $evento)
            <option value="{{ $evento->id }}" @selected($eventoId === (int) $evento->id)>
              {{ $evento->nombre }} ({{ (int) $evento->registros_count }} registro(s))
            </option>
          @endforeach
        </select>
      </x-ui.field>
    </div>

    <div class="sm:col-span-2 xl:col-span-4 {{ $seccion === 'presentaciones' ? '' : 'hidden' }}" data-campo="presentaciones">
      <p class="mb-2 text-sm font-medium">Estados de presentación</p>
      <div class="flex flex-wrap gap-x-4 gap-y-2">
        @foreach ($etiquetasPresentacion as $clave => $etiqueta)
          <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="estados_presentacion[]" value="{{ $clave }}" class="size-4 rounded border-line accent-[var(--accent)]" data-estado-presentacion @checked(in_array($clave, $estadosPresentacion, true))>
            {{ $etiqueta }}
          </label>
        @endforeach
      </div>
      <p class="mt-1.5 text-xs text-muted">Selecciona uno o más estados para incluir en el informe.</p>
    </div>

    <div class="{{ $seccion === 'bautismos' ? '' : 'hidden' }}" data-campo="bautismos">
      <x-ui.field label="Estado" name="estado_bautismo" hint="Ingresados, bautizados o todos.">
        <select class="input" id="estado_bautismo" name="estado_bautismo">
          @foreach ($estadosBautismo as $clave => $etiqueta)
            <option value="{{ $clave }}" @selected($estadoBautismo === $clave)>{{ $etiqueta }}</option>
          @endforeach
        </select>
      </x-ui.field>
    </div>

    <div class="flex flex-wrap items-center gap-2 sm:col-span-2 xl:col-span-4">
      <x-ui.button type="submit" icon="bi-file-earmark-pdf">Descargar PDF</x-ui.button>
      <x-ui.button type="submit" variant="secondary" icon="bi-file-earmark-excel" formaction="{{ route('informes.excel') }}">Descargar Excel</x-ui.button>
      <x-ui.button href="{{ route('informes') }}" variant="secondary" icon="bi-x-circle">Limpiar filtros</x-ui.button>
      <p class="w-full text-xs text-muted">El Excel incluye todos los campos de cada registro: teléfonos, fechas, estados, asistencia y datos adicionales.</p>
    </div>
  </form>
</x-ui.card>

<script>
  (function () {
    var seccion = document.getElementById('seccion');
    var soloEventos = @json($soloEventos);
    var evento = document.getElementById('evento_id');
    var campos = {
      ofrendas: document.querySelector('[data-campo="ofrendas"]'),
      eventos: document.querySelector('[data-campo="eventos"]'),
      presentaciones: document.querySelector('[data-campo="presentaciones"]'),
      bautismos: document.querySelector('[data-campo="bautismos"]')
    };
    var formulario = document.getElementById('formInforme');

    function actual() {
      if (soloEventos) {
        return 'eventos';
      }

      return seccion ? seccion.value : 'completo';
    }

    function actualizar() {
      var valor = actual();
      Object.keys(campos).forEach(function (clave) {
        if (!campos[clave]) {
          return;
        }
        campos[clave].classList.toggle('hidden', valor !== clave);
      });
      if (evento) {
        evento.required = valor === 'eventos';
      }
      if (valor !== 'ofrendas') {
        var casilla = document.getElementById('mostrar_sin_entregar');
        if (casilla) {
          casilla.checked = false;
        }
      }
    }

    if (seccion && seccion.tagName === 'SELECT') {
      seccion.addEventListener('change', actualizar);
    }
    actualizar();

    if (formulario) {
      formulario.addEventListener('submit', function (eventoSubmit) {
        var valor = actual();
        if (valor === 'eventos' && evento && !evento.value) {
          eventoSubmit.preventDefault();
          window.alert('Selecciona un evento para generar el informe.');
          return;
        }
        if (valor !== 'presentaciones') {
          return;
        }
        var marcado = false;
        document.querySelectorAll('[data-estado-presentacion]').forEach(function (casilla) {
          if (casilla.checked) {
            marcado = true;
          }
        });
        if (!marcado) {
          eventoSubmit.preventDefault();
          window.alert('Selecciona al menos un estado de presentación.');
        }
      });
    }
  })();
</script>
@endsection
