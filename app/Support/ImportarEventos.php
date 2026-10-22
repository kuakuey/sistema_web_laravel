<?php

namespace App\Support;

use App\Models\Evento;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportarEventos
{
    /**
     * @return array<string, string>
     */
    public static function columnas(): array
    {
        return [
            'evento' => 'Evento',
            'tipo_entrada' => 'Tipo entrada',
            'nombre' => 'Nombre',
            'telefono' => 'Teléfono',
            'fecha' => 'Fecha',
            'valor' => 'Valor',
            'numeracion' => 'Numeración',
            'estado_pago' => 'Estado',
            'forma_pago' => 'Forma de pago',
            'observacion' => 'Observación',
        ];
    }

    public static function plantilla(): StreamedResponse
    {
        $nombre = 'plantilla-registros-eventos.csv';

        return response()->streamDownload(function () {
            $salida = fopen('php://output', 'w');
            fwrite($salida, "\xEF\xBB\xBF");
            fputcsv($salida, array_values(self::columnas()), ';');
            fputcsv($salida, [
                'Boda Masiva',
                'Inscripción',
                'Nombre de ejemplo',
                '0999999999',
                now()->toDateString(),
                '0',
                '',
                'pagado',
                'efectivo',
                '',
            ], ';');
            fclose($salida);
        }, $nombre, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array{importados: int, omitidos: int, errores: array<int, array{fila: int, mensaje: string}>}
     */
    public static function procesar(UploadedFile $archivo, User $usuario): array
    {
        $ruta = $archivo->getRealPath();

        if (! $ruta) {
            throw new InvalidArgumentException('No se pudo leer el archivo.');
        }

        $handle = fopen($ruta, 'r');

        if (! $handle) {
            throw new InvalidArgumentException('No se pudo abrir el archivo.');
        }

        $primera = fgets($handle) ?: '';
        rewind($handle);

        if (str_starts_with($primera, "\xEF\xBB\xBF")) {
            fseek($handle, 3);
        }

        $delimitador = substr_count($primera, ';') >= substr_count($primera, ',') ? ';' : ',';
        $encabezados = fgetcsv($handle, 0, $delimitador) ?: [];
        $mapa = self::mapaEncabezados($encabezados);

        if (! isset($mapa['evento'], $mapa['nombre'], $mapa['telefono'])) {
            fclose($handle);
            throw new InvalidArgumentException('El archivo no tiene las columnas Evento, Nombre y Teléfono.');
        }

        $eventos = Evento::query()->with(['tiposEntrada', 'camposAdicionales'])->where('habilitado', true)->get();
        $importados = 0;
        $errores = [];
        $fila = 1;

        while (($datos = fgetcsv($handle, 0, $delimitador)) !== false) {
            $fila++;

            if (self::filaVacia($datos)) {
                continue;
            }

            try {
                $entrada = self::filaAEntrada($datos, $mapa, $eventos);
                Eventos::registrarParticipante($entrada, $usuario);
                $importados++;
            } catch (InvalidArgumentException $e) {
                $errores[] = ['fila' => $fila, 'mensaje' => $e->getMessage()];
            }
        }

        fclose($handle);

        return [
            'importados' => $importados,
            'omitidos' => count($errores),
            'errores' => $errores,
        ];
    }

    /**
     * @param  array<int, string|null>  $encabezados
     * @return array<string, int>
     */
    private static function mapaEncabezados(array $encabezados): array
    {
        $esperado = [];

        foreach (self::columnas() as $clave => $etiqueta) {
            $esperado[self::normalizar($etiqueta)] = $clave;
            $esperado[self::normalizar($clave)] = $clave;
        }

        $mapa = [];

        foreach ($encabezados as $indice => $titulo) {
            $clave = $esperado[self::normalizar((string) $titulo)] ?? null;

            if ($clave) {
                $mapa[$clave] = $indice;
            }
        }

        return $mapa;
    }

    /**
     * @param  array<int, string|null>  $datos
     * @param  array<string, int>  $mapa
     * @param  \Illuminate\Support\Collection<int, Evento>  $eventos
     * @return array<string, mixed>
     */
    private static function filaAEntrada(array $datos, array $mapa, $eventos): array
    {
        $valor = static fn (string $clave): string => trim((string) ($datos[$mapa[$clave] ?? -1] ?? ''));
        $nombreEvento = $valor('evento');
        $evento = $eventos->first(fn (Evento $item) => mb_strtolower(trim((string) $item->nombre)) === mb_strtolower($nombreEvento));

        if (! $evento) {
            throw new InvalidArgumentException('No existe un evento habilitado llamado «'.$nombreEvento.'».');
        }

        $nombreTipo = $valor('tipo_entrada');
        $tipo = $evento->tiposEntrada->first(
            fn ($item) => mb_strtolower(trim((string) $item->nombre)) === mb_strtolower($nombreTipo)
        );

        if ($evento->tiposEntrada->isNotEmpty() && ! $tipo) {
            throw new InvalidArgumentException('El tipo de entrada «'.$nombreTipo.'» no existe en '.$evento->nombre.'.');
        }

        $estado = mb_strtolower($valor('estado_pago'));
        $estado = in_array($estado, ['pagado', 'pago'], true) ? 'pagado' : 'por_cancelar';

        return [
            'evento_id' => $evento->id,
            'tipo_entrada_id' => $tipo?->id,
            'nombre' => $valor('nombre'),
            'telefono' => $valor('telefono'),
            'fecha' => $valor('fecha') !== '' ? $valor('fecha') : now()->toDateString(),
            'valor' => $valor('valor'),
            'numeracion' => $valor('numeracion'),
            'estado_pago' => $estado,
            'forma_pago' => mb_strtolower($valor('forma_pago')),
            'observacion' => $valor('observacion'),
        ];
    }

    /**
     * @param  array<int, string|null>  $datos
     */
    private static function filaVacia(array $datos): bool
    {
        foreach ($datos as $celda) {
            if (trim((string) $celda) !== '') {
                return false;
            }
        }

        return true;
    }

    private static function normalizar(string $texto): string
    {
        $texto = mb_strtolower(trim($texto));
        $texto = strtr($texto, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n']);

        return preg_replace('/[^a-z0-9]+/', '', $texto) ?? '';
    }
}
