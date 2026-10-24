@extends('layouts.app')

@section('content')
<x-ui.page-header title="Checkout" subtitle="Consulta un ticket por numeración y marca la asistencia">
  <x-slot:actions>
    <x-ui.button href="{{ route('eventos') }}" variant="secondary">Volver a eventos</x-ui.button>
  </x-slot:actions>
</x-ui.page-header>

<x-ui.card class="mx-auto max-w-xl">
  @if ($eventos->isEmpty())
    <x-ui.empty icon="calendar-x" title="No hay eventos habilitados" text="Habilita un evento para usar el checkout." />
  @else
    <form class="space-y-5" data-checkout data-consultar="{{ route('checkout.consultar') }}" data-marcar="{{ route('checkout.marcar') }}" data-reversar="{{ route('checkout.reversar') }}" data-puede-reversar="{{ $puedeReversar ? '1' : '0' }}">
      @csrf
      <x-ui.field label="Evento" name="evento_id" required>
        <select class="input" name="evento_id" required>
          <option value="">Seleccione evento…</option>
          @foreach ($eventos as $evento)
            <option value="{{ $evento->id }}">{{ $evento->nombre }}</option>
          @endforeach
        </select>
      </x-ui.field>
      <x-ui.field label="Código / numeración" name="codigo" required>
        <input class="input" name="codigo" required maxlength="30" autocomplete="off" autofocus>
      </x-ui.field>
      <x-ui.button type="submit" icon="search">Consultar</x-ui.button>
    </form>

    <p class="mt-4 hidden text-sm text-danger" data-checkout-error></p>
    <div class="mt-6 hidden space-y-3" data-checkout-resultado></div>
  @endif
</x-ui.card>
@endsection
