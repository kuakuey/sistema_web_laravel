@extends('layouts.app')

@section('content')
<x-ui.page-header title="Generar informe" subtitle="Resumen calculado sobre la base de datos actual" />

@if ($errorBd)
  <x-ui.alert type="warning" class="mb-6">{{ $errorBd }}</x-ui.alert>
@endif

@if (count($resumen) === 0)
  <x-ui.card flush>
    <x-ui.empty icon="bi-bar-chart" title="Sin datos para mostrar" text="No se pudo calcular el resumen en este momento." />
  </x-ui.card>
@else
  <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    @foreach ($resumen as $etiqueta => $valor)
      <x-ui.card>
        <p class="text-xs font-medium text-muted">{{ $etiqueta }}</p>
        <p class="mt-2 text-2xl font-semibold tracking-tight tabular-nums">{{ $valor }}</p>
      </x-ui.card>
    @endforeach
  </div>
@endif

<p class="mt-6 flex items-start gap-2 text-xs text-muted">
  <i class="bi bi-info-circle mt-0.5" aria-hidden="true"></i>
  Los informes en PDF y Excel del sistema anterior se portarán en el siguiente paso.
</p>
@endsection
