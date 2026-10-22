<?php

namespace App\Http\Controllers;

use App\Models\CasaVida;
use App\Models\Lider;
use App\Models\PiramideNodo;
use App\Models\Territorio;
use App\Models\TerritorioCategoria;
use App\Models\TerritorioCategoriaVinculo;
use App\Support\Actividad;
use App\Support\Permisos;
use App\Support\Piramide;
use App\Support\Texto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Throwable;

class EstructuraController extends Controller
{
    public function inicio(Request $request): View|RedirectResponse
    {
        $rol = $request->user()->rolNormalizado();
        $permitidas = Permisos::pestanasEstructura($rol);

        if ($permitidas === []) {
            return redirect(Permisos::urlInicio($rol));
        }

        if ($request->filled('pestaña')) {
            return redirect(Permisos::rutaEstructura($this->pestana((string) $request->query('pestaña'), $permitidas)));
        }

        return redirect(Permisos::rutaEstructura($permitidas[0]));
    }

    public function index(Request $request, string $seccion = ''): View|RedirectResponse
    {
        $rol = $request->user()->rolNormalizado();
        $permitidas = Permisos::pestanasEstructura($rol);

        if ($permitidas === []) {
            return redirect(Permisos::urlInicio($rol));
        }

        $pestana = $this->pestana($seccion !== '' ? $seccion : (string) $request->query('pestaña', $permitidas[0]), $permitidas);
        $errorBd = null;
        $registros = null;
        $territorios = collect();
        $categorias = collect();
        $miembros = collect();
        $piramide = collect();
        $ocupadosPiramide = collect();

        try {
            if ($pestana === 'lideres') {
                $registros = $this->consultaMiembros($request)->paginate(20)->withQueryString();
            }

            if ($pestana === 'territorios') {
                TerritorioCategoria::asegurarTablas();
                $categorias = TerritorioCategoria::query()->orderBy('orden')->orderBy('nombre')->get();
                $miembros = Lider::query()->orderBy('apellido')->orderBy('nombre')->get();
                $registros = Territorio::query()
                    ->with(['asignaciones.miembro', 'vinculoCategoria.categoria'])
                    ->when($this->buscar($request), function ($consulta, $like) {
                        $consulta->where(function ($grupo) use ($like) {
                            $grupo->where('nombre', 'like', $like)
                                ->orWhereHas('vinculoCategoria.categoria', fn ($categoria) => $categoria->where('nombre', 'like', $like));
                        });
                    })
                    ->orderBy('orden')
                    ->orderBy('nombre')
                    ->paginate(20)
                    ->withQueryString();
            }

            if ($pestana === 'piramide') {
                $miembros = Lider::query()->orderBy('apellido')->orderBy('nombre')->get();
                $piramide = Piramide::arbol();
                $ocupadosPiramide = collect();
            }

            if ($pestana === 'casas') {
                TerritorioCategoria::asegurarTablas();
                $territorios = Territorio::query()
                    ->deCategoria('Territorio')
                    ->orderBy('orden')
                    ->orderBy('nombre')
                    ->get();
                $miembros = Lider::query()->orderBy('apellido')->orderBy('nombre')->get();
                $registros = $this->consultaCasas($request)->paginate(20)->withQueryString();
            }
        } catch (Throwable) {
            $errorBd = 'No se pudieron cargar los datos de esta sección. Confirma que MySQL está iniciado.';
        }

        return view('estructura.index', [
            'tituloPagina' => 'Estructura CDA',
            'titulo' => 'Estructura CDA',
            'seccionActiva' => 'estructura',
            'pestaña' => $pestana,
            'permitidas' => $permitidas,
            'menuEstructura' => Permisos::menuEstructura($rol),
            'registros' => $registros,
            'territorios' => $territorios,
            'categorias' => $categorias,
            'opcionesCategorias' => $categorias->pluck('nombre', 'id')->all(),
            'miembros' => $miembros,
            'opcionesTerritorios' => $territorios->pluck('nombre', 'id')->all(),
            'opcionesMiembros' => $miembros->mapWithKeys(fn (Lider $miembro) => [$miembro->id => $miembro->nombreCompleto()])->all(),
            'piramide' => $piramide,
            'ocupadosPiramide' => $ocupadosPiramide,
            'miembrosAsignacion' => $miembros->map(fn (Lider $miembro) => [
                'id' => $miembro->id,
                'nombre' => $miembro->nombreCompleto(),
                'cedula' => (string) ($miembro->cedula ?: ''),
            ])->values(),
            'buscar' => trim((string) $request->query('buscar', '')),
            'puedeAgregar' => in_array($pestana, $permitidas, true),
            'puedeEditar' => Permisos::controlTotal($rol),
            'errorBd' => $errorBd,
        ]);
    }

    public function guardarMiembro(Request $request): RedirectResponse
    {
        $this->exigirPestana($request, 'lideres');

        $datos = $this->datosMiembro($request);
        $registro = Lider::query()->create($datos);

        Actividad::registrar('crear_miembro', 'estructura', 'lider', $registro->id, 'Crear miembro');

        return redirect()->route('estructura.miembros')->with('ok', 'Miembro registrado correctamente.');
    }

    public function actualizarMiembro(Request $request, Lider $lider): RedirectResponse
    {
        abort_unless(Permisos::controlTotal($request->user()->rolNormalizado()), 403);

        $lider->fill($this->datosMiembro($request))->save();

        Actividad::registrar('actualizar_miembro', 'estructura', 'lider', $lider->id, 'Editar miembro');

        return redirect()->route('estructura.miembros')->with('ok', 'Miembro actualizado correctamente.');
    }

    public function eliminarMiembro(Request $request, Lider $lider): RedirectResponse
    {
        abort_unless(Permisos::controlTotal($request->user()->rolNormalizado()), 403);

        $id = $lider->id;

        DB::table('territorio_asignaciones')->where('miembro_id', $id)->delete();
        DB::table('miembro_parentescos')->where(function ($consulta) use ($id) {
            $consulta->where('miembro_id', $id)->orWhere('pariente_id', $id);
        })->delete();
        DB::table('miembro_cursos')->where('miembro_id', $id)->delete();
        if (DB::getSchemaBuilder()->hasTable('piramide_equipo_miembros')) {
            DB::table('piramide_equipo_miembros')->where('lider_id', $id)->delete();
        }
        $lider->delete();

        Actividad::registrar('eliminar_miembro', 'estructura', 'lider', $id, 'Eliminar miembro');

        return redirect()->route('estructura.miembros')->with('ok', 'Miembro eliminado.');
    }

    public function guardarTerritorio(Request $request): RedirectResponse
    {
        $this->exigirPestana($request, 'territorios');

        [$nombre, $coordinadores, $encargados, $categoriaId] = $this->datosTerritorio($request);

        $registro = DB::transaction(function () use ($nombre, $coordinadores, $encargados, $categoriaId) {
            $territorio = Territorio::query()->create([
                'nombre' => $nombre,
                'orden' => ((int) Territorio::query()->max('orden')) + 1,
            ]);

            $this->sincronizarAsignaciones($territorio, $coordinadores, $encargados);
            $this->sincronizarCategoria($territorio, $categoriaId);

            return $territorio;
        });

        Actividad::registrar('crear_territorio', 'estructura', 'territorio', $registro->id, 'Crear ministerio');

        return redirect()->route('estructura.ministerios')->with('ok', 'Ministerio creado correctamente.');
    }

    public function actualizarTerritorio(Request $request, Territorio $territorio): RedirectResponse
    {
        abort_unless(Permisos::controlTotal($request->user()->rolNormalizado()), 403);

        [$nombre, $coordinadores, $encargados, $categoriaId] = $this->datosTerritorio($request);

        DB::transaction(function () use ($territorio, $nombre, $coordinadores, $encargados, $categoriaId) {
            $territorio->fill(['nombre' => $nombre])->save();
            $this->sincronizarAsignaciones($territorio, $coordinadores, $encargados);
            $this->sincronizarCategoria($territorio, $categoriaId);
        });

        Actividad::registrar('actualizar_territorio', 'estructura', 'territorio', $territorio->id, 'Editar ministerio');

        return redirect()->route('estructura.ministerios')->with('ok', 'Ministerio actualizado correctamente.');
    }

    public function eliminarTerritorio(Request $request, Territorio $territorio): RedirectResponse
    {
        abort_unless(Permisos::controlTotal($request->user()->rolNormalizado()), 403);

        $id = $territorio->id;
        TerritorioCategoria::asegurarTablas();
        $territorio->vinculoCategoria()->delete();
        $territorio->asignaciones()->delete();
        $territorio->delete();

        Actividad::registrar('eliminar_territorio', 'estructura', 'territorio', $id, 'Eliminar ministerio');

        return redirect()->route('estructura.ministerios')->with('ok', 'Ministerio eliminado.');
    }

    public function guardarPiramide(Request $request): RedirectResponse
    {
        $this->exigirPestana($request, 'piramide');
        Piramide::asegurarTabla();

        [$nombre, $padre, $lideres] = $this->datosEquipoPiramide($request);

        $equipo = DB::transaction(function () use ($nombre, $padre, $lideres) {
            $orden = (int) PiramideNodo::query()
                ->when($padre, fn ($consulta) => $consulta->where('padre_id', $padre->id), fn ($consulta) => $consulta->whereNull('padre_id'))
                ->max('orden');

            $datos = [
                'nombre' => $nombre,
                'padre_id' => $padre?->id,
                'orden' => $orden + 1,
            ];

            if (Schema::hasColumn('piramide_equipos', 'nivel')) {
                $datos['nivel'] = $padre ? ((int) ($padre->getAttribute('nivel') ?: 1)) + 1 : 1;
            }

            if (Schema::hasColumn('piramide_equipos', 'lider_id')) {
                $datos['lider_id'] = $lideres->first();
            }

            $registro = PiramideNodo::query()->create($datos);

            $this->sincronizarMiembrosPiramide($registro, $lideres);

            return $registro;
        });

        Actividad::registrar('asignar_piramide', 'estructura', 'piramide', $equipo->id, 'Crear equipo · '.$nombre);

        return redirect()->route('estructura.piramide')->with('ok', 'Equipo creado correctamente.');
    }

    public function actualizarPiramide(Request $request, PiramideNodo $nodo): RedirectResponse
    {
        abort_unless(Permisos::controlTotal($request->user()->rolNormalizado()), 403);
        Piramide::asegurarTabla();

        [$nombre, , $lideres] = $this->datosEquipoPiramide($request, false);

        DB::transaction(function () use ($nodo, $nombre, $lideres) {
            $nodo->fill(['nombre' => $nombre])->save();
            $this->sincronizarMiembrosPiramide($nodo, $lideres);
        });

        Actividad::registrar('actualizar_piramide', 'estructura', 'piramide', $nodo->id, 'Editar equipo · '.$nombre);

        return redirect()->route('estructura.piramide')->with('ok', 'Equipo actualizado correctamente.');
    }

    public function eliminarPiramide(Request $request, PiramideNodo $nodo): RedirectResponse
    {
        abort_unless(Permisos::controlTotal($request->user()->rolNormalizado()), 403);
        Piramide::asegurarTabla();

        $this->eliminarRama($nodo);

        Actividad::registrar('quitar_piramide', 'estructura', 'piramide', $nodo->id, 'Quitar equipo de la pirámide');

        return redirect()->route('estructura.piramide')->with('ok', 'Se quitó el equipo y los que dependían de él.');
    }

    public function guardarCasa(Request $request): RedirectResponse
    {
        $this->exigirPestana($request, 'casas');

        $registro = CasaVida::query()->create($this->datosCasa($request));

        Actividad::registrar('crear_casa', 'estructura', 'casa_vida', $registro->id, 'Crear casa de vida');

        return redirect()->route('estructura.casas')->with('ok', 'Casa de vida registrada correctamente.');
    }

    public function actualizarCasa(Request $request, CasaVida $casa): RedirectResponse
    {
        abort_unless(Permisos::controlTotal($request->user()->rolNormalizado()), 403);

        $casa->fill($this->datosCasa($request, $casa))->save();

        Actividad::registrar('actualizar_casa', 'estructura', 'casa_vida', $casa->id, 'Editar casa de vida');

        return redirect()->route('estructura.casas')->with('ok', 'Casa de vida actualizada correctamente.');
    }

    public function eliminarCasa(Request $request, CasaVida $casa): RedirectResponse
    {
        abort_unless(Permisos::controlTotal($request->user()->rolNormalizado()), 403);

        $id = $casa->id;
        $casa->delete();

        Actividad::registrar('eliminar_casa', 'estructura', 'casa_vida', $id, 'Eliminar casa de vida');

        return redirect()->route('estructura.casas')->with('ok', 'Casa de vida eliminada.');
    }

    /**
     * @return array<string, string>
     */
    private function datosMiembro(Request $request): array
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'genero' => ['required', 'in:masculino,femenino'],
            'cedula' => ['nullable', 'string', 'max:30'],
            'celular' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
            'notas' => ['nullable', 'string', 'max:500'],
        ]);

        return [
            'nombre' => Texto::titulo($datos['nombre']),
            'apellido' => Texto::titulo($datos['apellido']),
            'genero' => $datos['genero'],
            'cedula' => trim((string) ($datos['cedula'] ?? '')),
            'celular' => trim((string) ($datos['celular'] ?? '')),
            'email' => Texto::email($datos['email'] ?? ''),
            'notas' => trim((string) ($datos['notas'] ?? '')),
        ];
    }

    /**
     * @return array{0: string, 1: \Illuminate\Support\Collection<int, int>, 2: \Illuminate\Support\Collection<int, int>, 3: int|null}
     */
    private function datosTerritorio(Request $request): array
    {
        TerritorioCategoria::asegurarTablas();

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'categoria_id' => ['nullable', 'integer'],
            'categoria_nueva' => ['nullable', 'string', 'max:100'],
            'coordinador_ids' => ['nullable', 'array'],
            'coordinador_ids.*' => ['integer', 'exists:lideres,id'],
            'encargado_ids' => ['nullable', 'array'],
            'encargado_ids.*' => ['integer', 'exists:lideres,id'],
        ]);

        $coordinadores = collect($datos['coordinador_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->filter()
            ->values();

        $encargados = collect($datos['encargado_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->filter()
            ->reject(fn (int $id) => $coordinadores->contains($id))
            ->values();

        return [Texto::titulo($datos['nombre']), $coordinadores, $encargados, $this->resolverCategoria($datos)];
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function resolverCategoria(array $datos): ?int
    {
        $nueva = Texto::titulo(trim((string) ($datos['categoria_nueva'] ?? '')));

        if ($nueva !== '') {
            $existente = TerritorioCategoria::query()
                ->whereRaw('LOWER(nombre) = ?', [mb_strtolower($nueva)])
                ->first();

            if ($existente) {
                return (int) $existente->id;
            }

            $creada = TerritorioCategoria::query()->create([
                'nombre' => $nueva,
                'orden' => ((int) TerritorioCategoria::query()->max('orden')) + 1,
            ]);

            return (int) $creada->id;
        }

        $id = (int) ($datos['categoria_id'] ?? 0);

        if ($id <= 0) {
            return null;
        }

        abort_unless(TerritorioCategoria::query()->whereKey($id)->exists(), 422, 'La categoría seleccionada no existe.');

        return $id;
    }

    private function sincronizarCategoria(Territorio $territorio, ?int $categoriaId): void
    {
        TerritorioCategoria::asegurarTablas();

        if (! $categoriaId) {
            $territorio->vinculoCategoria()->delete();

            return;
        }

        TerritorioCategoriaVinculo::query()->updateOrCreate(
            ['territorio_id' => $territorio->id],
            ['categoria_id' => $categoriaId]
        );
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $coordinadores
     * @param  \Illuminate\Support\Collection<int, int>  $encargados
     */
    private function sincronizarAsignaciones(Territorio $territorio, $coordinadores, $encargados): void
    {
        $territorio->asignaciones()->delete();

        $ids = $coordinadores->merge($encargados)->unique()->values();
        $miembros = Lider::query()->whereIn('id', $ids)->get()->keyBy('id');

        foreach (['coordinador' => $coordinadores, 'encargado' => $encargados] as $rol => $seleccion) {
            foreach ($seleccion as $miembroId) {
                $miembro = $miembros->get($miembroId);

                if (! $miembro) {
                    continue;
                }

                $territorio->asignaciones()->create([
                    'miembro_id' => $miembroId,
                    'rol' => $rol,
                    'pareja' => $miembro->genero === 'femenino' ? 'esposa' : 'esposo',
                ]);
            }
        }
    }

    /**
     * @return array<string, int|string>
     */
    private function datosCasa(Request $request, ?CasaVida $actual = null): array
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'territorio_id' => ['required', 'integer', 'exists:territorios,id'],
            'lider_id' => ['required', 'integer', 'exists:lideres,id'],
            'colaborador_id' => ['nullable', 'integer'],
            'anfitrion_id' => ['nullable', 'integer'],
            'direccion' => ['required', 'string', 'max:255'],
        ]);

        $territorioId = (int) $datos['territorio_id'];
        $this->exigirMinisterioDeTerritorio($territorioId, $actual?->territorio_id);

        $colaborador = (int) ($datos['colaborador_id'] ?? 0);
        $anfitrion = (int) ($datos['anfitrion_id'] ?? 0);

        if ($colaborador > 0) {
            abort_unless(Lider::query()->whereKey($colaborador)->exists(), 422, 'El colaborador seleccionado no existe.');
        }

        if ($anfitrion > 0) {
            abort_unless(Lider::query()->whereKey($anfitrion)->exists(), 422, 'El anfitrión seleccionado no existe.');
        }

        return [
            'nombre' => Texto::titulo($datos['nombre']),
            'territorio_id' => $territorioId,
            'lider_id' => (int) $datos['lider_id'],
            'colaborador_id' => $colaborador,
            'anfitrion_id' => $anfitrion,
            'direccion' => Texto::titulo($datos['direccion']),
        ];
    }

    private function exigirMinisterioDeTerritorio(int $territorioId, ?int $actualId = null): void
    {
        if ($actualId && $territorioId === (int) $actualId) {
            return;
        }

        TerritorioCategoria::asegurarTablas();

        if (Territorio::query()->deCategoria('Territorio')->whereKey($territorioId)->exists()) {
            return;
        }

        throw ValidationException::withMessages([
            'territorio_id' => 'Solo puedes elegir un ministerio de la categoría Territorio.',
        ]);
    }

    private function consultaMiembros(Request $request)
    {
        return Lider::query()
            ->when($this->buscar($request), function ($consulta, $like) {
                $consulta->where(function ($grupo) use ($like) {
                    $grupo->where('nombre', 'like', $like)
                        ->orWhere('apellido', 'like', $like)
                        ->orWhere('cedula', 'like', $like)
                        ->orWhere('celular', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->orderBy('apellido')
            ->orderBy('nombre');
    }

    private function consultaCasas(Request $request)
    {
        return CasaVida::query()
            ->with(['territorio', 'lider', 'colaborador', 'anfitrion'])
            ->when($this->buscar($request), function ($consulta, $like) {
                $consulta->where(function ($grupo) use ($like) {
                    $grupo->where('nombre', 'like', $like)
                        ->orWhere('direccion', 'like', $like)
                        ->orWhereHas('territorio', fn ($territorio) => $territorio->where('nombre', 'like', $like))
                        ->orWhereHas('lider', function ($lider) use ($like) {
                            $lider->where('nombre', 'like', $like)->orWhere('apellido', 'like', $like);
                        });
                });
            })
            ->orderByDesc('creado_en')
            ->orderByDesc('id');
    }

    /**
     * @return array{0: string, 1: ?PiramideNodo, 2: \Illuminate\Support\Collection<int, int>}
     */
    private function datosEquipoPiramide(Request $request, bool $conPadre = true): array
    {
        $request->merge([
            'padre_id' => $request->filled('padre_id') ? $request->input('padre_id') : null,
        ]);

        $reglas = [
            'nombre' => ['required', 'string', 'max:100'],
            'lider_ids' => ['required', 'array', 'min:1'],
            'lider_ids.*' => ['integer', 'exists:lideres,id'],
        ];

        if ($conPadre) {
            $reglas['padre_id'] = ['nullable', 'integer', 'exists:piramide_equipos,id'];
        }

        $datos = $request->validate($reglas);
        $padre = ($conPadre && isset($datos['padre_id']))
            ? PiramideNodo::query()->find((int) $datos['padre_id'])
            : null;

        $lideres = collect($datos['lider_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->filter()
            ->values();

        return [Texto::titulo($datos['nombre']), $padre, $lideres];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, int>  $lideres
     */
    private function sincronizarMiembrosPiramide(PiramideNodo $equipo, $lideres): void
    {
        $equipo->miembros()->whereNotIn('lider_id', $lideres)->delete();

        $existentes = $equipo->miembros()->pluck('lider_id')->map(fn ($id) => (int) $id);
        $orden = (int) $equipo->miembros()->max('orden');

        foreach ($lideres as $liderId) {
            if ($existentes->contains($liderId)) {
                continue;
            }

            $orden++;
            $equipo->miembros()->create([
                'lider_id' => $liderId,
                'orden' => $orden,
            ]);
        }
    }

    private function eliminarRama(PiramideNodo $nodo): void
    {
        $nodo->loadMissing('hijos');

        foreach ($nodo->hijos as $hijo) {
            $this->eliminarRama($hijo);
        }

        $nodo->miembros()->delete();
        $nodo->delete();
    }

    /**
     * @param  array<int, string>  $permitidas
     */
    private function pestana(string $pestana, array $permitidas): string
    {
        $pestana = match ($pestana) {
            'miembros' => 'lideres',
            default => $pestana,
        };

        return in_array($pestana, $permitidas, true) ? $pestana : $permitidas[0];
    }

    private function exigirPestana(Request $request, string $pestana): void
    {
        abort_unless(in_array($pestana, Permisos::pestanasEstructura($request->user()->rolNormalizado()), true), 403);
    }

    private function buscar(Request $request): ?string
    {
        $buscar = trim((string) $request->query('buscar', ''));

        return $buscar === '' ? null : '%'.addcslashes($buscar, '%_\\').'%';
    }
}
