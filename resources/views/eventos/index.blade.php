@extends('layouts.app')

@section('content')
@php
  $esRegistros = $pestaña === 'registros' || $pestaña === 'participantes';
  $abrirRegistro = $errors->any() && old('_formulario', 'registro') === 'registro' && $pestaña === 'registros';
  $abrirEvento = $errors->any() && old('_formulario') === 'evento' && $pestaña === 'eventos';
  $itemActual = collect($menuEventos ?? [])->firstWhere('clave', $pestaña);
  $tituloSeccion = $eventoParticipantes?->nombre ?? ($itemActual['etiqueta'] ?? $titulo);
  $subtitulo = $eventoParticipantes
    ? 'Participantes de este evento'
    : ($itemActual['texto'] ?? 'Gestiona eventos y registra participantes');
@endphp

<x-ui.page-header :title="$tituloSeccion" :subtitle="$subtitulo">
  <x-slot:actions>
    @if ($pestaña === 'registros' && $registros)
      <x-ui.badge tone="accent">{{ $registros->total() }} registro(s)</x-ui.badge>
    @elseif ($pestaña === 'eventos')
      <x-ui.badge tone="accent">{{ $catalogo->count() }} evento(s)</x-ui.badge>
    @endif
    @if ($pestaña === 'registros' && $puedeRegistrar && ! $errorBd)
      <x-ui.button type="button" icon="bi-plus-lg" data-modal-open="dialogNuevo">Nuevo registro</x-ui.button>
    @endif
    @if ($pestaña === 'eventos' && $puedeAgregar && ! $errorBd)
      <x-ui.button type="button" icon="bi-plus-lg" data-modal-open="dialogNuevoEvento">Agregar evento</x-ui.button>
    @endif
  </x-slot:actions>
</x-ui.page-header>

@if (session('ok'))
  <x-ui.alert type="success" dismissible class="mb-6">{{ session('ok') }}</x-ui.alert>
@endif

@if (session('error'))
  <x-ui.alert type="danger" dismissible class="mb-6">{{ session('error') }}</x-ui.alert>
@endif

@if ($errors->any())
  <x-ui.alert type="danger" dismissible class="mb-6">{{ $errors->first() }}</x-ui.alert>
@endif

@if ($errorBd)
  <x-ui.alert type="warning">{{ $errorBd }}</x-ui.alert>
@else
  @if ($esRegistros)
    @include('eventos.partials.tabla-participantes')
  @elseif ($pestaña === 'eventos')
    @include('eventos.partials.catalogo')
  @endif
@endif

@if ($puedeRegistrar && $pestaña === 'registros' && ! $errorBd)
  <x-ui.modal id="dialogNuevo" title="Nuevo registro" description="Detalles de entradas, datos personales y datos adicionales." size="xl" :open="$abrirRegistro">
    @if ($habilitados->isEmpty())
      <x-ui.empty icon="bi-calendar-x" title="No hay eventos habilitados" text="Primero agrega y habilita un evento en Eventos." />
      <x-slot:footer>
        <x-ui.button type="button" variant="secondary" data-modal-close>Cerrar</x-ui.button>
      </x-slot:footer>
    @else
      @include('eventos.partials.registrar', ['enPopup' => true])
      <x-slot:footer>
        <x-ui.button type="button" variant="secondary" data-modal-close>Cerrar</x-ui.button>
        <x-ui.button type="submit" form="formNuevoRegistro" icon="bi-check-lg" data-submit-registro disabled>Registrar</x-ui.button>
      </x-slot:footer>
    @endif
  </x-ui.modal>
@endif

@if ($puedeAgregar && $pestaña === 'eventos' && ! $errorBd)
  <x-ui.modal id="dialogNuevoEvento" title="Agregar evento" description="Tipos de entrada, datos extra y si requiere numeración." size="xl" :open="$abrirEvento">
    <form id="formNuevoEvento" method="POST" action="{{ route('eventos.guardar') }}" class="space-y-6" data-catalogo-evento>
      @csrf
      <input type="hidden" name="_formulario" value="evento">
      @include('eventos.partials.form-catalogo', ['evento' => null])
    </form>
    <x-slot:footer>
      <x-ui.button type="button" variant="secondary" data-modal-close>Cerrar</x-ui.button>
      <x-ui.button type="submit" form="formNuevoEvento" icon="bi-plus-lg">Agregar evento</x-ui.button>
    </x-slot:footer>
  </x-ui.modal>
@endif
@endsection
