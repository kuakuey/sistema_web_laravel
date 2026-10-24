<!DOCTYPE html>
<html lang="es" class="h-dvh">
<head>
  @include('partials.head')
</head>
<body class="h-dvh overflow-x-hidden bg-canvas font-sans text-ink antialiased">
  <div class="relative flex h-dvh items-center justify-center overflow-y-auto px-4 py-12">
    <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(48rem_26rem_at_50%_-8%,var(--accent-soft),transparent)]"></div>

    <button type="button" data-theme-toggle class="absolute right-4 top-4 grid size-9 place-items-center rounded-lg text-muted transition hover:bg-surface-2 hover:text-ink" aria-label="Cambiar tema">
      <x-ui.icon name="moon-stars" class="dark:hidden" />
      <x-ui.icon name="sun" class="hidden dark:inline" />
    </button>

    @yield('content')
  </div>

  <x-notify::notify />
</body>
</html>
