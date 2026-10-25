@extends('layouts.guest')

@section('content')
<div class="w-full max-w-sm">
  <div class="mb-8 text-center">
    <span class="mx-auto mb-5 grid size-12 place-items-center rounded-xl bg-accent text-accent-ink shadow-sm">
      <x-ui.icon name="grid-1x2-fill" class="text-lg" />
    </span>
  </div>

  <x-ui.card class="shadow-[0_8px_30px_-12px_rgb(15_23_42/0.18)]">
    @if ($errors->any())
      <x-ui.alert type="danger" class="mb-5">{{ $errors->first() }}</x-ui.alert>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5" novalidate data-no-ajax>
      @csrf

      <x-ui.field label="Correo electrónico" name="usuario">
        <div class="relative">
          <x-ui.icon name="person" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
          <input type="text" id="usuario" name="usuario" value="{{ old('usuario') }}" class="input pl-9" placeholder="Correo electrónico" autocomplete="username" autofocus required>
        </div>
      </x-ui.field>

      <x-ui.field label="Contraseña" name="clave">
        <div class="relative">
          <x-ui.icon name="lock" class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted" />
          <input type="password" id="clave" name="clave" class="input px-9" placeholder="Contraseña" autocomplete="current-password" required>
          <button type="button" data-password-toggle="clave" class="absolute right-1.5 top-1/2 grid size-7 -translate-y-1/2 place-items-center rounded-md text-muted transition hover:bg-surface-2 hover:text-ink" aria-label="Mostrar contraseña" aria-pressed="false">
            <x-ui.icon name="eye" />
          </button>
        </div>
      </x-ui.field>

      <x-ui.button type="submit" icon="box-arrow-in-right" class="w-full">Iniciar sesión</x-ui.button>
    </form>
  </x-ui.card>
</div>
@endsection
