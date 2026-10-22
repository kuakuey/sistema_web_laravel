@extends('layouts.app')

@section('content')
@php
  $seccionInicial = array_key_exists('completo', $secciones) ? 'completo' : (string) array_key_first($secciones);
  $estadosDefecto = array_keys($etiquetasPresentacion);
  $estadosActuales = $estadosPresentacion;
  sort($estadosDefecto);
  sort($estadosActuales);
  $hayFiltros = $fechaDesde !== ''
      || $fechaHasta !== ''
      || $turno !== 'todos'
      || $seccion !== $seccionInicial
      || ($seccion === 'eventos' && $eventoId > 0)
      || $mostrarSinEntregar
      || ($seccion === 'bautismos' && $estadoBautismo !== 'todos')
      || ($seccion === 'presentaciones' && $estadosActuales !== $estadosDefecto);
@endphp

<x-ui.page-header title="Generar informe" subtitle="Elige el informe, aplica los filtros y descárgalo en PDF o Excel" />

@if (session('error'))
  <x-ui.alert type="danger" class="mb-6">{{ session('error') }}</x-ui.alert>
@endif

<x-ui.card title="Descargar informe">

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
      <div class="grid w-full grid-cols-2 gap-2 sm:flex sm:w-auto">
        <x-ui.button type="submit" icon="bi-file-earmark-pdf" class="w-full justify-center !px-2 !text-xs sm:w-auto sm:!px-4 sm:!text-sm">PDF</x-ui.button>
        <x-ui.button type="submit" variant="secondary" icon="bi-file-earmark-excel" class="w-full justify-center !px-2 !text-xs sm:w-auto sm:!px-4 sm:!text-sm" formaction="{{ route('informes.excel') }}">Excel</x-ui.button>
      </div>
      <span id="limpiarFiltros" @unless ($hayFiltros) hidden @endunless>
        <x-ui.button href="{{ route('informes') }}" variant="secondary" icon="bi-x-circle">Limpiar filtros</x-ui.button>
      </span>
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
    var limpiar = document.getElementById('limpiarFiltros');
    var seccionInicial = @json($seccionInicial);
    var estadosDefecto = @json(array_keys($etiquetasPresentacion)).slice().sort();

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
      sincronizarLimpiar();
    }

    function hayFiltro() {
      var valor = actual();
      var desde = document.getElementById('fecha_desde');
      var hasta = document.getElementById('fecha_hasta');
      var jornada = document.getElementById('turno');

      if ((desde && desde.value) || (hasta && hasta.value) || (jornada && jornada.value !== 'todos') || valor !== seccionInicial) {
        return true;
      }

      if (valor === 'eventos' && evento && evento.value) {
        return true;
      }

      if (valor === 'ofrendas') {
        var sinEntregar = document.getElementById('mostrar_sin_entregar');
        if (sinEntregar && sinEntregar.checked) {
          return true;
        }
      }

      if (valor === 'bautismos') {
        var estado = document.getElementById('estado_bautismo');
        if (estado && estado.value !== 'todos') {
          return true;
        }
      }

      if (valor === 'presentaciones') {
        var marcados = Array.from(document.querySelectorAll('[data-estado-presentacion]:checked'), function (casilla) {
          return casilla.value;
        }).sort();

        return marcados.length !== estadosDefecto.length || marcados.some(function (clave, indice) {
          return clave !== estadosDefecto[indice];
        });
      }

      return false;
    }

    function sincronizarLimpiar() {
      if (limpiar) {
        limpiar.hidden = !hayFiltro();
      }
    }

    if (seccion && seccion.tagName === 'SELECT') {
      seccion.addEventListener('change', actualizar);
    }
    if (formulario) {
      formulario.addEventListener('input', sincronizarLimpiar);
      formulario.addEventListener('change', sincronizarLimpiar);
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
