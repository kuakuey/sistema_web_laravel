<?php

namespace App\Http\Controllers;

use App\Models\Inscripcion;
use App\Models\PresentacionNino;
use App\Support\Actividad;
use App\Support\ApiPresentaciones;
use App\Support\Permisos;
use App\Support\Texto;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Throwable;

class RegistroController extends Controller
{
    public function generales(Request $request): View|RedirectResponse
    {
        return $this->responder($request, 'generales');
    }

    public function seccion(Request $request, string $seccion): View|RedirectResponse
    {
        abort_unless(in_array($seccion, ['escol', 'academia', 'bautismo', 'conexion'], true), 404);

        return $this->responder($request, $seccion);
    }

    public function inicio(Request $request, string $seccion = ''): View|RedirectResponse
    {
        $rol = $request->user()->rolNormalizado();

        if (! in_array($seccion, ['presentaciones', 'bautismo', 'conexion'], true) || ! Permisos::puedeVerItem($rol, $seccion)) {
            return redirect(Permisos::urlInicio($rol));
        }

        if ($request->filled('pestaña')) {
            return redirect(Permisos::rutaFlujo($seccion, (string) $request->query('pestaña')));
        }

        return redirect(Permisos::rutaFlujo($seccion, 'nuevos'));
    }

    public function index(Request $request, string $seccion = '', string $pestana = ''): View|RedirectResponse
    {
        return $this->responder($request, $seccion, $pestana);
    }

    public function presentaciones(Request $request): View|RedirectResponse
    {
        return $this->responder($request, 'presentaciones');
    }

    public function entregarDiploma(Request $request): View|RedirectResponse
    {
        $rol = $request->user()->rolNormalizado();

        if (! Permisos::puedeVerItem($rol, 'presentaciones')) {
            return redirect(Permisos::urlInicio($rol));
        }

        $errorBd = null;
        $registros = collect();

        try {
            $registros = PresentacionNino::query()
                ->whereIn('estado', ['contactado', 'confirmado'])
                ->orderBy('nombre_presentado')
                ->orderByDesc('id')
                ->limit(300)
                ->get();
        } catch (Throwable) {
            $errorBd = 'No se pudieron cargar los registros. Confirma que MySQL está iniciado y que la base existe.';
        }

        return view('presentaciones.entregar-diploma', [
            'tituloPagina' => 'Entregar diploma',
            'tituloBarra' => 'Entregar diploma',
            'registros' => $registros,
            'errorBd' => $errorBd,
        ]);
    }

    public function guardarInscripcion(Request $request): RedirectResponse
    {
        $rol = $request->user()->rolNormalizado();
        $seccion = (string) $request->input('seccion', '');
        $tipo = (string) $request->input('tipo_formulario', $seccion);

        if (! in_array($tipo, Permisos::tiposInscripcion($rol), true) || ! Permisos::puedeRegistrar($rol, $seccion === 'generales' ? $tipo : $seccion)) {
            abort(403);
        }

        $reglas = [
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'celular' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
        ];

        if ($tipo === 'conexion') {
            $reglas['zona'] = ['required', 'in:'.implode(',', array_keys(Permisos::zonas()))];
            $reglas['direccion'] = ['required', 'string', 'max:255'];
        }

        $datos = $request->validate($reglas);

        $registro = Inscripcion::query()->create([
            'tipo_formulario' => $tipo,
            'nombre' => Texto::titulo($datos['nombre']),
            'apellido' => Texto::titulo($datos['apellido']),
            'celular' => trim($datos['celular']),
            'email' => isset($datos['email']) ? Texto::email($datos['email']) : null,
            'zona' => $tipo === 'conexion' ? ($datos['zona'] ?? '') : '',
            'direccion' => $tipo === 'conexion' ? Texto::titulo($datos['direccion'] ?? '') : '',
            'estado_bautismo' => 'ingresado',
            'ip_cliente' => $request->ip(),
            'agente_usuario' => 'Sistema Web — '.$request->user()->usuario,
        ]);

        Actividad::registrar('crear_inscripcion', $tipo, 'inscripcion', $registro->id, 'Crear inscripción');

        $destino = $seccion === 'generales' ? 'generales' : $tipo;

        if ($destino === 'generales') {
            return redirect()->route('generales')->with('ok', 'Registro guardado correctamente.');
        }

        if (in_array($tipo, ['conexion', 'bautismo'], true)) {
            return redirect(Permisos::rutaFlujo($tipo, 'nuevos'))->with('ok', 'Registro guardado correctamente.');
        }

        return redirect(Permisos::url($destino))->with('ok', 'Registro guardado correctamente.');
    }

    public function guardarPresentacion(Request $request): RedirectResponse
    {
        $rol = $request->user()->rolNormalizado();
        abort_unless(Permisos::puedeRegistrar($rol, 'presentaciones'), 403);

        $datos = $request->validate([
            'parentesco_representante_1' => ['required', 'string', 'max:30'],
            'nombre_padre' => ['required', 'string', 'max:100'],
            'telefono_papa' => ['required', 'string', 'max:30'],
            'parentesco_representante_2' => ['nullable', 'string', 'max:30'],
            'nombre_madre' => ['nullable', 'string', 'max:100'],
            'telefono_mama' => ['nullable', 'string', 'max:30'],
            'nombre_presentado' => ['required', 'string', 'max:100'],
            'fecha_nacimiento' => ['nullable', 'date'],
        ]);

        $registro = PresentacionNino::query()->create([
            'parentesco_representante_1' => Texto::titulo($datos['parentesco_representante_1']),
            'nombre_padre' => Texto::titulo($datos['nombre_padre']),
            'telefono_papa' => trim($datos['telefono_papa']),
            'parentesco_representante_2' => Texto::titulo($datos['parentesco_representante_2'] ?? ''),
            'nombre_madre' => Texto::titulo($datos['nombre_madre'] ?? ''),
            'telefono_mama' => trim((string) ($datos['telefono_mama'] ?? '')),
            'nombre_presentado' => Texto::titulo($datos['nombre_presentado']),
            'fecha_nacimiento' => $datos['fecha_nacimiento'] ?? null,
            'estado' => 'recibido',
            'ip_cliente' => $request->ip(),
            'agente_usuario' => 'Sistema Web — '.$request->user()->usuario,
        ]);

        Actividad::registrar('crear_presentacion', 'presentaciones', 'presentacion', $registro->id, 'Crear presentación');

        return redirect()->route('presentaciones.nuevos')->with('ok', 'Presentación registrada correctamente.');
    }

    public function actualizarInscripcion(Request $request, Inscripcion $inscripcion): RedirectResponse
    {
        abort_unless(Permisos::controlTotal($request->user()->rolNormalizado()), 403);

        $reglas = [
            'nombre' => ['required', 'string', 'max:100'],
            'apellido' => ['required', 'string', 'max:100'],
            'celular' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:100'],
        ];

        if ($inscripcion->tipo_formulario === 'conexion') {
            $reglas['zona'] = ['required', 'in:'.implode(',', array_keys(Permisos::zonas()))];
            $reglas['direccion'] = ['required', 'string', 'max:255'];
            $reglas['contactado'] = ['required', 'in:0,1'];
        }

        $datos = $request->validate($reglas);

        $inscripcion->nombre = Texto::titulo($datos['nombre']);
        $inscripcion->apellido = Texto::titulo($datos['apellido']);
        $inscripcion->celular = trim($datos['celular']);
        $inscripcion->email = isset($datos['email']) ? Texto::email($datos['email']) : null;

        if ($inscripcion->tipo_formulario === 'conexion') {
            $inscripcion->zona = $datos['zona'];
            $inscripcion->direccion = Texto::titulo($datos['direccion']);
            $inscripcion->contactado = (bool) (int) $datos['contactado'];
        }

        $inscripcion->save();
        Actividad::registrar('actualizar_inscripcion', $inscripcion->tipo_formulario, 'inscripcion', $inscripcion->id, 'Editar inscripción');

        return back()->with('ok', 'Registro actualizado correctamente.');
    }

    public function actualizarPresentacion(Request $request, PresentacionNino $presentacion): RedirectResponse
    {
        abort_unless(Permisos::controlTotal($request->user()->rolNormalizado()), 403);

        $datos = $request->validate([
            'parentesco_representante_1' => ['required', 'string', 'max:30'],
            'nombre_padre' => ['required', 'string', 'max:100'],
            'telefono_papa' => ['required', 'string', 'max:30'],
            'parentesco_representante_2' => ['nullable', 'string', 'max:30'],
            'nombre_madre' => ['nullable', 'string', 'max:100'],
            'telefono_mama' => ['nullable', 'string', 'max:30'],
            'nombre_presentado' => ['required', 'string', 'max:100'],
            'fecha_nacimiento' => ['nullable', 'date'],
        ]);

        $presentacion->fill([
            'parentesco_representante_1' => $datos['parentesco_representante_1'],
            'nombre_padre' => Texto::titulo($datos['nombre_padre']),
            'telefono_papa' => trim($datos['telefono_papa']),
            'parentesco_representante_2' => $datos['parentesco_representante_2'] ?? '',
            'nombre_madre' => Texto::titulo($datos['nombre_madre'] ?? ''),
            'telefono_mama' => trim((string) ($datos['telefono_mama'] ?? '')),
            'nombre_presentado' => Texto::titulo($datos['nombre_presentado']),
            'fecha_nacimiento' => $datos['fecha_nacimiento'] ?? null,
        ]);

        $presentacion->save();
        Actividad::registrar('actualizar_presentacion', 'presentaciones', 'presentacion', $presentacion->id, 'Editar presentación');

        return back()->with('ok', 'Presentación actualizada correctamente.');
    }

    public function estadoInscripcion(Request $request, Inscripcion $inscripcion): RedirectResponse
    {
        $rol = $request->user()->rolNormalizado();
        $accion = (string) $request->input('accion');

        if ($accion === 'contactado') {
            abort_unless($inscripcion->tipo_formulario === 'conexion' && Permisos::puedeGestionarEstadoConexion($rol), 403);
            $inscripcion->contactado = true;
            $inscripcion->save();
            Actividad::registrar('actualizar_estado_conexion', 'conexion', 'inscripcion', $inscripcion->id, 'Marcar contactado');
        } elseif ($accion === 'bautizado') {
            abort_unless($inscripcion->tipo_formulario === 'bautismo' && Permisos::tieneSeccion($rol, 'bautismo'), 403);
            $inscripcion->estado_bautismo = 'bautizado';
            $inscripcion->fecha_bautismo = now()->toDateString();
            $inscripcion->save();
            Actividad::registrar('actualizar_estado_bautismo', 'bautismo', 'inscripcion', $inscripcion->id, 'Marcar bautizado');
        } elseif ($accion === 'restablecer') {
            abort_unless(Permisos::controlTotal($rol), 403);

            if ($inscripcion->tipo_formulario === 'bautismo') {
                $inscripcion->estado_bautismo = 'ingresado';
                $inscripcion->fecha_bautismo = null;
            }

            if ($inscripcion->tipo_formulario === 'conexion') {
                $inscripcion->contactado = false;
            }

            $inscripcion->save();
            Actividad::registrar('actualizar_inscripcion', $inscripcion->tipo_formulario, 'inscripcion', $inscripcion->id, 'Restablecer estado');
        } else {
            abort(404);
        }

        return back()->with('ok', 'Registro actualizado correctamente.');
    }

    public function estadoPresentacion(Request $request, PresentacionNino $presentacion): RedirectResponse|JsonResponse
    {
        $rol = $request->user()->rolNormalizado();
        abort_unless(Permisos::tieneSeccion($rol, 'presentaciones'), 403);

        $accion = (string) $request->input('accion');
        $ajax = $request->expectsJson() || $request->ajax();

        if ($accion === 'entregar_diploma') {
            try {
                $resultado = ApiPresentaciones::actualizarEstado((int) $presentacion->id, 'entregar_diploma', $rol);
            } catch (\InvalidArgumentException $e) {
                if ($ajax) {
                    return response()->json(['exito' => false, 'mensaje' => $e->getMessage()], 422);
                }

                return back()->withErrors($e->getMessage());
            }

            Actividad::registrar('actualizar_estado_presentacion', 'presentaciones', 'presentacion', $presentacion->id, 'Entregar diploma');
            $presentacion->refresh();
            notify()->success()->title($resultado['mensaje'])->send();

            if ($ajax) {
                return $this->respuestaEstadoPresentacion($ajax, $resultado['mensaje'], $presentacion, true);
            }

            return back()->with('ok', $resultado['mensaje']);
        }

        if ($accion === 'avanzar') {
            $siguiente = ApiPresentaciones::siguienteEstado((string) $presentacion->estado);

            if ($siguiente === null) {
                return $this->respuestaEstadoPresentacion($ajax, 'El registro ya está presentado.', $presentacion, false);
            }

            try {
                $resultado = ApiPresentaciones::actualizarEstado((int) $presentacion->id, $siguiente, $rol);
            } catch (\InvalidArgumentException $e) {
                if ($ajax) {
                    return response()->json(['exito' => false, 'mensaje' => $e->getMessage()], 422);
                }

                return back()->withErrors($e->getMessage());
            }

            Actividad::registrar('actualizar_estado_presentacion', 'presentaciones', 'presentacion', $presentacion->id, 'Avanzar a '.$resultado['estado_etiqueta']);
            $presentacion->refresh();
            notify()->success()->title($resultado['mensaje'])->send();

            if ($ajax) {
                return $this->respuestaEstadoPresentacion($ajax, $resultado['mensaje'], $presentacion, $siguiente === 'presentado');
            }

            if ($siguiente === 'presentado') {
                return redirect()->route('presentaciones.registros');
            }

            return back();
        }

        if ($accion === 'retroceder') {
            abort_unless(Permisos::normalizarRol($rol) === Permisos::SUPERADMIN, 403);

            $estadoActual = (string) $presentacion->estado;
            $anterior = ApiPresentaciones::estadoAnterior($estadoActual);

            if ($anterior === null) {
                return $this->respuestaEstadoPresentacion($ajax, 'El registro ya está en el primer estado.', $presentacion, false);
            }

            try {
                $resultado = ApiPresentaciones::actualizarEstado((int) $presentacion->id, $anterior, $rol);
            } catch (\InvalidArgumentException $e) {
                if ($ajax) {
                    return response()->json(['exito' => false, 'mensaje' => $e->getMessage()], 422);
                }

                return back()->withErrors($e->getMessage());
            }

            Actividad::registrar('actualizar_estado_presentacion', 'presentaciones', 'presentacion', $presentacion->id, 'Retroceder a '.$resultado['estado_etiqueta']);
            $presentacion->refresh();
            notify()->success()->title($resultado['mensaje'])->send();

            if ($ajax) {
                return $this->respuestaEstadoPresentacion($ajax, $resultado['mensaje'], $presentacion, $estadoActual === 'presentado');
            }

            if ($estadoActual === 'presentado') {
                return redirect()->route('presentaciones.nuevos');
            }

            return back();
        }

        if ($accion === 'restablecer' && Permisos::controlTotal($rol)) {
            try {
                ApiPresentaciones::actualizarEstado((int) $presentacion->id, 'recibido', $rol);
            } catch (\InvalidArgumentException $e) {
                if ($ajax) {
                    return response()->json(['exito' => false, 'mensaje' => $e->getMessage()], 422);
                }

                return back()->withErrors($e->getMessage());
            }

            Actividad::registrar('actualizar_estado_presentacion', 'presentaciones', 'presentacion', $presentacion->id, 'Restablecer estado');
            $presentacion->refresh();
            notify()->success()->title('Registro restablecido a Registrado.')->send();

            if ($ajax) {
                return $this->respuestaEstadoPresentacion($ajax, 'Registro restablecido a Registrado.', $presentacion, true);
            }

            return redirect()->route('presentaciones.nuevos');
        }

        abort(403);
    }

    private function respuestaEstadoPresentacion(bool $ajax, string $mensaje, PresentacionNino $presentacion, bool $salirLista): RedirectResponse|JsonResponse
    {
        if (! $ajax) {
            return back()->with('ok', $mensaje);
        }

        $notify = session()->pull('notify');

        return response()->json([
            'exito' => true,
            'mensaje' => $mensaje,
            'estado' => (string) $presentacion->estado,
            'estado_etiqueta' => ApiPresentaciones::etiquetaEstado((string) $presentacion->estado),
            'salir_lista' => $salirLista,
            'html' => view('components.ui.progreso-presentacion', [
                'registro' => $presentacion,
                'editable' => $presentacion->estado !== 'presentado',
            ])->render(),
            'notify' => is_array($notify) ? [
                'title' => $notify['title'] ?? $mensaje,
                'message' => $notify['message'] ?? null,
                'type' => ($notify['type'] ?? null) instanceof \BackedEnum
                    ? $notify['type']->value
                    : (string) ($notify['type'] ?? 'success'),
                'duration' => (int) ($notify['duration'] ?? config('notify.timeout', 5000)),
            ] : [
                'title' => $mensaje,
                'type' => 'success',
                'duration' => (int) config('notify.timeout', 5000),
            ],
        ]);
    }

    public function eliminarInscripcion(Request $request, Inscripcion $inscripcion): RedirectResponse
    {
        abort_unless(Permisos::controlTotal($request->user()->rolNormalizado()), 403);
        $id = $inscripcion->id;
        $tipo = $inscripcion->tipo_formulario;
        $inscripcion->delete();
        Actividad::registrar('eliminar_inscripcion', $tipo, 'inscripcion', $id, 'Eliminar inscripción');

        return back()->with('ok', 'Registro eliminado.');
    }

    public function eliminarPresentacion(Request $request, PresentacionNino $presentacion): RedirectResponse
    {
        abort_unless(Permisos::controlTotal($request->user()->rolNormalizado()), 403);
        $id = $presentacion->id;
        $presentacion->delete();
        Actividad::registrar('eliminar_presentacion', 'presentaciones', 'presentacion', $id, 'Eliminar presentación');

        return back()->with('ok', 'Registro eliminado.');
    }

    private function responder(Request $request, string $seccion, string $pestana = ''): View|RedirectResponse
    {
        $rol = $request->user()->rolNormalizado();

        if (! Permisos::puedeVerItem($rol, $seccion)) {
            return redirect(Permisos::urlInicio($rol));
        }

        $usaFlujo = in_array($seccion, ['bautismo', 'conexion', 'presentaciones'], true);

        if ($pestana === '' && $request->filled('pestaña') && $usaFlujo) {
            return redirect(Permisos::rutaFlujo($seccion, (string) $request->query('pestaña')));
        }

        $pestaña = $pestana !== '' ? $pestana : (string) $request->query('pestaña', $usaFlujo ? 'nuevos' : 'registros');
        $permitidas = $usaFlujo ? ['nuevos', 'registros'] : ['registros'];

        if ($pestaña === 'nuevo' || ! in_array($pestaña, $permitidas, true)) {
            $pestaña = $usaFlujo ? 'nuevos' : 'registros';
        }

        $puedeRegistrar = $seccion !== 'generales' && Permisos::puedeRegistrar($rol, $seccion);

        $errorBd = null;
        $registros = null;

        try {
            $registros = $seccion === 'generales'
                ? $this->generalesDelDia($request, $rol)
                : ($seccion === 'presentaciones'
                    ? $this->consultarPresentaciones($request, $pestaña)
                    : $this->consultarInscripciones($request, $seccion, $pestaña));
        } catch (Throwable) {
            $errorBd = 'No se pudieron cargar los registros. Confirma que MySQL está iniciado y que la base existe.';
            $registros = new LengthAwarePaginator([], 0, 20);
        }

        return view('registros.index', [
            'seccionActiva' => $seccion,
            'seccion' => $seccion,
            'titulo' => Permisos::etiquetas()[$seccion] ?? 'Registros',
            'pestaña' => $pestaña,
            'menuSeccion' => $usaFlujo ? Permisos::menuFlujo($rol, $seccion) : [],
            'usaFlujo' => $usaFlujo,
            'puedeRegistrar' => $puedeRegistrar,
            'puedeEditar' => Permisos::controlTotal($rol),
            'puedeEstadoConexion' => Permisos::puedeGestionarEstadoConexion($rol),
            'registros' => $registros,
            'errorBd' => $errorBd,
            'zonas' => Permisos::zonas(),
            'buscar' => trim((string) $request->query('buscar', '')),
        ]);
    }

    private function consultarInscripciones(Request $request, string $seccion, string $pestaña): LengthAwarePaginator
    {
        $consulta = Inscripcion::query()->where('tipo_formulario', $seccion);
        $this->aplicarBusqueda($consulta, $request, ['nombre', 'apellido', 'email', 'celular', 'direccion', 'zona']);
        $this->aplicarFechas($consulta, $request, 'creado_en');

        if ($seccion === 'bautismo') {
            $consulta->where('estado_bautismo', $pestaña === 'nuevos' ? 'ingresado' : 'bautizado');
        }

        if ($seccion === 'conexion' && in_array($pestaña, ['nuevos', 'registros'], true)) {
            $consulta->where('contactado', $pestaña === 'nuevos' ? 0 : 1);
        }

        return $consulta->orderByDesc('creado_en')->orderByDesc('id')->paginate(20)->withQueryString();
    }

    private function consultarPresentaciones(Request $request, string $pestaña): LengthAwarePaginator
    {
        $consulta = PresentacionNino::query();
        $this->aplicarBusqueda($consulta, $request, ['nombre_padre', 'nombre_madre', 'nombre_presentado', 'telefono_papa', 'telefono_mama']);
        $this->aplicarFechas($consulta, $request, 'creado_en');

        if ($pestaña === 'registros') {
            $consulta->where('estado', 'presentado');
        } elseif ($pestaña === 'nuevos') {
            $consulta->where('estado', '<>', 'presentado');
        }

        return $consulta->orderByDesc('creado_en')->orderByDesc('id')->paginate(20)->withQueryString();
    }

    private function generalesDelDia(Request $request, string $rol): LengthAwarePaginator
    {
        $desde = now()->startOfDay();
        $hasta = now()->endOfDay();
        $filas = collect();
        $tipos = Permisos::tiposInscripcion($rol);

        if ($tipos !== []) {
            $filas = $filas->merge(
                Inscripcion::query()
                    ->whereIn('tipo_formulario', $tipos)
                    ->whereBetween('creado_en', [$desde, $hasta])
                    ->orderByDesc('creado_en')
                    ->get()
                    ->map(fn (Inscripcion $fila) => [
                        'origen' => 'inscripcion',
                        'id' => $fila->id,
                        'seccion' => $fila->tipo_formulario,
                        'nombre' => trim($fila->nombre.' '.$fila->apellido),
                        'detalle' => $fila->celular,
                        'creado_en' => $fila->creado_en,
                        'modelo' => $fila,
                    ])
            );
        }

        if (Permisos::tieneSeccion($rol, 'presentaciones')) {
            $filas = $filas->merge(
                PresentacionNino::query()
                    ->whereBetween('creado_en', [$desde, $hasta])
                    ->orderByDesc('creado_en')
                    ->get()
                    ->map(fn (PresentacionNino $fila) => [
                        'origen' => 'presentacion',
                        'id' => $fila->id,
                        'seccion' => 'presentaciones',
                        'nombre' => $fila->nombre_presentado,
                        'detalle' => $fila->telefono_papa,
                        'creado_en' => $fila->creado_en,
                        'modelo' => $fila,
                    ])
            );
        }

        $filas = $filas->sortByDesc(fn (array $fila) => $fila['creado_en']?->timestamp ?? 0)->values();

        return $this->paginarColeccion($filas, $request);
    }

    private function aplicarBusqueda(Builder $consulta, Request $request, array $campos): void
    {
        $buscar = trim((string) $request->query('buscar', ''));

        if ($buscar === '') {
            return;
        }

        $like = '%'.addcslashes($buscar, '%_\\').'%';
        $consulta->where(function (Builder $grupo) use ($campos, $like) {
            foreach ($campos as $campo) {
                $grupo->orWhere($campo, 'like', $like);
            }
        });
    }

    private function aplicarFechas(Builder $consulta, Request $request, string $columna): void
    {
        $desde = trim((string) $request->query('fecha_desde', ''));
        $hasta = trim((string) $request->query('fecha_hasta', ''));

        if ($desde !== '') {
            $consulta->where($columna, '>=', $desde.' 00:00:00');
        }

        if ($hasta !== '') {
            $consulta->where($columna, '<=', $hasta.' 23:59:59');
        }
    }

    private function paginarColeccion(Collection $filas, Request $request): LengthAwarePaginator
    {
        $pagina = max(1, (int) $request->query('page', 1));
        $porPagina = 20;

        return new LengthAwarePaginator(
            $filas->slice(($pagina - 1) * $porPagina, $porPagina)->values(),
            $filas->count(),
            $porPagina,
            $pagina,
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }
}
