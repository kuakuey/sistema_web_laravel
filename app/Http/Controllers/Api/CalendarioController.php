<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiCalendario;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class CalendarioController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! $request->isMethod('GET')) {
            return response()->json(['exito' => false, 'mensaje' => 'Método no permitido.'], 405);
        }

        try {
            $accion = trim((string) $request->query('accion', 'calendario_text'));

            if (in_array($accion, ['calendario_text', 'texto', 'html'], true)) {
                $datos = ApiCalendario::proximosEventos();

                return response()->json([
                    'exito' => true,
                    'accion' => 'calendario_text',
                    'html' => ApiCalendario::htmlTexto(),
                    'meses' => $datos['meses'],
                    'total' => $datos['total'],
                ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            if ($accion === 'listar') {
                $datos = ApiCalendario::proximosEventos();

                return response()->json([
                    'exito' => true,
                    'meses' => $datos['meses'],
                    'total' => $datos['total'],
                ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            if (in_array($accion, ['banners', 'calendario_banners'], true)) {
                $datos = ApiCalendario::bannersMesActual();

                return response()->json([
                    'exito' => true,
                    'accion' => 'banners',
                    'anio' => $datos['anio'],
                    'mes' => $datos['mes'],
                    'etiqueta' => $datos['etiqueta'],
                    'imagenes' => $datos['imagenes'],
                    'total' => $datos['total'],
                ], 200, [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            return response()->json([
                'exito' => false,
                'mensaje' => 'Acción no válida. Usa calendario_text, banners o listar.',
            ], 400);
        } catch (Throwable) {
            return response()->json(['exito' => false, 'mensaje' => 'Error al procesar la solicitud.'], 500);
        }
    }
}
