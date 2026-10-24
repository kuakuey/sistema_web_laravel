<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiPresentaciones;
use App\Support\ApiSesion;
use App\Support\Permisos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class PresentacionesController extends Controller
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
                        'meta' => ApiPresentaciones::meta(),
                    ]);
                }

                return response()->json(ApiPresentaciones::respuestaListaPublica());
            }

            if (! $request->isMethod('POST')) {
                return response()->json(['exito' => false, 'mensaje' => 'Método no permitido.'], 405);
            }

            $accion = trim((string) ($payload['accion'] ?? ''));

            if ($accion === 'registrar' || ApiPresentaciones::esPayloadRegistro($payload)) {
                return response()->json(ApiPresentaciones::registrarPublico($payload, [
                    'ip_cliente' => $request->ip(),
                    'agente_usuario' => 'Formulario Publico',
                ]));
            }

            if ($accion === 'entregar_diploma') {
                $id = (int) ($payload['id'] ?? 0);

                return response()->json(ApiPresentaciones::entregarDiploma($id));
            }

            $usuarioSesion = ApiSesion::validar(ApiSesion::tokenDesdeRequest($request, $payload));

            if (! $usuarioSesion || ! Permisos::tieneSeccion($usuarioSesion['rol'], 'presentaciones')) {
                return response()->json([
                    'exito' => false,
                    'mensaje' => 'Debes iniciar sesión para actualizar estados.',
                ], 401);
            }

            $id = (int) ($payload['id'] ?? 0);
            $estado = trim((string) ($payload['estado'] ?? ''));
            $estadosPlugin = ['contactado', 'confirmado', 'entregar_diploma'];

            if ($id <= 0 || $estado === '') {
                throw new InvalidArgumentException('ID y estado son obligatorios.');
            }

            if (! in_array($estado, $estadosPlugin, true)) {
                throw new InvalidArgumentException('Estado no permitido desde el portal.');
            }

            return response()->json(ApiPresentaciones::actualizarEstado($id, $estado, $usuarioSesion['rol']));
        } catch (InvalidArgumentException $e) {
            return response()->json(['exito' => false, 'mensaje' => $e->getMessage()], 400);
        } catch (Throwable) {
            return response()->json(['exito' => false, 'mensaje' => 'Error al procesar la solicitud.'], 500);
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
