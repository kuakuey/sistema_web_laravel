@extends('layouts.app')

@section('content')
@php $dinero = fn ($valor) => '$ '.number_format((float) $valor, 2); @endphp

<x-ui.page-header :title="'Informe · '.$evento->nombre" :subtitle="optional($evento->fecha)->format('d/m/Y')">
  <x-slot:actions>
    <x-ui.button type="button" variant="secondary" icon="bi-printer" onclick="window.print()">Imprimir</x-ui.button>
    <x-ui.button href="{{ route('eventos.participantes', $evento->slug()) }}" variant="secondary">Volver</x-ui.button>
  </x-slot:actions>
</x-ui.page-header>

<div class="mb-6 grid gap-3 sm:grid-cols-4">
  <x-ui.card><p class="text-xs text-muted">Registros</p><p class="mt-1 text-xl font-semibold">{{ $total }}</p></x-ui.card>
  <x-ui.card><p class="text-xs text-muted">Asistieron</p><p class="mt-1 text-xl font-semibold">{{ $asistieron }}</p></x-ui.card>
  <x-ui.card><p class="text-xs text-muted">Recaudado</p><p class="mt-1 text-xl font-semibold">{{ $dinero($recaudado) }}</p></x-ui.card>
  <x-ui.card><p class="text-xs text-muted">Por cancelar</p><p class="mt-1 text-xl font-semibold">{{ $dinero($pendiente) }}</p></x-ui.card>
</div>

<x-ui.card flush>
  @if ($registros->isEmpty())
    <x-ui.empty icon="bi-inbox" title="Sin registros" text="Este evento aún no tiene participantes." />
  @else
    <x-ui.table>
      <x-slot:head>
        <th>Nombre</th>
        <th>Tipo</th>
        @if ($evento->requiere_numeracion)<th>Numeración</th>@endif
        <th>Valor</th>
        <th>Estado</th>
        <th>Asistencia</th>
      </x-slot:head>
      @foreach ($registros as $registro)
        <tr>
          <td class="font-medium">{{ $registro->nombre }}</td>
          <td>{{ $registro->tipo_entrada ?: '—' }}</td>
          @if ($evento->requiere_numeracion)
            <td class="tabular-nums">{{ $registro->numeracion ?: '—' }}</td>
          @endif
          <td class="tabular-nums">{{ $dinero($registro->valor) }}</td>
          <td>{{ \App\Support\Eventos::etiquetaPago($registro->estado_pago) }}</td>
          <td>{{ $registro->asistio ? 'Asistió' : 'No asistió' }}</td>
        </tr>
      @endforeach
    </x-ui.table>
  @endif
</x-ui.card>
@endsection
