<?php

namespace App\Support;

use App\Models\SesionApi;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ApiSesion
{
    /**
     * @return array{id: int, usuario: string, nombre: string, rol: string}|null
     */
    public static function autenticar(string $usuario, string $clave): ?array
    {
        $registro = User::query()->where('usuario', trim($usuario))->first();

        if (! $registro || ! Hash::check($clave, (string) $registro->clave)) {
            return null;
        }

        return self::usuarioDesdeModelo($registro);
    }

    public static function crear(int $idUsuario): string
    {
        $token = bin2hex(random_bytes(32));
        $horas = max(1, (int) config('cda_api.sesion_horas', 8));

        SesionApi::query()->create([
            'usuario_id' => $idUsuario,
            'token' => $token,
            'expira_en' => now()->addHours($horas),
        ]);

        return $token;
    }

    /**
     * @return array{id: int, usuario: string, nombre: string, rol: string}|null
     */
    public static function validar(string $token): ?array
    {
        $token = trim($token);

        if ($token === '') {
            return null;
        }

        $sesion = SesionApi::query()
            ->where('token', $token)
            ->where('expira_en', '>', now())
            ->first();

        if (! $sesion) {
            return null;
        }

        $usuario = User::query()->find($sesion->usuario_id);

        return $usuario ? self::usuarioDesdeModelo($usuario) : null;
    }

    public static function cerrar(string $token): void
    {
        $token = trim($token);

        if ($token === '') {
            return;
        }

        SesionApi::query()->where('token', $token)->delete();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function tokenDesdeRequest(Request $request, array $payload = []): string
    {
        foreach (['X-Token-Sesion', 'X-Token-Session'] as $header) {
            $valor = trim((string) $request->header($header, ''));

            if ($valor !== '') {
                return $valor;
            }
        }

        return trim((string) ($payload['token_sesion'] ?? ''));
    }

    /**
     * @return array{id: int, usuario: string, nombre: string, rol: string}
     */
    private static function usuarioDesdeModelo(User $usuario): array
    {
        return [
            'id' => (int) $usuario->id,
            'usuario' => (string) $usuario->usuario,
            'nombre' => $usuario->nombreVisible(),
            'rol' => Permisos::normalizarRol((string) $usuario->rol),
        ];
    }
}
