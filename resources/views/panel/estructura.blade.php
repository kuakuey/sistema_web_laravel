@extends('layouts.app')

@section('content')
<x-ui.page-header title="Estructura CDA" subtitle="Territorios, miembros y casas de vida" />

@if ($errorBd)
  <x-ui.alert type="warning" class="mb-6">{{ $errorBd }}</x-ui.alert>
@endif

<div class="grid gap-5 lg:grid-cols-3">
  <x-ui.card title="Territorios" flush>
    <ul class="max-h-[28rem] divide-y divide-line overflow-y-auto">
      @forelse ($territorios as $territorio)
        <li class="flex items-center gap-3 px-5 py-3 text-sm">
          <x-ui.icon name="geo-alt" class="text-muted" />{{ $territorio->nombre }}
        </li>
      @empty
        <li><x-ui.empty icon="geo-alt" title="Sin territorios" /></li>
      @endforelse
    </ul>
  </x-ui.card>

  <x-ui.card title="Miembros" flush>
    <ul class="max-h-[28rem] divide-y divide-line overflow-y-auto">
      @forelse ($miembros as $miembro)
        <li class="flex items-center gap-3 px-5 py-3 text-sm">
          <span class="grid size-7 shrink-0 place-items-center rounded-full bg-surface-2 text-[0.65rem] font-semibold text-muted">{{ mb_strtoupper(mb_substr($miembro->nombre, 0, 1).mb_substr($miembro->apellido, 0, 1)) }}</span>
          {{ $miembro->nombre }} {{ $miembro->apellido }}
        </li>
      @empty
        <li><x-ui.empty icon="people" title="Sin miembros" /></li>
      @endforelse
    </ul>
  </x-ui.card>

  <x-ui.card title="Casas de vida" flush>
    <ul class="max-h-[28rem] divide-y divide-line overflow-y-auto">
      @forelse ($casas as $casa)
        <li class="px-5 py-3 text-sm">
          <p class="font-medium">{{ $casa->nombre }}</p>
          <p class="mt-0.5 text-xs text-muted">{{ $casa->direccion }}</p>
        </li>
      @empty
        <li><x-ui.empty icon="house" title="Sin casas de vida" /></li>
      @endforelse
    </ul>
  </x-ui.card>
</div>

<p class="mt-6 flex items-start gap-2 text-xs text-muted">
  <x-ui.icon name="info-circle" class="mt-0.5" />
  La importación y la edición completa de la estructura siguen en el sistema PHP. Esta pantalla lee las mismas tablas.
</p>
@endsection
