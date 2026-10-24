<p class="mb-5 text-sm text-muted">Elige el rol y marca qué puede ver o hacer. El superadmin siempre tiene acceso total.</p>

<div class="mb-6 flex flex-wrap gap-2">
  @foreach ($rolesPermisos as $clave => $etiqueta)
    <a
      href="{{ route('avanzado.permisos.ver', ['rol' => $clave]) }}"
      @class([
        'inline-flex h-9 items-center rounded-full px-4 text-sm font-medium transition',
        'bg-accent text-accent-ink' => $rolPermisos === $clave,
        'border border-line bg-surface text-ink hover:bg-surface-2' => $rolPermisos !== $clave,
      ])
    >{{ $etiqueta }}</a>
  @endforeach
</div>

<form method="POST" action="{{ route('avanzado.permisos') }}" class="space-y-4" data-permisos>
  @csrf
  <input type="hidden" name="rol" value="{{ $rolPermisos }}">

  <div class="grid gap-4 lg:grid-cols-2">
    @foreach ($catalogo as $modulo => $info)
      @php
        $detalle = $info['permisos'] ?? [];
        $activos = collect($detalle)->keys()->filter(fn ($clave) => in_array($modulo.':'.$clave, $permisosRol, true))->count();
      @endphp
      <section class="rounded-xl border border-line bg-surface p-4">
        <div class="mb-3 flex items-start justify-between gap-3">
          <div class="flex items-start gap-3">
            <span class="grid size-9 place-items-center rounded-lg bg-accent-soft text-accent">
              <x-ui.icon :name="$iconos[$modulo] ?? 'circle'" />
            </span>
            <div>
              <h3 class="text-sm font-semibold">{{ $info['etiqueta'] }}</h3>
              <p class="text-xs text-muted">{{ $activos }} de {{ count($detalle) }} activo(s)</p>
            </div>
          </div>
          @if (count($detalle) > 1)
            <button type="button" class="text-xs text-accent hover:underline" data-permisos-toggle="{{ $modulo }}">Marcar todo</button>
          @endif
        </div>
        <div class="grid gap-2 sm:grid-cols-2">
          @foreach ($detalle as $clave => $etiqueta)
            <label class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm hover:bg-surface-2">
              <input
                type="checkbox"
                name="permisos[]"
                value="{{ $modulo }}:{{ $clave }}"
                data-modulo="{{ $modulo }}"
                class="size-4 rounded border-line accent-[var(--accent)]"
                @checked(in_array($modulo.':'.$clave, $permisosRol, true))
              >
              <span>{{ $etiqueta }}</span>
            </label>
          @endforeach
        </div>
      </section>
    @endforeach
  </div>

  <div class="flex justify-end pt-2">
    <x-ui.button type="submit" icon="check-lg">Guardar permisos de {{ $rolesPermisos[$rolPermisos] ?? $rolPermisos }}</x-ui.button>
  </div>
</form>
