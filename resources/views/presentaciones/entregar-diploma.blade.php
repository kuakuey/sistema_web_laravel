@extends('layouts.standalone')

@section('content')
@php
  use App\Support\ApiPresentaciones;
@endphp

<div hidden data-presentaciones-pestana="entregar-diploma"></div>

@if (session('ok'))
  <x-ui.alert type="success" dismissible class="mb-6">{{ session('ok') }}</x-ui.alert>
@endif

@if ($errors->any())
  <x-ui.alert type="danger" dismissible class="mb-6">{{ $errors->first() }}</x-ui.alert>
@endif

@if ($errorBd)
  <x-ui.alert type="warning">{{ $errorBd }}</x-ui.alert>
@else
  <x-ui.card flush>
    @if ($registros->isEmpty())
      <x-ui.empty icon="mortarboard" title="Nadie pendiente de diploma" />
    @else
      <ul class="divide-y divide-line" data-lista-entregar-diploma>
        @foreach ($registros as $registro)
          @php
            $representantes = ApiPresentaciones::representantesLista($registro);
          @endphp
          <li
            class="flex flex-col gap-3 px-5 py-4 transition hover:bg-surface-2/60 sm:flex-row sm:items-center sm:gap-4"
            data-presentacion-item
            data-id="{{ $registro->id }}"
          >
            <div class="min-w-0 flex-1">
              <p class="text-base font-semibold leading-snug break-words text-ink">{{ $registro->nombre_presentado }}</p>
              @foreach ($representantes as $rep)
                <p class="mt-1 text-sm break-words text-muted">
                  {{ $rep['parentesco'] }}: {{ $rep['nombre'] }}
                </p>
              @endforeach
            </div>

            <form
              method="POST"
              action="{{ route('presentaciones.estado', $registro) }}"
              class="m-0 w-full shrink-0 sm:w-auto"
              data-ajax-estado-presentacion
            >
              @csrf
              <input type="hidden" name="accion" value="entregar_diploma">
              <x-ui.button
                type="submit"
                variant="success"
                icon="check2-circle"
                class="w-full sm:w-auto"
                title="Entregar diploma"
                aria-label="Entregar diploma a {{ $registro->nombre_presentado }}"
              >
                Entregar diploma
              </x-ui.button>
            </form>
          </li>
        @endforeach
      </ul>
    @endif
  </x-ui.card>
@endif
@endsection
