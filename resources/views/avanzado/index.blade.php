@extends('layouts.app')

@section('content')
@php
  $abrirUsuario = $errors->any() && old('_formulario') === 'usuario';
  $abrirClave = $errors->any() && old('_formulario') === 'clave';
  $itemActual = collect($menuAvanzado ?? [])->firstWhere('clave', $pestaña);
  $tituloSeccion = $itemActual['etiqueta'] ?? 'Avanzado';
  $subtitulo = $itemActual['texto'] ?? 'Usuarios, permisos, importación, log de actividad y base de datos';
@endphp

<x-ui.page-header :title="$tituloSeccion" :subtitle="$subtitulo">
  <x-slot:actions>
    @if ($pestaña === 'usuarios')
      <x-ui.badge tone="accent">{{ $usuarios->count() }} usuario(s)</x-ui.badge>
      <x-ui.button type="button" icon="bi-person-plus" data-modal-open="dialogNuevoUsuario">Nuevo usuario</x-ui.button>
    @elseif ($pestaña === 'bd' && $estadoBd)
      <x-ui.badge :tone="$estadoBd['sistema_listo'] ? 'success' : 'warning'">
        {{ $estadoBd['tablas_instaladas'] }}/{{ $estadoBd['total_tablas'] }} tablas
      </x-ui.badge>
    @elseif ($pestaña === 'logs' && $logs)
      <x-ui.badge tone="accent">{{ $logs->total() }} registro(s)</x-ui.badge>
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

@if ($pestaña === 'usuarios')
  @include('avanzado.partials.usuarios')
@elseif ($pestaña === 'permisos')
  @include('avanzado.partials.permisos')
@elseif ($pestaña === 'bd')
  @include('avanzado.partials.bd')
@elseif ($pestaña === 'importar')
  @include('avanzado.partials.importar')
@elseif ($pestaña === 'logs')
  @include('avanzado.partials.logs')
@endif
@endsection
