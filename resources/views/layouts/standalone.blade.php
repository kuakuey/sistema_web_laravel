<!DOCTYPE html>
<html lang="es">
<head>
  @include('partials.head')
</head>
<body class="min-h-screen overflow-x-clip bg-canvas font-sans text-ink antialiased">
  <header class="border-b border-line bg-surface">
    <div class="mx-auto flex h-14 max-w-3xl items-center justify-between gap-3 px-4 sm:px-6">
      <div class="flex min-w-0 items-center gap-3">
        <span class="grid size-8 shrink-0 place-items-center rounded-lg bg-accent text-accent-ink">
          <x-ui.icon name="mortarboard" class="text-sm" />
        </span>
        <div class="min-w-0">
          <p class="truncate text-sm font-semibold tracking-tight">{{ $tituloBarra ?? 'Sistema CDA' }}</p>
          @isset($usuarioActual)
            <p class="truncate text-xs text-muted">{{ $usuarioActual->nombreVisible() }}</p>
          @endisset
        </div>
      </div>

      <div class="flex shrink-0 items-center gap-1">
        <button type="button" data-theme-toggle class="grid size-9 place-items-center rounded-lg text-muted transition hover:bg-surface-2 hover:text-ink" aria-label="Cambiar tema" title="Cambiar tema">
          <x-ui.icon name="moon-stars" class="dark:hidden" />
          <x-ui.icon name="sun" class="hidden dark:inline" />
        </button>
        <form method="POST" action="{{ route('logout') }}" data-no-ajax>
          @csrf
          <button type="submit" class="grid size-9 place-items-center rounded-lg text-muted transition hover:bg-danger-soft hover:text-danger" aria-label="Cerrar sesión" title="Cerrar sesión">
            <x-ui.icon name="box-arrow-right" />
          </button>
        </form>
      </div>
    </div>
  </header>

  <main class="mx-auto w-full max-w-3xl px-4 py-6 sm:px-6 sm:py-8">
    @yield('content')
  </main>

  <dialog id="confirmDialog" class="m-auto w-[calc(100%-2rem)] max-w-sm rounded-xl border border-line bg-surface p-0 text-ink shadow-2xl backdrop:bg-ink/40 backdrop:backdrop-blur-sm">
    <div class="p-6">
      <span class="mb-4 grid size-10 place-items-center rounded-full bg-danger-soft text-danger">
        <x-ui.icon name="exclamation-triangle" />
      </span>
      <h2 class="text-base font-semibold">Confirmar acción</h2>
      <p class="mt-1.5 text-sm text-muted" data-confirm-message></p>
    </div>
    <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
      <x-ui.button variant="secondary" data-confirm-cancel>Cancelar</x-ui.button>
      <x-ui.button data-confirm-accept class="!bg-danger !text-accent-ink hover:!brightness-110">Confirmar</x-ui.button>
    </div>
  </dialog>

  <x-notify::notify />
</body>
</html>
