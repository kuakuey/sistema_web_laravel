<x-ui.card flush>
  @if ($usuarios->isEmpty())
    <x-ui.empty icon="people" title="Sin usuarios" text="Crea el primer acceso al panel.">
      <x-ui.button type="button" icon="person-plus" data-modal-open="dialogNuevoUsuario">Nuevo usuario</x-ui.button>
    </x-ui.empty>
  @else
    <x-ui.table>
      <x-slot:head>
        <th class="w-12">#</th>
        <th>Usuario</th>
        <th>Nombre</th>
        <th>Rol</th>
        <th>Creado</th>
        <th class="text-right">Acciones</th>
      </x-slot:head>
      @foreach ($usuarios as $indice => $usuario)
        <tr>
          <td class="text-muted tabular-nums">{{ $indice + 1 }}</td>
          <td class="font-medium">{{ $usuario->usuario }}</td>
          <td class="text-muted">{{ $usuario->nombre ?: '—' }}</td>
          <td><x-ui.badge tone="accent">{{ $roles[$usuario->rolNormalizado()] ?? $usuario->rol }}</x-ui.badge></td>
          <td class="text-muted tabular-nums">{{ optional($usuario->creado_en)->format('d/m/Y H:i') ?: '—' }}</td>
          <td>
            <div class="flex items-center justify-end gap-1">
              <x-ui.button
                type="button"
                size="sm"
                variant="ghost"
                icon="key"
                aria-label="Cambiar contraseña"
                data-modal-open="dialogCambiarClave"
                data-usuario-id="{{ $usuario->id }}"
                data-usuario-nombre="{{ $usuario->usuario }}"
              ></x-ui.button>
              @if ($usuario->id !== auth()->id())
                <form method="POST" action="{{ route('avanzado.usuarios.eliminar', $usuario) }}" data-confirm="¿Eliminar el usuario {{ $usuario->usuario }}?">
                  @csrf @method('DELETE')
                  <x-ui.button type="submit" size="sm" variant="danger" icon="trash" aria-label="Eliminar"></x-ui.button>
                </form>
              @endif
            </div>
          </td>
        </tr>
      @endforeach
    </x-ui.table>
  @endif
</x-ui.card>

<x-ui.modal id="dialogNuevoUsuario" title="Nuevo usuario" description="Crea un acceso al panel. El superadmin no se crea desde aquí." size="md" :open="$abrirUsuario">
  <form id="formNuevoUsuario" method="POST" action="{{ route('avanzado.usuarios') }}" class="space-y-5">
    @csrf
    <input type="hidden" name="_formulario" value="usuario">
    <x-ui.field label="Usuario" name="usuario" required>
      <input class="input" id="usuario" name="usuario" value="{{ old('usuario') }}" required maxlength="50" autocomplete="off" autofocus>
    </x-ui.field>
    <x-ui.field label="Nombre" name="nombre">
      <input class="input" id="nombre" name="nombre" value="{{ old('nombre') }}" maxlength="100">
    </x-ui.field>
    <x-ui.field label="Contraseña" name="clave" required hint="Mínimo 6 caracteres.">
      <input type="password" class="input" id="clave" name="clave" required minlength="6" autocomplete="new-password">
    </x-ui.field>
    <x-ui.field label="Rol" name="rol" required>
      <select class="input" id="rol" name="rol" required>
        @foreach ($rolesNuevo as $valor => $etiqueta)
          <option value="{{ $valor }}" @selected(old('rol') === $valor)>{{ $etiqueta }}</option>
        @endforeach
      </select>
    </x-ui.field>
  </form>
  <x-slot:footer>
    <x-ui.button type="button" variant="secondary" data-modal-close>Cancelar</x-ui.button>
    <x-ui.button type="submit" form="formNuevoUsuario" icon="person-plus">Crear usuario</x-ui.button>
  </x-slot:footer>
</x-ui.modal>

<x-ui.modal id="dialogCambiarClave" title="Cambiar contraseña" description="La nueva clave reemplaza la actual de inmediato." size="md" :open="$abrirClave">
  <form id="formCambiarClave" method="POST" action="{{ route('avanzado.usuarios.clave', old('usuario_id', $usuarios->first()?->id ?? 0)) }}" class="space-y-5" data-form-clave>
    @csrf
    @method('PUT')
    <input type="hidden" name="_formulario" value="clave">
    <p class="text-sm text-muted">Nueva contraseña para <strong class="text-ink" data-clave-nombre>{{ old('usuario_nombre', '') }}</strong></p>
    <x-ui.field label="Nueva contraseña" name="clave" required>
      <input type="password" class="input" id="clave_nueva" name="clave" required minlength="6" autocomplete="new-password">
    </x-ui.field>
    <x-ui.field label="Confirmar contraseña" name="clave_confirmation" required>
      <input type="password" class="input" id="clave_confirmacion" name="clave_confirmation" required minlength="6" autocomplete="new-password">
    </x-ui.field>
  </form>
  <x-slot:footer>
    <x-ui.button type="button" variant="secondary" data-modal-close>Cancelar</x-ui.button>
    <x-ui.button type="submit" form="formCambiarClave" icon="key">Guardar contraseña</x-ui.button>
  </x-slot:footer>
</x-ui.modal>
