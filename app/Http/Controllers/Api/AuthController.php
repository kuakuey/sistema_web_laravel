<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\ApiSesion;
use App\Support\Permisos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class AuthController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $payload = $this->payload($request);

        try {
            if ($request->isMethod('GET')) {
                $usuario = ApiSesion::validar(ApiSesion::tokenDesdeRequest($request, $payload));

                if (! $usuario) {
                    return response()->json(['exito' => false, 'mensaje' => 'Sesión no válida o expirada.'], 401);
                }

                return response()->json([
                    'exito' => true,
                    'usuario' => $usuario['usuario'],
                    'nombre' => $usuario['nombre'],
                    'rol' => $usuario['rol'],
                ]);
            }

            if (! $request->isMethod('POST')) {
                return response()->json(['exito' => false, 'mensaje' => 'Método no permitido.'], 405);
            }

            $accion = trim((string) ($payload['accion'] ?? 'iniciar'));

            if ($accion === 'cerrar') {
                ApiSesion::cerrar(ApiSesion::tokenDesdeRequest($request, $payload));

                return response()->json([
                    'exito' => true,
                    'mensaje' => 'Sesión cerrada correctamente.',
                ]);
            }

            $usuarioLogin = trim((string) ($payload['usuario'] ?? ''));
            $clave = (string) ($payload['clave'] ?? '');

            if ($usuarioLogin === '' || $clave === '') {
                throw new InvalidArgumentException('Usuario y contraseña son obligatorios.');
            }

            $usuario = ApiSesion::autenticar($usuarioLogin, $clave);

            if (! $usuario) {
                return response()->json(['exito' => false, 'mensaje' => 'Usuario o contraseña incorrectos.'], 401);
            }

            if (! Permisos::puedeAccederPortalPlugin($usuario['rol'])) {
                return response()->json([
                    'exito' => false,
                    'mensaje' => 'Tu rol no tiene permiso para usar el portal.',
                ], 403);
            }

            $contexto = trim((string) ($payload['contexto'] ?? 'ofrendas'));

            if ($contexto === 'presentaciones' && ! Permisos::tieneSeccion($usuario['rol'], 'presentaciones')) {
                return response()->json([
                    'exito' => false,
                    'mensaje' => 'Tu rol no tiene permiso para gestionar presentaciones.',
                ], 403);
            }

            if ($contexto === 'ofrendas' && ! Permisos::puedeRegistrar($usuario['rol'], 'ofrendas')) {
                return response()->json([
                    'exito' => false,
                    'mensaje' => 'Tu rol no tiene permiso para registrar ofrendas.',
                ], 403);
            }

            $token = ApiSesion::crear($usuario['id']);

            return response()->json([
                'exito' => true,
                'mensaje' => 'Sesión iniciada correctamente.',
                'token_sesion' => $token,
                'usuario' => $usuario['usuario'],
                'nombre' => $usuario['nombre'],
                'rol' => $usuario['rol'],
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json(['exito' => false, 'mensaje' => $e->getMessage()], 400);
        } catch (Throwable) {
            return response()->json(['exito' => false, 'mensaje' => 'Error al procesar la autenticación.'], 500);
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
