@php
  $iniciales = collect(preg_split('/\s+/', trim($usuarioActual->nombreVisible())))
      ->filter()
      ->take(2)
      ->map(fn ($parte) => mb_strtoupper(mb_substr($parte, 0, 1)))
      ->implode('');
@endphp
<aside
  id="appSidebar"
  class="app-sidebar fixed inset-y-0 left-0 z-40 flex w-64 max-w-full -translate-x-full flex-col overflow-x-clip border-r border-line bg-surface transition-[transform,width] duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
  aria-label="Navegación principal"
>
  <div class="sidebar-header flex h-16 shrink-0 items-center gap-3 px-4">
    <a href="{{ \App\Support\Permisos::urlInicio($rolActual) }}" class="sidebar-logo grid size-8 shrink-0 place-items-center rounded-lg bg-accent text-accent-ink" aria-label="Sistema CDA">
      <i class="bi bi-grid-1x2-fill text-sm" aria-hidden="true"></i>
    </a>
    <a href="{{ \App\Support\Permisos::urlInicio($rolActual) }}" class="sidebar-label min-w-0 flex-1 truncate text-sm font-semibold tracking-tight text-ink">Sistema CDA</a>
    <button
      type="button"
      data-sidebar-collapse
      class="sidebar-collapse relative hidden size-8 shrink-0 place-items-center rounded-lg text-muted transition hover:bg-surface-2 hover:text-ink lg:grid"
      aria-label="Colapsar menú"
      title="Colapsar menú"
    >
      <i class="bi bi-chevron-left sidebar-collapse-open text-sm" aria-hidden="true"></i>
      <i class="bi bi-chevron-right sidebar-collapse-closed text-sm" aria-hidden="true"></i>
      <span class="sidebar-tip sidebar-tip-collapse" role="tooltip">Expandir menú</span>
    </button>
  </div>

  <nav class="sidebar-nav min-w-0 flex-1 space-y-0.5 overflow-x-hidden overflow-y-auto px-3 py-2">
    @php
      $submenus = $menusLaterales ?? [
        'estructura' => $menuEstructura ?? [],
        'avanzado' => $menuAvanzado ?? [],
      ];
      $claveSubmenu = $menuActivo ?? $pestaña ?? '';
    @endphp
    @foreach ($itemsMenu as $claveSeccion)
      @php
        $activo = ($seccionActiva ?? '') === $claveSeccion;
        $etiqueta = $etiquetasMenu[$claveSeccion] ?? $claveSeccion;
      @endphp
      <a
        href="{{ \App\Support\Permisos::url($claveSeccion) }}"
        @if ($activo && $claveSubmenu === '') aria-current="page" @endif
        @class([
          'sidebar-link group relative flex min-w-0 items-center gap-3 rounded-lg px-3 py-2 text-sm transition',
          'bg-accent-soft font-medium text-accent' => $activo,
          'text-muted hover:bg-surface-2 hover:text-ink' => ! $activo,
        ])
      >
        <i class="bi {{ $iconosMenu[$claveSeccion] ?? 'bi-file-text' }} w-4 shrink-0 text-center text-[0.95rem]" aria-hidden="true"></i>
        <span class="sidebar-label min-w-0 truncate">{{ $etiqueta }}</span>
        <span class="sidebar-tip" role="tooltip">{{ $etiqueta }}</span>
      </a>
      @if ($activo && ! empty($submenus[$claveSeccion] ?? []))
        <div class="sidebar-submenu mb-1 ml-4 min-w-0 space-y-0.5 overflow-hidden border-l border-line pl-2">
          @foreach ($submenus[$claveSeccion] as $item)
            @php $subactivo = $claveSubmenu === $item['clave']; @endphp
            <a
              href="{{ route($item['ruta']) }}"
              @if ($subactivo) aria-current="page" @endif
              @class([
                'flex min-w-0 items-center gap-2 rounded-lg px-3 py-1.5 text-sm transition',
                'bg-accent-soft font-medium text-accent' => $subactivo,
                'text-muted hover:bg-surface-2 hover:text-ink' => ! $subactivo,
              ])
            >
              <i class="bi {{ $item['icono'] }} w-4 text-center text-[0.85rem]" aria-hidden="true"></i>
              <span class="min-w-0 truncate">{{ $item['etiqueta'] }}</span>
            </a>
          @endforeach
        </div>
      @endif
    @endforeach
  </nav>

  <div class="sidebar-footer shrink-0 border-t border-line p-3">
    <div class="sidebar-user flex items-center gap-3 rounded-lg px-2 py-2">
      <span class="grid size-9 shrink-0 place-items-center rounded-full bg-surface-2 text-xs font-semibold text-muted">{{ $iniciales ?: 'U' }}</span>
      <div class="sidebar-label min-w-0 flex-1">
        <p class="truncate text-sm font-medium text-ink">{{ $usuarioActual->nombreVisible() }}</p>
        @if (!empty($etiquetaRol))
          <p class="truncate text-xs text-muted">{{ $etiquetaRol }}</p>
        @endif
      </div>
      <button type="button" data-theme-toggle class="relative grid size-8 shrink-0 place-items-center rounded-lg text-muted transition hover:bg-surface-2 hover:text-ink" aria-label="Cambiar tema" title="Cambiar tema">
        <i class="bi bi-moon-stars dark:hidden" aria-hidden="true"></i>
        <i class="bi bi-sun hidden dark:inline" aria-hidden="true"></i>
        <span class="sidebar-tip" role="tooltip">Cambiar tema</span>
      </button>
    </div>

    <form method="POST" action="{{ route('logout') }}" class="mt-1">
      @csrf
      <button type="submit" class="sidebar-link relative flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-muted transition hover:bg-danger-soft hover:text-danger">
        <i class="bi bi-box-arrow-right w-4 shrink-0 text-center" aria-hidden="true"></i>
        <span class="sidebar-label">Cerrar sesión</span>
        <span class="sidebar-tip" role="tooltip">Cerrar sesión</span>
      </button>
    </form>
  </div>
</aside>
