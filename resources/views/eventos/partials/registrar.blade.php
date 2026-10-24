@php
  $enPopup = $enPopup ?? false;
  $configEventos = $habilitados->mapWithKeys(
      fn ($evento) => [$evento->id => \App\Support\Eventos::eventoParaJs($evento, $rol)]
  );
@endphp
@if ($habilitados->isEmpty())
  <x-ui.empty icon="calendar-x" title="No hay eventos habilitados" text="Un superadmin debe agregar y habilitar eventos primero." />
@else
    <script type="application/json" id="configEventosRegistro">@json($configEventos)</script>
    <form id="formNuevoRegistro" method="POST" action="{{ route('eventos.participantes.guardar') }}" class="space-y-8" data-registro-evento data-puede-estado="{{ $puedeEstadoPago ? '1' : '0' }}" data-solo-pago="{{ $soloMetodosPago ? '1' : '0' }}">
      @csrf
      <input type="hidden" name="_formulario" value="registro">

      <section class="space-y-4">
        <h3 class="border-b border-line pb-2 text-sm font-semibold text-ink">Detalles de entradas</h3>
        <div class="grid gap-5 sm:grid-cols-2">
          <x-ui.field label="Evento" name="evento_id" required>
            <select class="input" id="evento_id" name="evento_id" required>
              <option value="">Seleccione evento…</option>
              @foreach ($habilitados as $evento)
                <option value="{{ $evento->id }}" @selected((string) old('evento_id') === (string) $evento->id)>{{ $evento->nombre }}</option>
              @endforeach
            </select>
          </x-ui.field>

          <x-ui.field label="Tipo de entrada" name="tipo_entrada_id" required>
            <select class="input" id="tipo_entrada_id" name="tipo_entrada_id" required disabled>
              <option value="">Seleccione evento primero…</option>
            </select>
          </x-ui.field>

          <x-ui.field label="Valor" name="valor" required>
            <input class="input" id="valor" name="valor" type="number" min="0" step="0.01" disabled>
          </x-ui.field>

          <div class="hidden" data-bloque-numeracion>
            <x-ui.field label="Numeración" name="numeracion" required>
              <div class="flex">
                <span class="hidden items-center rounded-l-lg border border-r-0 border-line bg-surface-2 px-3 text-sm text-muted" data-prefijo></span>
                <input class="input" id="numeracion" name="numeracion" maxlength="30" placeholder="Número del ticket" disabled data-numeracion>
              </div>
            </x-ui.field>
          </div>

          @if ($puedeEstadoPago)
            <div class="space-y-2" data-bloque-estado hidden>
              <p class="text-sm font-medium">Estado <span class="text-danger">*</span></p>
              <label class="mr-4 text-sm"><input type="radio" name="estado_pago" value="por_cancelar" checked> Por cancelar</label>
              <label class="text-sm"><input type="radio" name="estado_pago" value="pagado"> Pagado</label>
            </div>
          @elseif ($soloMetodosPago)
            <input type="hidden" name="estado_pago" value="pagado">
          @else
            <input type="hidden" name="estado_pago" value="por_cancelar">
          @endif

          <div class="space-y-2" data-bloque-pago hidden>
            <p class="text-sm font-medium">Forma de pago</p>
            <p class="text-sm text-muted" data-pago-pendiente hidden>Pendiente</p>
            <div data-pago-metodos>
              <label class="mr-4 text-sm"><input type="radio" name="forma_pago" value="efectivo" checked> Efectivo</label>
              <label class="text-sm"><input type="radio" name="forma_pago" value="transferencia"> Transferencia</label>
            </div>
          </div>
        </div>
      </section>

      <section class="space-y-4">
        <h3 class="border-b border-line pb-2 text-sm font-semibold text-ink">Datos personales</h3>
        <div class="grid gap-5 sm:grid-cols-2">
          <x-ui.field label="Nombre" name="nombre" required>
            <input class="input" id="nombre" name="nombre" required maxlength="100" value="{{ old('nombre') }}" disabled>
          </x-ui.field>

          <x-ui.field label="Teléfono" name="telefono" required>
            <input class="input" id="telefono" name="telefono" required maxlength="30" value="{{ old('telefono') }}" inputmode="tel" disabled>
          </x-ui.field>

          <x-ui.field label="Fecha de registro" name="fecha" required>
            <input class="input" id="fecha" name="fecha" type="date" required value="{{ old('fecha', now()->toDateString()) }}" disabled>
          </x-ui.field>

          <x-ui.field label="Observación" name="observacion" class="sm:col-span-2">
            <textarea class="input" id="observacion" name="observacion" rows="2" maxlength="500" disabled>{{ old('observacion') }}</textarea>
          </x-ui.field>
        </div>
      </section>

      <section class="space-y-4" data-bloque-adicionales>
        <h3 class="border-b border-line pb-2 text-sm font-semibold text-ink">Datos adicionales</h3>
        <p class="text-sm text-muted" data-adicionales-vacio>Seleccione un evento para ver si pide datos extra.</p>
        <div class="grid gap-5 sm:grid-cols-2" data-campos-adicionales></div>
      </section>

      @unless ($enPopup)
        <div>
          <x-ui.button type="submit" icon="check-lg" data-submit-registro disabled>Registrar</x-ui.button>
        </div>
      @endunless
    </form>
@endif
