<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\ValorAdicional;
use App\Support\Actividad;
use App\Support\Eventos;
use App\Support\Permisos;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class EventoController extends Controller
{
    public function inicio(Request $request): View|RedirectResponse
    {
        $rol = $request->user()->rolNormalizado();
        $this->exigirModulo($rol);

        $permitidas = Permisos::pestanasEventos($rol);

        if ($permitidas === []) {
            return redirect(Permisos::urlInicio($rol));
        }

        if ($request->filled('pestaña')) {
            return redirect(Permisos::rutaEventos($this->normalizarPestana((string) $request->query('pestaña'))));
        }

        $destino = in_array('registros', $permitidas, true) ? 'registros' : $permitidas[0];

        return redirect(Permisos::rutaEventos($destino));
    }

    public function index(Request $request, string $seccion = ''): View|RedirectResponse
    {
        $rol = $request->user()->rolNormalizado();
        $this->exigirModulo($rol);

        $permitidas = Permisos::pestanasEventos($rol);

        if ($permitidas === []) {
            return redirect(Permisos::urlInicio($rol));
        }

        $pestana = $this->normalizarPestana($seccion !== '' ? $seccion : (string) $request->query('pestaña', $permitidas[0] ?? 'registros'));

        if (! in_array($pestana, $permitidas, true) || $pestana === 'participantes') {
            $pestana = $permitidas[0];
        }

        return $this->vista($request, $rol, $pestana, $permitidas);
    }

    public function participantes(Request $request, string $slug): View|RedirectResponse
    {
        $rol = $request->user()->rolNormalizado();
        $this->exigirModulo($rol);

        abort_unless(Permisos::puedeVerParticipantesEventos($rol), 403);

        $evento = Evento::buscarPorSlug($slug);

        if (! $evento) {
            return redirect()->route('eventos.catalogo')->withErrors(['evento' => 'Evento no encontrado.']);
        }

        if ($slug !== $evento->slug()) {
            return redirect()->route('eventos.participantes', array_merge(['slug' => $evento->slug()], $request->query()));
        }

        return $this->vista($request, $rol, 'participantes', Permisos::pestanasEventos($rol), $evento);
    }

    public function guardarEvento(Request $request): RedirectResponse
    {
        $rol = $request->user()->rolNormalizado();
        abort_unless(Permisos::puedeAgregarEventos($rol), 403);

        try {
            $evento = Eventos::guardarCatalogo(null, $request->all());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['evento' => $e->getMessage()]);
        }

        Actividad::registrar('crear_evento', 'eventos', 'evento', $evento->id, 'Crear evento');

        return redirect()->route('eventos.catalogo')->with('ok', 'Evento creado correctamente.');
    }

    public function actualizarEvento(Request $request, Evento $evento): RedirectResponse
    {
        $rol = $request->user()->rolNormalizado();
        abort_unless(Permisos::puedeAgregarEventos($rol), 403);

        try {
            Eventos::guardarCatalogo($evento, $request->all());
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['evento' => $e->getMessage()]);
        }

        Actividad::registrar('actualizar_evento_catalogo', 'eventos', 'evento', $evento->id, 'Editar evento');

        return redirect()->route('eventos.catalogo')->with('ok', 'Evento actualizado correctamente.');
    }

    public function eliminarEvento(Request $request, Evento $evento): RedirectResponse
    {
        abort_unless(Permisos::controlTotal($request->user()->rolNormalizado()), 403);

        $id = $evento->id;
        $evento->tiposEntrada()->delete();
        $evento->camposAdicionales()->delete();
        $evento->registros()->delete();
        $evento->delete();
        Actividad::registrar('eliminar_evento', 'eventos', 'evento', $id, 'Eliminar evento');

        return redirect()->route('eventos.catalogo')->with('ok', 'Evento eliminado.');
    }

    public function vaciarRegistros(Request $request, Evento $evento): RedirectResponse
    {
        abort_unless(Permisos::controlTotal($request->user()->rolNormalizado()), 403);

        $eliminados = $evento->registros()->delete();
        Actividad::registrar('eliminar_registros_evento', 'eventos', 'evento', $evento->id, 'Vaciar registros del evento');

        return redirect()->route('eventos.catalogo')->with(
            'ok',
            $eliminados === 1
                ? 'Se eliminó 1 registro del evento. El evento se conservó.'
                : 'Se eliminaron '.$eliminados.' registro(s) del evento. El evento se conservó.'
        );
    }

    public function guardarParticipante(Request $request): RedirectResponse
    {
        $usuario = $request->user();
        abort_unless(Permisos::puedeRegistrar($usuario->rolNormalizado(), 'eventos'), 403);

        try {
            $registro = Eventos::registrarParticipante($request->all(), $usuario);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['registro' => $e->getMessage()]);
        }

        Actividad::registrar('registrar_evento', 'eventos', 'registro', $registro->id, 'Registrar participante');

        return redirect()->route('eventos.registros')->with('ok', 'Participante registrado correctamente.');
    }

    public function actualizarParticipante(Request $request, ValorAdicional $registro): RedirectResponse
    {
        abort_unless($registro->esRegistroEvento(), 404);
        abort_unless(Permisos::controlTotal($request->user()->rolNormalizado()), 403);

        try {
            $datos = Eventos::validarRegistro(
                array_merge($request->all(), ['evento_id' => $registro->evento_id]),
                $request->user()->rolNormalizado(),
                $registro->evento?->load(['tiposEntrada', 'camposAdicionales'])
            );
            unset($datos['tipo']);
            $registro->fill($datos)->save();
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['registro' => $e->getMessage()]);
        }

        Actividad::registrar('actualizar_registro_evento', 'eventos', 'registro', $registro->id, 'Editar participante');

        return back()->with('ok', 'Registro actualizado correctamente.');
    }

    public function eliminarParticipante(Request $request, ValorAdicional $registro): RedirectResponse
    {
        abort_unless($registro->esRegistroEvento(), 404);
        abort_unless(Permisos::controlTotal($request->user()->rolNormalizado()), 403);

        $id = $registro->id;
        $registro->delete();
        Actividad::registrar('eliminar_registro_evento', 'eventos', 'registro', $id, 'Eliminar participante');

        return back()->with('ok', 'Registro eliminado.');
    }

    public function estadoPago(Request $request, ValorAdicional $registro): RedirectResponse
    {
        abort_unless($registro->esRegistroEvento(), 404);

        $rol = $request->user()->rolNormalizado();
        abort_unless(Permisos::controlTotal($rol) || Permisos::puedeRegistrar($rol, 'eventos'), 403);

        $estado = $request->validate([
            'estado_pago' => ['required', 'in:por_cancelar,pagado'],
        ])['estado_pago'];

        if ($registro->forma_pago === 'gratuito') {
            return back()->withErrors(['registro' => 'Las entradas gratuitas siempre quedan pagadas.']);
        }

        $registro->estado_pago = $estado;
        $registro->forma_pago = $estado === 'pagado' ? ($registro->forma_pago ?: 'efectivo') : 'pendiente';
        $registro->save();
        Actividad::registrar('actualizar_estado_pago_evento', 'eventos', 'registro', $registro->id, 'Cambiar estado de pago');

        return back()->with('ok', 'Estado de pago actualizado.');
    }

    public function informe(Request $request, Evento $evento): View
    {
        $rol = $request->user()->rolNormalizado();
        abort_unless(Permisos::puedeVerInformeEventos($rol), 403);

        $registros = $evento->registros()->orderBy('tipo_entrada')->orderBy('nombre')->get();

        return view('eventos.informe', [
            'tituloPagina' => 'Informe · '.$evento->nombre,
            'seccionActiva' => 'eventos',
            'pestaña' => 'eventos',
            'menuActivo' => 'eventos',
            'evento' => $evento->load('tiposEntrada'),
            'registros' => $registros,
            'total' => $registros->count(),
            'asistieron' => $registros->where('asistio', true)->count(),
            'recaudado' => $registros->where('estado_pago', 'pagado')->sum('valor'),
            'pendiente' => $registros->where('estado_pago', 'por_cancelar')->sum('valor'),
        ]);
    }

    /**
     * @param  array<int, string>  $permitidas
     */
    private function vista(Request $request, string $rol, string $pestana, array $permitidas, ?Evento $eventoParticipantes = null): View
    {
        $errorBd = null;
        $catalogo = collect();
        $habilitados = collect();
        $registros = null;

        try {
            if (in_array($pestana, ['eventos', 'registros'], true) || $eventoParticipantes) {
                $catalogo = Evento::query()->with(['tiposEntrada', 'camposAdicionales'])->withCount('registros')->orderBy('nombre')->get();
            }

            if ($pestana === 'registros') {
                $habilitados = Evento::query()
                    ->with(['tiposEntrada', 'camposAdicionales'])
                    ->where('habilitado', true)
                    ->orderBy('nombre')
                    ->get();
            }

            if ($pestana === 'registros' || $pestana === 'participantes') {
                $consulta = ValorAdicional::query()->with('evento')->where('tipo', Eventos::TIPO);

                if ($eventoParticipantes) {
                    $consulta->where('evento_id', $eventoParticipantes->id);
                } else {
                    $consulta->whereHas('evento', fn ($evento) => $evento->where('habilitado', true));
                }

                $buscar = trim((string) $request->query('buscar', ''));

                if ($buscar !== '') {
                    $like = '%'.addcslashes($buscar, '%_\\').'%';
                    $consulta->where(function ($grupo) use ($like) {
                        $grupo->where('nombre', 'like', $like)
                            ->orWhere('telefono', 'like', $like)
                            ->orWhere('numeracion', 'like', $like)
                            ->orWhere('tipo_entrada', 'like', $like);
                    });
                }

                if ($request->filled('fecha_desde')) {
                    $consulta->whereDate('creado_en', '>=', $request->query('fecha_desde'));
                }

                if ($request->filled('fecha_hasta')) {
                    $consulta->whereDate('creado_en', '<=', $request->query('fecha_hasta'));
                }

                $registros = $consulta->orderByDesc('creado_en')->orderByDesc('id')->paginate(20)->withQueryString();
            }
        } catch (Throwable) {
            $errorBd = 'No se pudieron cargar los eventos.';
        }

        return view('eventos.index', [
            'tituloPagina' => $eventoParticipantes ? $eventoParticipantes->nombre.' · Eventos' : 'Eventos',
            'titulo' => $eventoParticipantes ? $eventoParticipantes->nombre : 'Eventos',
            'seccionActiva' => 'eventos',
            'pestaña' => $pestana,
            'menuActivo' => $eventoParticipantes ? 'eventos' : $pestana,
            'menuEventos' => Permisos::menuEventos($rol),
            'permitidas' => $permitidas,
            'catalogo' => $catalogo,
            'habilitados' => $habilitados,
            'registros' => $registros,
            'eventoParticipantes' => $eventoParticipantes,
            'buscar' => trim((string) $request->query('buscar', '')),
            'puedeVerTabla' => Permisos::puedeVerTablaEventos($rol),
            'puedeRegistrar' => Permisos::puedeRegistrar($rol, 'eventos'),
            'puedeAgregar' => Permisos::puedeAgregarEventos($rol),
            'puedeVerCatalogo' => Permisos::puedeVerCatalogoEventos($rol),
            'puedeVerParticipantes' => Permisos::puedeVerParticipantesEventos($rol),
            'puedeVerInforme' => Permisos::puedeVerInformeEventos($rol),
            'puedeEditar' => Permisos::controlTotal($rol),
            'puedeEstadoPago' => Permisos::puedeCrearRegistroEventoPendiente($rol),
            'soloMetodosPago' => Permisos::registroEventoRequierePagoInmediato($rol),
            'errorBd' => $errorBd,
            'rol' => $rol,
        ]);
    }

    private function normalizarPestana(string $pestana): string
    {
        return match ($pestana) {
            'tabla', 'registrar' => 'registros',
            'agregar', 'catalogo' => 'eventos',
            default => $pestana,
        };
    }

    private function exigirModulo(string $rol): void
    {
        if (! Permisos::tieneSeccion($rol, 'eventos')) {
            abort(redirect(Permisos::urlInicio($rol)));
        }
    }
}
