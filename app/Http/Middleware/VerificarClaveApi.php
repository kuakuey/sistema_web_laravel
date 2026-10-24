<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarClaveApi
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('OPTIONS')) {
            return response('', 204);
        }

        $clave = (string) $request->header('X-API-KEY', '');
        $esperada = (string) config('cda_api.key', '');

        if ($clave === '' || $esperada === '' || ! hash_equals($esperada, $clave)) {
            return response()->json([
                'exito' => false,
                'mensaje' => 'Clave API inválida.',
            ], 401);
        }

        return $next($request);
    }
}
