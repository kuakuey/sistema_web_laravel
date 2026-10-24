<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CasaVida;
use App\Models\Territorio;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class EstructuraController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! $request->isMethod('GET')) {
            return response()->json(['exito' => false, 'mensaje' => 'Método no permitido.'], 405);
        }

        try {
            $territorios = Territorio::query()
                ->orderBy('nombre')
                ->get()
                ->map(fn (Territorio $territorio) => [
                    'id' => (int) $territorio->id,
                    'nombre' => (string) $territorio->nombre,
                ])
                ->sortBy(fn (array $item) => mb_strtolower($item['nombre']), SORT_NATURAL)
                ->values()
                ->all();

            $casas = CasaVida::query()
                ->with(['lider', 'colaborador', 'anfitrion'])
                ->get()
                ->map(function (CasaVida $casa) {
                    $nombre = trim((string) ($casa->nombre ?? ''));
                    if ($nombre === '') {
                        $anfitrion = trim(($casa->anfitrion?->nombreCompleto() ?? ''));
                        $nombre = $anfitrion !== '' ? $anfitrion : (trim((string) ($casa->direccion ?? '')) ?: 'Casa de vida');
                    }

                    return [
                        'id' => (int) $casa->id,
                        'territorio_id' => (int) $casa->territorio_id,
                        'nombre' => $nombre,
                        'lider' => trim((string) ($casa->lider?->nombreCompleto() ?? '')),
                        'colaborador' => trim((string) ($casa->colaborador?->nombreCompleto() ?? '')),
                        'anfitrion' => trim((string) ($casa->anfitrion?->nombreCompleto() ?? '')),
                        'direccion' => (string) ($casa->direccion ?? ''),
                    ];
                })
                ->sortBy(fn (array $item) => mb_strtolower($item['nombre']), SORT_NATURAL)
                ->values()
                ->all();

            return response()->json([
                'exito' => true,
                'territorios' => $territorios,
                'casas' => $casas,
            ]);
        } catch (Throwable) {
            return response()->json(['exito' => false, 'mensaje' => 'Error al cargar estructura CDA.'], 500);
        }
    }
}
