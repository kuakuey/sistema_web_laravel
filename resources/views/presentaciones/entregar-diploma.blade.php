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
            $fila = ApiPresentaciones::formatearPublica($registro);
            $representantes = collect($fila['representantes'] ?? [])
              ->map(function (array $rep) {
                  $parentesco = $rep['parentesco_etiqueta'] ?? 'Representante';
                  $nombre = trim((string) ($rep['nombre'] ?? ''));
                  $primer = $nombre !== '' ? (preg_split('/\s+/', $nombre)[0] ?? $nombre) : '';

                  return $primer !== '' ? $parentesco.': '.$primer : null;
              })
              ->filter()
              ->values();
          @endphp
          <li
            class="flex items-center gap-4 px-5 py-4 transition hover:bg-surface-2/60"
            data-presentacion-item
            data-id="{{ $registro->id }}"
          >
            <div class="min-w-0 flex-1">
              <p class="truncate text-base font-semibold text-ink">
                {{ $fila['nombre_presentado'] }}
                @if (($fila['edad_etiqueta'] ?? '—') !== '—')
                  <span class="font-normal text-muted">({{ $fila['edad_etiqueta'] }})</span>
                @endif
              </p>
              @if ($representantes->isNotEmpty())
                <p class="mt-0.5 truncate text-sm text-muted">
                  {{ $representantes->implode(' · ') }}
                </p>
              @endif
            </div>

            <form
              method="POST"
              action="{{ route('presentaciones.estado', $registro) }}"
              class="m-0 shrink-0"
              data-ajax-estado-presentacion
            >
              @csrf
              <input type="hidden" name="accion" value="entregar_diploma">
              <x-ui.button
                type="submit"
                variant="success"
                icon="check2-circle"
                title="Entregar diploma"
                aria-label="Entregar diploma a {{ $fila['nombre_presentado'] }}"
              >
                <span class="hidden sm:inline">Entregar diploma</span>
              </x-ui.button>
            </form>
          </li>
        @endforeach
      </ul>
    @endif
  </x-ui.card>
@endif
@endsection
