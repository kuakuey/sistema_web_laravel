<?php

namespace App\Http\Controllers;

use App\Models\CalendarioEvento;
use App\Models\CasaVida;
use App\Models\Consejeria;
use App\Models\NetworkingEmprendedor;
use App\Models\Ofrenda;
use App\Models\ValorAdicional;
use App\Support\Actividad;
use App\Support\Eventos;
use App\Support\Permisos;
use App\Support\Texto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ModuloController extends Controller
{
    public function ofrendas(Request $request): View|RedirectResponse
    {
        $this->exigir($request, 'ofrendas');

        $casas = $this->casasOfrenda();

        return $this->vista($request, 'ofrendas', 'Ofrendas', $this->paginar(function () use ($request) {
            return Ofrenda::query()
                ->when($this->buscar($request), function ($consulta, $like) {
                    $consulta->where(function ($grupo) use ($like) {
                        $grupo->where('casa_vida', 'like', $like)->orWhere('lider', 'like', $like);
                    });
                })
                ->orderByDesc('fecha_ofrenda')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }), [
            'casasOfrenda' => $casas,
            'opcionesCasasOfrenda' => $casas
                ->mapWithKeys(fn (CasaVida $casa) => [$casa->id => $casa->nombre])
                ->all(),
        ]);
    }

    public function guardarOfrenda(Request $request): RedirectResponse
    {
        abort_unless(Permisos::puedeRegistrar($request->user()->rolNormalizado(), 'ofrendas'), 403);

        $datos = $request->validate([
            'casa_id' => ['required', 'integer', 'exists:casas_vida,id'],
            'fecha_ofrenda' => ['required', 'date'],
            'monto' => ['required', 'numeric', 'min:0'],
        ]);

        $registro = Ofrenda::query()->create(array_merge($this->datosCasaOfrenda((int) $datos['casa_id']), [
            'fecha_ofrenda' => $datos['fecha_ofrenda'],
            'monto' => $datos['monto'],
            'registrado_por_id' => $request->user()->id,
            'registrado_por_nombre' => $request->user()->nombreVisible(),
        ]));

        Actividad::registrar('crear_ofrenda', 'ofrendas', 'ofrenda', $registro->id, 'Crear ofrenda');

        return redirect()->route('ofrendas')->with('ok', 'Ofrenda registrada correctamente.');
    }

    public function actualizarOfrenda(Request $request, Ofrenda $ofrenda): RedirectResponse
    {
        abort_unless(Permisos::controlTotal($request->user()->rolNormalizado()), 403);

        $datos = $request->validate([
            'casa_id' => ['required', 'integer', 'exists:casas_vida,id'],
            'fecha_ofrenda' => ['required', 'date'],
            'monto' => ['required', 'numeric', 'min:0'],
        ]);

        $ofrenda->fill(array_merge($this->datosCasaOfrenda((int) $datos['casa_id']), [
            'fecha_ofrenda' => $datos['fecha_ofrenda'],
            'monto' => $datos['monto'],
        ]));
        $ofrenda->save();
        Actividad::registrar('actualizar_ofrenda', 'ofrendas', 'ofrenda', $ofrenda->id, 'Editar ofrenda');

        return back()->with('ok', 'Ofrenda actualizada correctamente.');
    }

    public function eliminarOfrenda(Request $request, Ofrenda $ofrenda): RedirectResponse
    {
        abort_unless(Permisos::controlTotal($request->user()->rolNormalizado()), 403);
        $id = $ofrenda->id;
        $ofrenda->delete();
        Actividad::registrar('eliminar_ofrenda', 'ofrendas', 'ofrenda', $id, 'Eliminar ofrenda');

        return back()->with('ok', 'Ofrenda eliminada.');
    }

    public function consejeria(Request $request): View|RedirectResponse
    {
        $this->exigir($request, 'consejeria');

        return $this->vista($request, 'consejeria', 'Consejería', $this->paginar(function () use ($request) {
            return Consejeria::query()
                ->when($this->buscar($request), function ($consulta, $like) {
                    $consulta->where(function ($grupo) use ($like) {
                        $grupo->where('nombre_completo', 'like', $like)->orWhere('telefono', 'like', $like);
                    });
                })
                ->when($request->query('tipo_consejeria'), function ($consulta, $tipo) {
                    $consulta->where('tipo_consejeria', $tipo);
                })
                ->orderByDesc('creado_en')
                ->paginate(20)
                ->withQueryString();
        }), ['tiposConsejeria' => Permisos::tiposConsejeria()]);
    }

    public function guardarConsejeria(Request $request): RedirectResponse
    {
        abort_unless(Permisos::puedeRegistrar($request->user()->rolNormalizado(), 'consejeria'), 403);

        $datos = $request->validate([
            'nombre_completo' => ['required', 'string', 'max:200'],
            'telefono' => ['required', 'string', 'max:30'],
            'tipo_consejeria' => ['required', 'in:'.implode(',', array_keys(Permisos::tiposConsejeria()))],
            'anio_en_cda' => ['required', 'integer', 'min:0', 'max:80'],
            'primera_consejeria' => ['nullable', 'boolean'],
        ]);

        $registro = Consejeria::query()->create([
            'nombre_completo' => Texto::titulo($datos['nombre_completo']),
            'telefono' => trim($datos['telefono']),
            'tipo_consejeria' => $datos['tipo_consejeria'],
            'anio_en_cda' => $datos['anio_en_cda'],
            'primera_consejeria' => $request->boolean('primera_consejeria'),
            'registrado_por_id' => $request->user()->id,
            'registrado_por_nombre' => $request->user()->nombreVisible(),
        ]);

        Actividad::registrar('crear_consejeria', 'consejeria', 'consejeria', $registro->id, 'Crear consejería');

        return redirect()->route('consejeria')->with('ok', 'Solicitud de consejería registrada correctamente.');
    }

    public function actualizarConsejeria(Request $request, Consejeria $consejeria): RedirectResponse
    {
        abort_unless(Permisos::controlTotal($request->user()->rolNormalizado()), 403);

        $datos = $request->validate([
            'nombre_completo' => ['required', 'string', 'max:200'],
            'telefono' => ['required', 'string', 'max:30'],
            'tipo_consejeria' => ['required', 'in:'.implode(',', array_keys(Permisos::tiposConsejeria()))],
            'anio_en_cda' => ['required', 'integer', 'min:0', 'max:80'],
            'primera_consejeria' => ['required', 'in:0,1'],
            'cita_fecha' => ['nullable', 'date'],
            'cita_hora' => ['nullable', 'date_format:H:i'],
        ]);

        $consejeria->fill([
            'nombre_completo' => Texto::titulo($datos['nombre_completo']),
            'telefono' => trim($datos['telefono']),
            'tipo_consejeria' => $datos['tipo_consejeria'],
            'anio_en_cda' => $datos['anio_en_cda'],
            'primera_consejeria' => (bool) (int) $datos['primera_consejeria'],
            'cita_fecha' => $datos['cita_fecha'] ?: null,
            'cita_hora' => $datos['cita_hora'] ?: null,
        ]);
        $consejeria->save();
        Actividad::registrar('actualizar_consejeria', 'consejeria', 'consejeria', $consejeria->id, 'Editar consejería');

        return back()->with('ok', 'Consejería actualizada correctamente.');
    }

    public function eliminarConsejeria(Request $request, Consejeria $consejeria): RedirectResponse
    {
        abort_unless(Permisos::controlTotal($request->user()->rolNormalizado()), 403);
        $id = $consejeria->id;
        $consejeria->delete();
        Actividad::registrar('eliminar_consejeria', 'consejeria', 'consejeria', $id, 'Eliminar consejería');

        return back()->with('ok', 'Registro eliminado.');
    }

    public function valores(Request $request): View|RedirectResponse
    {
        $this->exigir($request, 'valores_adicionales');

        $tipos = [];

        try {
            $tipos = DB::table('tipos_valor_adicional')
                ->where('clave', '!=', Eventos::TIPO)
                ->orderBy('etiqueta')
                ->get();
        } catch (Throwable) {
            $tipos = [];
        }

        return $this->vista($request, 'valores_adicionales', 'Valores adicionales', $this->paginar(function () use ($request) {
            return ValorAdicional::query()
                ->sinEventos()
                ->when($this->buscar($request), function ($consulta, $like) {
                    $consulta->where(function ($grupo) use ($like) {
                        $grupo->where('nombre', 'like', $like)->orWhere('telefono', 'like', $like)->orWhere('tipo', 'like', $like);
                    });
                })
                ->when($request->filled('fecha_desde'), fn ($consulta) => $consulta->whereDate('fecha', '>=', $request->query('fecha_desde')))
                ->when($request->filled('fecha_hasta'), fn ($consulta) => $consulta->whereDate('fecha', '<=', $request->query('fecha_hasta')))
                ->orderByDesc('fecha')
                ->orderByDesc('id')
                ->paginate(20)
                ->withQueryString();
        }), ['tiposValor' => $tipos]);
    }

    public function guardarValor(Request $request): RedirectResponse
    {
        abort_unless(Permisos::puedeRegistrar($request->user()->rolNormalizado(), 'valores_adicionales'), 403);

        $datos = $request->validate([
            'tipo' => ['required', 'string', 'max:50', 'not_in:'.Eventos::TIPO],
            'nombre' => ['required', 'string', 'max:100'],
            'fecha' => ['required', 'date'],
            'telefono' => ['required', 'string', 'max:30'],
            'valor' => ['required', 'numeric', 'min:0'],
            'observacion' => ['nullable', 'string'],
        ]);

        $registro = ValorAdicional::query()->create([
            'tipo' => trim($datos['tipo']),
            'nombre' => Texto::titulo($datos['nombre']),
            'fecha' => $datos['fecha'],
            'telefono' => trim($datos['telefono']),
            'valor' => $datos['valor'],
            'observacion' => $datos['observacion'] ?? null,
            'registrado_por_id' => $request->user()->id,
            'registrado_por_nombre' => $request->user()->nombreVisible(),
        ]);

        Actividad::registrar('crear_valor_adicional', 'valores_adicionales', 'valor_adicional', $registro->id, 'Crear valor adicional');

        return redirect()->route('valores')->with('ok', 'Valor registrado correctamente.');
    }

    public function actualizarValor(Request $request, ValorAdicional $valor): RedirectResponse
    {
        abort_unless(Permisos::controlTotal($request->user()->rolNormalizado()), 403);
        abort_if($valor->esRegistroEvento(), 404);

        $datos = $request->validate([
            'tipo' => ['required', 'string', 'max:50', 'not_in:'.Eventos::TIPO],
            'nombre' => ['required', 'string', 'max:100'],
            'fecha' => ['required', 'date'],
            'telefono' => ['required', 'string', 'max:30'],
            'valor' => ['required', 'numeric', 'min:0'],
            'observacion' => ['nullable', 'string'],
        ]);

        $valor->fill([
            'tipo' => trim($datos['tipo']),
            'nombre' => Texto::titulo($datos['nombre']),
            'fecha' => $datos['fecha'],
            'telefono' => trim($datos['telefono']),
            'valor' => $datos['valor'],
            'observacion' => $datos['observacion'] ?? null,
        ]);
        $valor->save();
        Actividad::registrar('actualizar_valor_adicional', 'valores_adicionales', 'valor_adicional', $valor->id, 'Editar valor adicional');

        return back()->with('ok', 'Valor actualizado correctamente.');
    }

    public function networking(Request $request): View|RedirectResponse
    {
        $this->exigir($request, 'networking');

        return $this->vista($request, 'networking', 'Networking', $this->paginar(function () use ($request) {
            return NetworkingEmprendedor::query()
                ->when($this->buscar($request), function ($consulta, $like) {
                    $consulta->where(function ($grupo) use ($like) {
                        $grupo->where('nombre', 'like', $like)
                            ->orWhere('empresa', 'like', $like)
                            ->orWhere('whatsapp', 'like', $like)
                            ->orWhere('email', 'like', $like);
                    });
                })
                ->orderByDesc('creado_en')
                ->paginate(20)
                ->withQueryString();
        }));
    }

    public function calendario(Request $request): View|RedirectResponse
    {
        $this->exigir($request, 'calendario');

        return $this->vista($request, 'calendario', 'Calendario', $this->paginar(function () use ($request) {
            return CalendarioEvento::query()
                ->when($this->buscar($request), function ($consulta, $like) {
                    $consulta->where('titulo', 'like', $like);
                })
                ->orderBy('fecha')
                ->paginate(20)
                ->withQueryString();
        }));
    }

    public function informes(Request $request): View|RedirectResponse
    {
        $this->exigir($request, 'generar_informe');

        $resumen = [];
        $errorBd = null;

        try {
            $resumen = [
                'Inscripciones' => DB::table('inscripciones')->count(),
                'Presentaciones de niños' => DB::table('presentaciones_ninos')->count(),
                'Ofrendas' => number_format((float) DB::table('ofrendas')->sum('monto'), 2),
                'Valores adicionales' => number_format((float) DB::table('valores_adicionales')->where('tipo', '!=', Eventos::TIPO)->sum('valor'), 2),
                'Eventos' => DB::table('eventos')->count(),
                'Participantes de eventos' => DB::table('valores_adicionales')->whereNotNull('evento_id')->count(),
                'Consejerías' => DB::table('consejerias')->count(),
                'Networking' => DB::table('networking_emprendedores')->count(),
            ];
        } catch (Throwable) {
            $errorBd = 'No se pudo calcular el resumen.';
        }

        return view('panel.informe', [
            'seccionActiva' => 'generar_informe',
            'titulo' => 'Generar informe',
            'resumen' => $resumen,
            'errorBd' => $errorBd,
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, CasaVida>
     */
    private function casasOfrenda()
    {
        try {
            return CasaVida::query()
                ->with('lider')
                ->orderBy('nombre')
                ->get();
        } catch (Throwable) {
            return collect();
        }
    }

    /**
     * @return array{casa_id: int, casa_vida: string, lider: string}
     */
    private function datosCasaOfrenda(int $casaId): array
    {
        $casa = CasaVida::query()->with('lider')->findOrFail($casaId);

        return [
            'casa_id' => $casa->id,
            'casa_vida' => (string) $casa->nombre,
            'lider' => $casa->lider?->nombreCompleto() ?: '',
        ];
    }

    private function exigir(Request $request, string $seccion): void
    {
        $rol = $request->user()->rolNormalizado();

        if (! Permisos::puedeVerItem($rol, $seccion)) {
            abort(redirect(Permisos::urlInicio($rol)));
        }
    }

    private function buscar(Request $request): ?string
    {
        $buscar = trim((string) $request->query('buscar', ''));

        return $buscar === '' ? null : '%'.addcslashes($buscar, '%_\\').'%';
    }

    private function paginar(callable $consulta): LengthAwarePaginator|null
    {
        try {
            return $consulta();
        } catch (Throwable) {
            return null;
        }
    }

    private function vista(Request $request, string $seccion, string $titulo, ?LengthAwarePaginator $registros, array $extra = []): View
    {
        $rol = $request->user()->rolNormalizado();
        $conFormulario = in_array($seccion, ['ofrendas', 'consejeria', 'valores_adicionales'], true);
        $puedeRegistrar = $conFormulario && Permisos::puedeRegistrar($rol, $seccion);

        return view('panel.modulo', array_merge([
            'seccionActiva' => $seccion,
            'seccion' => $seccion,
            'titulo' => $titulo,
            'registros' => $registros,
            'pestaña' => 'registros',
            'puedeRegistrar' => $puedeRegistrar,
            'puedeEditar' => Permisos::controlTotal($rol),
            'errorBd' => $registros === null ? 'No se pudieron cargar los registros.' : null,
            'buscar' => trim((string) $request->query('buscar', '')),
        ], $extra));
    }
}
