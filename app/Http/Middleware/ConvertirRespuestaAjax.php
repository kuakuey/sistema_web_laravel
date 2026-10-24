<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConvertirRespuestaAjax
{
    public function handle(Request $request, Closure $next): Response
    {
        $respuesta = $next($request);

        if (! $this->esAjax($request) || $respuesta instanceof JsonResponse) {
            return $respuesta;
        }

        if (! $respuesta instanceof RedirectResponse) {
            return $respuesta;
        }

        $errores = $request->session()->get('errors');

        if ($errores) {
            $mensaje = method_exists($errores, 'first')
                ? (string) $errores->first()
                : 'Revisa los datos enviados.';

            return response()->json([
                'exito' => false,
                'mensaje' => $mensaje !== '' ? $mensaje : 'Revisa los datos enviados.',
                'errores' => method_exists($errores, 'getMessages') ? $errores->getMessages() : [],
            ], 422);
        }

        $mensajeOk = $request->session()->pull('ok');
        $mensajeError = $request->session()->pull('error');
        $notify = $request->session()->get('notify');
        $exito = $mensajeError === null;

        if ($exito && is_string($mensajeOk) && $mensajeOk !== '' && ! $notify) {
            notify()->success()->title($mensajeOk)->send();
            $notify = $request->session()->get('notify');
        }

        if (! $exito && is_string($mensajeError) && $mensajeError !== '' && ! $notify) {
            notify()->error()->title($mensajeError)->send();
            $notify = $request->session()->get('notify');
        }

        $mensaje = $exito
            ? (is_string($mensajeOk) && $mensajeOk !== ''
                ? $mensajeOk
                : (is_array($notify) ? (string) ($notify['title'] ?? $notify['message'] ?? 'Listo.') : 'Listo.'))
            : (is_string($mensajeError) && $mensajeError !== '' ? $mensajeError : 'No se pudo completar la acción.');

        return response()->json([
            'exito' => $exito,
            'mensaje' => $mensaje,
            'redirect' => $respuesta->getTargetUrl(),
            // pull: el cliente muestra el toast; no dejar flash para la siguiente navegación
            'notify' => $this->serializarNotify($request->session()->pull('notify')),
        ], $exito ? 200 : 422);
    }

    /**
     * @param  mixed  $notify
     * @return array<string, mixed>|null
     */
    private function serializarNotify(mixed $notify): ?array
    {
        if (! is_array($notify)) {
            return null;
        }

        $tipo = $notify['type'] ?? null;
        $modelo = $notify['model'] ?? null;

        return [
            'title' => $notify['title'] ?? null,
            'message' => $notify['message'] ?? null,
            'type' => $tipo instanceof \BackedEnum ? $tipo->value : (string) ($tipo ?? 'info'),
            'model' => $modelo instanceof \BackedEnum ? $modelo->value : (string) ($modelo ?? 'toast'),
            'duration' => (int) ($notify['duration'] ?? config('notify.timeout', 5000)),
        ];
    }

    private function esAjax(Request $request): bool
    {
        return $request->ajax() || $request->expectsJson();
    }
}
