@extends('layouts.app')

@section('content')
@php
  $abrirMiembro = $errors->any() && old('_formulario') === 'miembro' && $pestaña === 'lideres';
  $abrirTerritorio = $errors->any() && old('_formulario') === 'territorio' && $pestaña === 'territorios';
  $abrirCasa = $errors->any() && old('_formulario') === 'casa' && $pestaña === 'casas';
  $abrirPiramide = $errors->any() && old('_formulario') === 'piramide' && $pestaña === 'piramide';
  $puedeCrearCasa = $puedeAgregar && $territorios->isNotEmpty() && $miembros->isNotEmpty();
  $itemActual = collect($menuEstructura ?? [])->firstWhere('clave', $pestaña);
  $tituloSeccion = $itemActual['etiqueta'] ?? 'Estructura CDA';
  $subtitulo = $pestaña === 'piramide'
    ? 'Equipos con nombre, varios miembros y niveles sin límite.'
    : 'Primero registra los miembros. Luego crea ministerios y casas de vida.';
@endphp

<x-ui.page-header :title="$tituloSeccion" :subtitle="$subtitulo">
  <x-slot:actions>
    @if (! $errorBd && $registros && method_exists($registros, 'total'))
      <x-ui.badge tone="accent">{{ $registros->total() }} registro(s)</x-ui.badge>
    @endif
    @if ($puedeAgregar && ! $errorBd && $pestaña === 'lideres')
      <x-ui.button type="button" icon="plus-lg" data-modal-open="dialogNuevoMiembro">Nuevo miembro</x-ui.button>
    @endif
    @if ($puedeAgregar && ! $errorBd && $pestaña === 'territorios')
      <x-ui.button type="button" icon="plus-lg" data-modal-open="dialogNuevoTerritorio">Nuevo ministerio</x-ui.button>
    @endif
    @if ($puedeCrearCasa && ! $errorBd && $pestaña === 'casas')
      <x-ui.button type="button" icon="plus-lg" data-modal-open="dialogNuevaCasa">Nueva casa</x-ui.button>
    @endif
    @if ($puedeAgregar && ! $errorBd && $pestaña === 'piramide')
      <x-ui.button
        type="button"
        variant="secondary"
        icon="pencil"
        data-piramide-toggle
        :aria-pressed="$abrirPiramide ? 'true' : 'false'"
        title="Activar o desactivar edición"
      >Agregar equipo</x-ui.button>
    @endif
  </x-slot:actions>
</x-ui.page-header>

@if (session('ok'))
  <x-ui.alert type="success" dismissible class="mb-6">{{ session('ok') }}</x-ui.alert>
@endif

@if ($errors->any())
  <x-ui.alert type="danger" dismissible class="mb-6">{{ $errors->first() }}</x-ui.alert>
@endif

@if ($errorBd)
  <x-ui.alert type="warning">{{ $errorBd }}</x-ui.alert>
@else
  @if ($pestaña === 'lideres')
    @include('estructura.partials.miembros')
  @elseif ($pestaña === 'territorios')
    @include('estructura.partials.territorios')
  @elseif ($pestaña === 'casas')
    @include('estructura.partials.casas')
  @elseif ($pestaña === 'piramide')
    @include('estructura.partials.piramide')
  @endif
@endif

@if ($puedeAgregar && $pestaña === 'lideres' && ! $errorBd)
  <x-ui.modal id="dialogNuevoMiembro" title="Nuevo miembro" description="Los campos con * son obligatorios." :open="$abrirMiembro">
    <form id="formNuevoMiembro" method="POST" action="{{ route('estructura.miembros.guardar') }}" class="space-y-4">
      @csrf
      <input type="hidden" name="_formulario" value="miembro">
      <x-ui.field label="Nombres" name="nombre" required>
        <input class="input" id="nombre" name="nombre" value="{{ old('nombre') }}" required maxlength="100" autofocus>
      </x-ui.field>
      <x-ui.field label="Apellidos" name="apellido" required>
        <input class="input" id="apellido" name="apellido" value="{{ old('apellido') }}" required maxlength="100">
      </x-ui.field>
      <x-ui.field label="Género" name="genero" required>
        <select class="input" id="genero" name="genero" required>
          <option value="">Seleccione…</option>
          <option value="masculino" @selected(old('genero') === 'masculino')>Masculino</option>
          <option value="femenino" @selected(old('genero') === 'femenino')>Femenino</option>
        </select>
      </x-ui.field>
      <x-ui.field label="Cédula" name="cedula">
        <input class="input" id="cedula" name="cedula" value="{{ old('cedula') }}" maxlength="30">
      </x-ui.field>
      <x-ui.field label="Celular" name="celular">
        <input class="input" id="celular" name="celular" type="tel" inputmode="tel" value="{{ old('celular') }}" maxlength="30">
      </x-ui.field>
      <x-ui.field label="Correo" name="email">
        <input class="input" id="email" name="email" type="email" value="{{ old('email') }}" maxlength="100">
      </x-ui.field>
      <x-ui.field label="Notas" name="notas">
        <textarea class="input" id="notas" name="notas" rows="2" maxlength="500">{{ old('notas') }}</textarea>
      </x-ui.field>
    </form>
    <x-slot:footer>
      <x-ui.button type="button" variant="secondary" data-modal-close>Cancelar</x-ui.button>
      <x-ui.button type="submit" form="formNuevoMiembro" icon="check-lg">Crear miembro</x-ui.button>
    </x-slot:footer>
  </x-ui.modal>
@endif

@if (in_array($pestaña, ['territorios', 'piramide', 'casas'], true) && ! $errorBd)
  <script id="configMiembrosEstructura" type="application/json">{!! $miembrosAsignacion->toJson(JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endif

@if ($pestaña === 'casas' && ! $errorBd)
  <script id="configCasasTerritorios" type="application/json">{!! $territorios->map(fn ($territorio) => ['id' => $territorio->id, 'nombre' => $territorio->nombre])->values()->toJson(JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) !!}</script>
@endif

@if ($puedeAgregar && $pestaña === 'territorios' && ! $errorBd)
  <x-ui.modal id="dialogNuevoTerritorio" title="Nuevo ministerio" description="El ministerio es la subcategoría. La categoría puede quedar sin asignar." size="xl" :open="$abrirTerritorio">
    <form id="formNuevoTerritorio" method="POST" action="{{ route('estructura.territorios.guardar') }}" class="space-y-5">
      @csrf
      <input type="hidden" name="_formulario" value="territorio">
      <div class="grid gap-5 sm:grid-cols-2">
        <x-ui.field label="Categoría" name="categoria_id">
          <select class="input" id="categoria_id" name="categoria_id">
            <option value="0" @selected((string) old('categoria_id', '0') === '0')>Sin asignar</option>
            @foreach ($categorias ?? [] as $categoria)
              <option value="{{ $categoria->id }}" @selected((string) old('categoria_id') === (string) $categoria->id)>{{ $categoria->nombre }}</option>
            @endforeach
          </select>
        </x-ui.field>
        <x-ui.field label="Nueva categoría" name="categoria_nueva" hint="Si escribes un nombre, se crea y se usa esa categoría.">
          <input class="input" id="categoria_nueva" name="categoria_nueva" value="{{ old('categoria_nueva') }}" maxlength="100">
        </x-ui.field>
      </div>
      <x-ui.field label="Ministerio" name="nombre" required>
        <input class="input" id="nombre_territorio" name="nombre" value="{{ old('nombre') }}" required maxlength="100" autofocus>
      </x-ui.field>
      @if ($miembrosAsignacion->isEmpty())
        <x-ui.alert type="warning">
          Primero registra
          <a href="{{ route('estructura.miembros') }}" class="font-medium underline">miembros</a>
          para poder asignarlos.
        </x-ui.alert>
      @else
        @include('estructura.partials.asignar-miembros', [
          'coordIds' => old('coordinador_ids', []),
          'encIds' => old('encargado_ids', []),
        ])
      @endif
    </form>
    <x-slot:footer>
      <x-ui.button type="button" variant="secondary" data-modal-close>Cancelar</x-ui.button>
      <x-ui.button type="submit" form="formNuevoTerritorio" icon="check-lg">Crear ministerio</x-ui.button>
    </x-slot:footer>
  </x-ui.modal>
@endif

@if ($puedeCrearCasa && $pestaña === 'casas' && ! $errorBd)
  <x-ui.modal id="dialogNuevaCasa" title="Nueva casa de vida" description="El ministerio debe ser de la categoría Territorio." :open="$abrirCasa">
    <form id="formNuevaCasa" method="POST" action="{{ route('estructura.casas.guardar') }}" class="space-y-4">
      @csrf
      <input type="hidden" name="_formulario" value="casa">
      <x-ui.field label="Nombre de la casa" name="nombre" required>
        <input class="input" id="nombre_casa" name="nombre" value="{{ old('nombre') }}" required maxlength="100" autofocus>
      </x-ui.field>
      @php
        $territorioElegido = $territorios->firstWhere('id', (int) old('territorio_id'));
        $liderElegido = $miembros->firstWhere('id', (int) old('lider_id'));
        $coordinadorElegido = $miembros->firstWhere('id', (int) old('colaborador_id'));
        $anfitrionElegido = $miembros->firstWhere('id', (int) old('anfitrion_id'));
      @endphp
      <x-ui.field label="Territorio" name="territorio_id" required>
        @include('estructura.partials.buscar-uno', [
          'name' => 'territorio_id',
          'catalogo' => 'territorios',
          'value' => old('territorio_id'),
          'texto' => $territorioElegido?->nombre,
          'required' => true,
          'placeholder' => 'Buscar territorio por nombre',
        ])
      </x-ui.field>
      <x-ui.field label="Líder" name="lider_id" required>
        @include('estructura.partials.buscar-uno', [
          'name' => 'lider_id',
          'value' => old('lider_id'),
          'texto' => $liderElegido?->nombreCompleto(),
          'required' => true,
          'placeholder' => 'Buscar líder por nombre',
        ])
      </x-ui.field>
      <x-ui.field label="Coordinador" name="colaborador_id">
        @include('estructura.partials.buscar-uno', [
          'name' => 'colaborador_id',
          'value' => old('colaborador_id'),
          'texto' => $coordinadorElegido?->nombreCompleto(),
          'placeholder' => 'Buscar coordinador por nombre',
        ])
      </x-ui.field>
      <x-ui.field label="Anfitrión" name="anfitrion_id">
        @include('estructura.partials.buscar-uno', [
          'name' => 'anfitrion_id',
          'value' => old('anfitrion_id'),
          'texto' => $anfitrionElegido?->nombreCompleto(),
          'placeholder' => 'Buscar anfitrión por nombre',
        ])
      </x-ui.field>
      <x-ui.field label="Dirección" name="direccion" required>
        <input class="input" id="direccion" name="direccion" value="{{ old('direccion') }}" required maxlength="255">
      </x-ui.field>
    </form>
    <x-slot:footer>
      <x-ui.button type="button" variant="secondary" data-modal-close>Cancelar</x-ui.button>
      <x-ui.button type="submit" form="formNuevaCasa" icon="check-lg">Crear casa</x-ui.button>
    </x-slot:footer>
  </x-ui.modal>
@endif

@if ($puedeAgregar && $pestaña === 'piramide' && ! $errorBd)
  <x-ui.modal id="dialogPiramideNodo" title="Equipo" description="Ponle un nombre y marca a todos los miembros que lo integran." size="xl" :open="$abrirPiramide">
    <form id="formPiramideNodo" method="POST" action="{{ route('estructura.piramide.guardar') }}" class="space-y-5" data-guardar="{{ route('estructura.piramide.guardar') }}">
      @csrf
      <input type="hidden" name="_formulario" value="piramide">
      <input type="hidden" name="_method" id="piramideMetodo" value="POST">
      <input type="hidden" name="padre_id" id="piramidePadreId" value="{{ old('padre_id') }}">
      <p class="text-sm text-muted" data-piramide-contexto>Nuevo equipo</p>
      <x-ui.field label="Nombre del equipo" name="nombre" required>
        <input class="input" id="piramideNombre" name="nombre" value="{{ old('nombre') }}" required maxlength="100" placeholder="Ej. Cabezas de la iglesia">
      </x-ui.field>
      @if ($miembrosAsignacion->isEmpty())
        <x-ui.alert type="warning">
          Primero registra
          <a href="{{ route('estructura.miembros') }}" class="font-medium underline">miembros</a>.
        </x-ui.alert>
      @else
        @include('estructura.partials.marcar-miembros', [
          'seleccion' => old('lider_ids', []),
          'excluidos' => [],
        ])
      @endif
    </form>
    <x-slot:footer>
      <x-ui.button type="button" variant="secondary" data-modal-close>Cancelar</x-ui.button>
      <x-ui.button type="submit" form="formPiramideNodo" icon="check-lg">Guardar equipo</x-ui.button>
    </x-slot:footer>
  </x-ui.modal>
@endif
@endsection
