<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Actividad;
use App\Support\ApiNetworking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class NetworkingController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $this->payload($request);

        try {
            if ($request->isMethod('GET')) {
                $accion = trim((string) $request->query('accion', 'listar'));

                if ($accion === 'meta') {
                    return response()->json([
                        'exito' => true,
                        'mensaje' => 'Catálogo de networking.',
                        'categorias' => ApiNetworking::catalogo(ApiNetworking::categorias()),
                        'ofrece' => ApiNetworking::catalogo(ApiNetworking::opcionesOfrece()),
                        'busca' => ApiNetworking::catalogo(ApiNetworking::opcionesBusca()),
                    ]);
                }

                $registros = ApiNetworking::buscarPublicos([
                    'buscar' => trim((string) $request->query('buscar', '')),
                    'categoria' => trim((string) $request->query('categoria', '')),
                ]);

                return response()->json([
                    'exito' => true,
                    'mensaje' => 'Directorio de empresarios y emprendedores.',
                    'total' => count($registros),
                    'categorias' => ApiNetworking::catalogo(ApiNetworking::categorias()),
                    'registros' => $registros,
                ]);
            }

            if (! $request->isMethod('POST')) {
                return response()->json(['exito' => false, 'mensaje' => 'Método no permitido.'], 405);
            }

            $accion = trim((string) ($payload['accion'] ?? 'registrar'));

            if ($accion !== 'registrar') {
                throw new InvalidArgumentException('Acción no permitida.');
            }

            $payload['visible'] = 1;
            $datos = ApiNetworking::armarDesdeEntrada(
                $payload,
                $request->file('logo'),
                true,
                'publico'
            );
            $datos['visible'] = 1;
            $datos['origen'] = 'publico';
            $datos['ip_cliente'] = $request->ip();
            $datos['agente_usuario'] = 'Formulario Publico';

            try {
                $id = ApiNetworking::insertar($datos);
                ApiNetworking::confirmarLogo($datos, true);
            } catch (Throwable $e) {
                ApiNetworking::confirmarLogo($datos, false);
                throw $e;
            }

            try {
                Actividad::registrar(
                    'crear_networking',
                    'networking',
                    'emprendedor',
                    $id,
                    $datos['empresa'].' — '.$datos['nombre']
                );
            } catch (Throwable) {
                // El registro ya quedó guardado.
            }

            return response()->json([
                'exito' => true,
                'mensaje' => 'Tu negocio ya forma parte del directorio.',
                'id' => $id,
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['exito' => false, 'mensaje' => $e->getMessage()], 400);
        } catch (Throwable) {
            return response()->json(['exito' => false, 'mensaje' => 'No se pudo procesar la solicitud.'], 500);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request): array
    {
        $json = $request->json()->all();

        return is_array($json) && $json !== [] ? $json : $request->all();
    }
}
