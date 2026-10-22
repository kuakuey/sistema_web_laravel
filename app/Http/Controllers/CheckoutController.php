<?php

namespace App\Http\Controllers;

use App\Models\Evento;
use App\Models\ValorAdicional;
use App\Support\Actividad;
use App\Support\Eventos;
use App\Support\Permisos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class CheckoutController extends Controller
{
    public function index(Request $request): View
    {
        $rol = $request->user()->rolNormalizado();
        abort_unless(Permisos::puedeUsarCheckoutEventos($rol), 403);

        $eventos = Evento::query()->where('habilitado', true)->orderBy('nombre')->get(['id', 'nombre', 'fecha']);

        return view('eventos.checkout', [
            'tituloPagina' => 'Checkout',
            'seccionActiva' => 'eventos',
            'pestaña' => 'eventos',
            'menuActivo' => 'eventos',
            'eventos' => $eventos,
            'puedeReversar' => Permisos::controlTotal($rol),
        ]);
    }

    public function consultar(Request $request): JsonResponse
    {
        $this->exigir($request);

        $datos = $request->validate([
            'evento_id' => ['required', 'integer'],
            'codigo' => ['required', 'string', 'max:30'],
        ]);

        $evento = Evento::query()->find((int) $datos['evento_id']);

        if (! $evento || ! $evento->habilitado) {
            return response()->json(['ok' => false, 'error' => 'Solo se puede consultar eventos habilitados.']);
        }

        $codigo = trim($datos['codigo']);
        $registros = ValorAdicional::query()
            ->where('tipo', Eventos::TIPO)
            ->where('evento_id', $evento->id)
            ->whereRaw('LOWER(TRIM(numeracion)) = ?', [mb_strtolower($codigo)])
            ->orderBy('nombre')
            ->get();

        if ($registros->isEmpty()) {
            return response()->json(['ok' => false, 'error' => 'No se encontró un ticket con ese código.']);
        }

        $rol = $request->user()->rolNormalizado();

        return response()->json([
            'ok' => true,
            'tickets' => $registros->map(fn (ValorAdicional $registro) => Eventos::ticket($registro, $rol))->all(),
        ]);
    }

    public function marcar(Request $request): JsonResponse
    {
        $this->exigir($request);

        $registro = $this->registro($request);

        try {
            $ticket = Eventos::marcarAsistencia($registro, $request->user());
        } catch (InvalidArgumentException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()]);
        }

        Actividad::registrar('marcar_asistencia_evento', 'eventos', 'registro', $registro->id, 'Marcar asistencia');

        return response()->json(['ok' => true, 'ticket' => $ticket]);
    }

    public function reversar(Request $request): JsonResponse
    {
        $this->exigir($request);
        abort_unless(Permisos::controlTotal($request->user()->rolNormalizado()), 403);

        $registro = $this->registro($request);

        try {
            $ticket = Eventos::reversarAsistencia($registro, $request->user());
        } catch (InvalidArgumentException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()]);
        }

        Actividad::registrar('reversar_asistencia_evento', 'eventos', 'registro', $registro->id, 'Reversar asistencia');

        return response()->json(['ok' => true, 'ticket' => $ticket]);
    }

    private function exigir(Request $request): void
    {
        abort_unless(Permisos::puedeUsarCheckoutEventos($request->user()->rolNormalizado()), 403);
    }

    private function registro(Request $request): ValorAdicional
    {
        $id = (int) $request->validate(['id' => ['required', 'integer']])['id'];
        $registro = ValorAdicional::query()->with('evento')->findOrFail($id);
        abort_unless($registro->esRegistroEvento(), 404);

        return $registro;
    }
}
