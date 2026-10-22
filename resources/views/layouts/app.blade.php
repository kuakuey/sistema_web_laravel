<!DOCTYPE html>
<html lang="es">
<head>
  @include('partials.head')
</head>
<body class="min-h-screen bg-canvas font-sans text-ink antialiased">
  <div class="app-shell lg:grid lg:grid-cols-[16rem_minmax(0,1fr)]">
    <div id="sidebarOverlay" class="fixed inset-0 z-30 hidden bg-ink/40 backdrop-blur-sm lg:hidden" aria-hidden="true"></div>

    @include('partials.sidebar')

    <div class="min-w-0">
      @include('partials.navbar')

      <main class="mx-auto w-full max-w-6xl px-4 py-8 sm:px-6 lg:px-10 lg:py-12">
        @yield('content')
      </main>
    </div>
  </div>

  <dialog id="confirmDialog" class="m-auto w-[calc(100%-2rem)] max-w-sm rounded-xl border border-line bg-surface p-0 text-ink shadow-2xl backdrop:bg-ink/40 backdrop:backdrop-blur-sm">
    <div class="p-6">
      <span class="mb-4 grid size-10 place-items-center rounded-full bg-danger-soft text-danger">
        <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
      </span>
      <h2 class="text-base font-semibold">Confirmar acción</h2>
      <p class="mt-1.5 text-sm text-muted" data-confirm-message></p>
    </div>
    <div class="flex justify-end gap-2 border-t border-line px-6 py-4">
      <x-ui.button variant="secondary" data-confirm-cancel>Cancelar</x-ui.button>
      <x-ui.button data-confirm-accept class="!bg-danger !text-accent-ink hover:!brightness-110">Confirmar</x-ui.button>
    </div>
  </dialog>
</body>
</html>
