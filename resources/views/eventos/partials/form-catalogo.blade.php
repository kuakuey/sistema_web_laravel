@php
  $tipos = $evento?->tiposEntrada?->all() ?: [['nombre' => '', 'valor' => 0, 'prefijo' => '', 'visible_publico' => true, 'es_gratis' => false]];
  $campos = $evento?->camposAdicionales?->all() ?: [];
@endphp

<div class="grid gap-5 sm:grid-cols-2">
  <x-ui.field label="Nombre" name="nombre" required>
    <input class="input" name="nombre" required maxlength="150" value="{{ old('nombre', $evento->nombre ?? '') }}">
  </x-ui.field>
  <x-ui.field label="Fecha" name="fecha" required>
    <input class="input" name="fecha" type="date" required value="{{ old('fecha', optional($evento->fecha ?? null)->format('Y-m-d') ?: now()->toDateString()) }}">
  </x-ui.field>
</div>

<div class="space-y-3">
  <div class="flex items-center justify-between">
    <p class="text-sm font-medium">Tipos de entrada <span class="text-danger">*</span></p>
    <x-ui.button type="button" size="sm" variant="secondary" icon="plus-lg" data-agregar-tipo>Agregar tipo</x-ui.button>
  </div>
  <div class="overflow-x-auto rounded-lg border border-line">
    <table class="data-table">
      <thead>
        <tr>
          <th>Tipo</th>
          <th>Prefijo</th>
          <th>Valor</th>
          <th></th>
        </tr>
      </thead>
      <tbody data-lista-tipos>
        @foreach ($tipos as $tipo)
          <tr data-fila-tipo>
            <td><input class="input" name="tipo_entrada[nombre][]" required maxlength="100" placeholder="General, VIP…" value="{{ $tipo['nombre'] ?? $tipo->nombre ?? '' }}"></td>
            <td><input class="input" name="tipo_entrada[prefijo][]" maxlength="10" placeholder="VIP" value="{{ $tipo['prefijo'] ?? $tipo->prefijo ?? '' }}"></td>
            <td><input class="input" name="tipo_entrada[valor][]" type="number" min="0" step="0.01" value="{{ $tipo['valor'] ?? $tipo->valor ?? 0 }}"></td>
            <td class="space-y-1">
              <input type="hidden" name="tipo_entrada[visible_publico][]" value="{{ !empty($tipo['visible_publico'] ?? $tipo->visible_publico ?? true) ? '1' : '0' }}" data-visible>
              <input type="hidden" name="tipo_entrada[es_gratis][]" value="{{ !empty($tipo['es_gratis'] ?? $tipo->es_gratis ?? false) ? '1' : '0' }}" data-gratis>
              <label class="mr-2 text-xs"><input type="checkbox" data-check-visible @checked(!empty($tipo['visible_publico'] ?? $tipo->visible_publico ?? true))> Visible</label>
              <label class="mr-2 text-xs"><input type="checkbox" data-check-gratis @checked(!empty($tipo['es_gratis'] ?? $tipo->es_gratis ?? false))> Gratis</label>
              <button type="button" class="text-danger text-xs" data-quitar-tipo>Quitar</button>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <p class="text-xs text-muted">El prefijo se junta con la numeración (G + 203 = G203). Sin «Visible» solo lo usan admin y superadmin.</p>
</div>

<div class="space-y-3">
  <div class="flex items-center justify-between">
    <p class="text-sm font-medium">Información adicional</p>
    <x-ui.button type="button" size="sm" variant="secondary" icon="plus-lg" data-agregar-campo>Agregar dato</x-ui.button>
  </div>
  <p class="text-xs text-muted">Datos que se pedirán al registrar. Déjalo vacío si no aplica.</p>
  <div class="space-y-3" data-lista-campos>
    @foreach ($campos as $campo)
      @php
        $tipoCampo = $campo['tipo'] ?? $campo->tipo ?? 'texto';
        $opciones = is_object($campo) ? implode("\n", $campo->opcionesLista()) : '';
      @endphp
      <div class="grid gap-3 rounded-lg border border-line p-3 sm:grid-cols-[1fr_8rem_1fr_auto]" data-fila-campo>
        <input class="input" name="campo_adicional[etiqueta][]" maxlength="100" placeholder="Ej. Iglesia, talla…" value="{{ $campo['etiqueta'] ?? $campo->etiqueta ?? '' }}">
        <select class="input" name="campo_adicional[tipo][]" data-tipo-campo>
          @foreach (\App\Support\Eventos::tiposCampo() as $clave => $etiqueta)
            <option value="{{ $clave }}" @selected($tipoCampo === $clave)>{{ $etiqueta }}</option>
          @endforeach
        </select>
        <textarea class="input" name="campo_adicional[opciones][]" rows="2" placeholder="Una opción por línea" data-opciones-campo @if ($tipoCampo !== 'lista') hidden @endif>{{ $opciones }}</textarea>
        <div class="space-y-1">
          <input type="hidden" name="campo_adicional[obligatorio][]" value="{{ !empty($campo['obligatorio'] ?? $campo->obligatorio ?? false) ? '1' : '0' }}" data-obligatorio>
          <label class="block text-xs"><input type="checkbox" data-check-obligatorio @checked(!empty($campo['obligatorio'] ?? $campo->obligatorio ?? false))> Obligatorio</label>
          <button type="button" class="text-danger text-xs" data-quitar-campo>Quitar</button>
        </div>
      </div>
    @endforeach
  </div>
</div>

<label class="flex items-center gap-2 text-sm">
  <input type="checkbox" name="habilitado" value="1" @checked(old('habilitado', $evento->habilitado ?? true))>
  Habilitado para registro
</label>
<label class="flex items-center gap-2 text-sm">
  <input type="checkbox" name="requiere_numeracion" value="1" @checked(old('requiere_numeracion', $evento->requiere_numeracion ?? false))>
  ¿Requiere numeración?
</label>
