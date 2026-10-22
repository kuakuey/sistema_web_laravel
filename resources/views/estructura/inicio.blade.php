@extends('layouts.app')

@section('content')
<x-ui.page-header title="Estructura CDA" subtitle="Elige una sección del menú para continuar." />

<nav class="grid gap-4 sm:grid-cols-2" aria-label="Menú de Estructura CDA">
  @foreach ($menuEstructura as $item)
    <a
      href="{{ route($item['ruta']) }}"
      class="group rounded-xl border border-line bg-surface p-5 shadow-[0_1px_2px_0_rgb(15_23_42/0.03)] transition hover:border-accent hover:bg-accent-soft"
    >
      <span class="grid size-10 place-items-center rounded-lg bg-surface-2 text-accent group-hover:bg-surface">
        <i class="bi {{ $item['icono'] }}" aria-hidden="true"></i>
      </span>
      <h2 class="mt-4 text-base font-semibold text-ink">{{ $item['etiqueta'] }}</h2>
      <p class="mt-1 text-sm text-muted">{{ $item['texto'] }}</p>
    </a>
  @endforeach
</nav>
@endsection
