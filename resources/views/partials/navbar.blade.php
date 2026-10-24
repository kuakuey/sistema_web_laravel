<header class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-line bg-surface/85 px-4 backdrop-blur lg:hidden">
  <button type="button" data-sidebar-toggle class="grid size-9 place-items-center rounded-lg text-muted transition hover:bg-surface-2 hover:text-ink" aria-label="Abrir menú" aria-controls="appSidebar">
    <x-ui.icon name="list" class="text-xl" />
  </button>
  <a href="{{ \App\Support\Permisos::urlInicio($rolActual) }}" class="flex-1 truncate text-sm font-semibold tracking-tight">Sistema CDA</a>
  <button type="button" data-theme-toggle class="grid size-9 place-items-center rounded-lg text-muted transition hover:bg-surface-2 hover:text-ink" aria-label="Cambiar tema">
    <x-ui.icon name="moon-stars" class="dark:hidden" />
    <x-ui.icon name="sun" class="hidden dark:inline" />
  </button>
</header>
