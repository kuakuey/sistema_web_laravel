<header class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-line bg-surface/85 px-4 backdrop-blur lg:hidden">
  <button type="button" data-sidebar-toggle class="grid size-9 place-items-center rounded-lg text-muted transition hover:bg-surface-2 hover:text-ink" aria-label="Abrir menú" aria-controls="appSidebar">
    <i class="bi bi-list text-xl" aria-hidden="true"></i>
  </button>
  <a href="{{ \App\Support\Permisos::urlInicio($rolActual) }}" class="flex-1 truncate text-sm font-semibold tracking-tight">Sistema CDA</a>
  <button type="button" data-theme-toggle class="grid size-9 place-items-center rounded-lg text-muted transition hover:bg-surface-2 hover:text-ink" aria-label="Cambiar tema">
    <i class="bi bi-moon-stars dark:hidden" aria-hidden="true"></i>
    <i class="bi bi-sun hidden dark:inline" aria-hidden="true"></i>
  </button>
</header>
