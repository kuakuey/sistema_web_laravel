<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CasaVida;
use App\Models\Inscripcion;
use App\Models\Ofrenda;
use App\Support\ApiPresentaciones;
use App\Support\ApiSesion;
use App\Support\Permisos;
use App\Support\Texto;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Throwable;

class SubmitController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if ($request->isMethod('GET') && $request->query('recurso') === 'presentaciones') {
            try {
                return response()->json(ApiPresentaciones::respuestaListaPublica());
            } catch (Throwable) {
                return response()->json(['exito' => false, 'mensaje' => 'Error al procesar la solicitud.'], 500);
            }
        }

        if (! $request->isMethod('POST')) {
            return response()->json(['exito' => false, 'mensaje' => 'Método no permitido.'], 405);
        }

        $payload = $this->payload($request);
        $tipoFormulario = isset($payload['tipo_formulario']) ? trim((string) $payload['tipo_formulario']) : '';
        $tiposInscripcion = ['escol', 'academia', 'bautismo', 'conexion'];

        try {
            if (in_array($tipoFormulario, $tiposInscripcion, true)) {
                $nombre = trim((string) ($payload['nombre'] ?? ''));
                $apellido = trim((string) ($payload['apellido'] ?? ''));
                $celular = trim((string) ($payload['celular'] ?? $payload['telefono'] ?? ''));
                $email = trim((string) ($payload['email'] ?? ''));

                if ($nombre === '' || $apellido === '' || $celular === '') {
                    throw new InvalidArgumentException('Completa todos los campos obligatorios.');
                }

                $zona = '';
                $direccion = '';

                if ($tipoFormulario === 'conexion') {
                    $zona = trim((string) ($payload['zona'] ?? ''));
                    $direccion = trim((string) ($payload['direccion'] ?? ''));
                    $zonasOk = array_keys(Permisos::zonas());

                    if ($zona === '' || ! in_array($zona, $zonasOk, true)) {
                        throw new InvalidArgumentException('Selecciona una zona válida.');
                    }

                    if ($direccion === '') {
                        throw new InvalidArgumentException('La dirección es obligatoria.');
                    }
                }

                $registro = Inscripcion::query()->create([
                    'tipo_formulario' => $tipoFormulario,
                    'nombre' => Texto::titulo($nombre),
                    'apellido' => Texto::titulo($apellido),
                    'celular' => $celular,
                    'email' => $email !== '' ? Texto::email($email) : null,
                    'zona' => $zona,
                    'direccion' => $direccion !== '' ? Texto::titulo($direccion) : '',
                    'estado_bautismo' => 'ingresado',
                    'ip_cliente' => $request->ip(),
                    'agente_usuario' => 'Formulario Publico',
                ]);

                $mensaje = $tipoFormulario === 'conexion'
                    ? '¡Gracias por registrarte! Nos comunicaremos contigo pronto.'
                    : '¡Registro enviado correctamente!';

                return response()->json([
                    'exito' => true,
                    'mensaje' => $mensaje,
                    'id' => (int) $registro->id,
                ]);
            }

            if ($tipoFormulario === 'presentacion_ninos') {
                return response()->json(ApiPresentaciones::registrarPublico($payload, [
                    'ip_cliente' => $request->ip(),
                    'agente_usuario' => 'Formulario Publico',
                ]));
            }

            if ($tipoFormulario === 'ofrenda') {
                $usuarioSesion = ApiSesion::validar(ApiSesion::tokenDesdeRequest($request, $payload));

                if (! $usuarioSesion) {
                    return response()->json([
                        'exito' => false,
                        'mensaje' => 'Debes iniciar sesión para registrar ofrendas.',
                    ], 401);
                }

                if (! Permisos::puedeRegistrar($usuarioSesion['rol'], 'ofrendas')) {
                    return response()->json([
                        'exito' => false,
                        'mensaje' => 'Tu rol no tiene permiso para registrar ofrendas.',
                    ], 403);
                }

                $casaId = (int) ($payload['casa_id'] ?? 0);
                $fecha = trim((string) ($payload['fecha_ofrenda'] ?? ''));
                $monto = isset($payload['monto']) ? (float) $payload['monto'] : 0;

                if ($casaId <= 0 || $fecha === '' || $monto <= 0) {
                    throw new InvalidArgumentException('Completa casa de vida, fecha y un valor mayor a cero.');
                }

                try {
                    $fechaObj = Carbon::createFromFormat('Y-m-d', $fecha);
                } catch (Throwable) {
                    $fechaObj = null;
                }

                if (! $fechaObj || $fechaObj->format('Y-m-d') !== $fecha) {
                    throw new InvalidArgumentException('Fecha no válida.');
                }

                $casa = CasaVida::query()->with('lider')->find($casaId);

                if (! $casa) {
                    throw new InvalidArgumentException('Casa de vida no encontrada.');
                }

                $registro = Ofrenda::query()->create([
                    'casa_id' => (int) $casa->id,
                    'casa_vida' => (string) $casa->nombre,
                    'lider' => $casa->lider?->nombreCompleto() ?: '',
                    'fecha_ofrenda' => $fecha,
                    'monto' => $monto,
                    'registrado_por_id' => $usuarioSesion['id'],
                    'registrado_por_nombre' => $usuarioSesion['nombre'],
                ]);

                return response()->json([
                    'exito' => true,
                    'mensaje' => 'Ofrenda registrada correctamente.',
                    'id' => (int) $registro->id,
                ]);
            }

            return response()->json(['exito' => false, 'mensaje' => 'Tipo de formulario no válido.'], 400);
        } catch (InvalidArgumentException $e) {
            return response()->json(['exito' => false, 'mensaje' => $e->getMessage()], 400);
        } catch (Throwable) {
            return response()->json(['exito' => false, 'mensaje' => 'Error interno del servidor.'], 500);
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
