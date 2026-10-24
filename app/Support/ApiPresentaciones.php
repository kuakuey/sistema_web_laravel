<?php

namespace App\Support;

use App\Models\PresentacionNino;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ApiPresentaciones
{
    /**
     * @return array<string, mixed>
     */
    public static function meta(): array
    {
        return [
            'opciones_parentesco' => Permisos::parentescos(),
        ];
    }

    public static function etiquetaEstado(string $estado): string
    {
        return [
            'recibido' => 'Registrado',
            'contactado' => 'Contactado',
            'confirmado' => 'Confirmado',
            'entregar_diploma' => 'Entregar Diploma',
            'presentado' => 'Presentado',
        ][$estado] ?? $estado;
    }

    /**
     * Flujo del panel (sin Entregar Diploma, que solo usa el plugin).
     *
     * @return array<int, string>
     */
    public static function flujoPanel(): array
    {
        return ['recibido', 'contactado', 'confirmado', 'presentado'];
    }

    /**
     * @return array<string, string>
     */
    public static function etiquetasFlujoPanel(): array
    {
        $etiquetas = [];

        foreach (self::flujoPanel() as $estado) {
            $etiquetas[$estado] = self::etiquetaEstado($estado);
        }

        return $etiquetas;
    }

    public static function indiceFlujo(string $estado): int
    {
        if ($estado === 'entregar_diploma') {
            return 2;
        }

        $indice = array_search($estado, self::flujoPanel(), true);

        return $indice === false ? 0 : (int) $indice;
    }

    public static function siguienteEstado(string $estado): ?string
    {
        $flujo = self::flujoPanel();
        $siguiente = $flujo[self::indiceFlujo($estado) + 1] ?? null;

        return is_string($siguiente) ? $siguiente : null;
    }

    /**
     * @return array<int, string>
     */
    public static function estadosValidos(): array
    {
        return ['recibido', 'contactado', 'confirmado', 'entregar_diploma', 'presentado'];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function listarPublicas(): array
    {
        return PresentacionNino::query()
            ->whereIn('estado', ['contactado', 'confirmado'])
            ->orderByDesc('creado_en')
            ->orderByDesc('id')
            ->limit(300)
            ->get()
            ->map(fn (PresentacionNino $fila) => self::formatearPublica($fila))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function respuestaListaPublica(): array
    {
        $lista = self::listarPublicas();

        return [
            'exito' => true,
            'meta' => self::meta(),
            'registros' => $lista,
            'total' => count($lista),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $meta
     * @return array<string, mixed>
     */
    public static function registrarPublico(array $payload, array $meta): array
    {
        $representantes = self::normalizarRepresentantes($payload);
        $presentados = self::parsearPresentados($payload);
        $ids = [];

        DB::transaction(function () use ($representantes, $presentados, $meta, &$ids) {
            foreach ($presentados as $presentado) {
                $registro = PresentacionNino::query()->create(array_merge($representantes, $presentado, [
                    'estado' => 'recibido',
                    'ip_cliente' => $meta['ip_cliente'] ?? '',
                    'agente_usuario' => $meta['agente_usuario'] ?? '',
                ]));
                $ids[] = (int) $registro->id;
            }
        });

        $cantidad = count($ids);
        $registros = [];

        foreach ($ids as $indice => $id) {
            $presentado = $presentados[$indice] ?? ['nombre_presentado' => '', 'fecha_nacimiento' => null];
            $registros[] = [
                'id' => $id,
                'nombre_presentado' => $presentado['nombre_presentado'],
                'fecha_nacimiento' => $presentado['fecha_nacimiento'],
                'representante_1' => self::formatearRepresentanteDesdeDatos($representantes, 1),
                'representante_2' => self::formatearRepresentanteDesdeDatos($representantes, 2),
            ];
        }

        return [
            'exito' => true,
            'mensaje' => $cantidad > 1
                ? $cantidad.' inscripciones de presentación enviadas correctamente.'
                : 'Inscripción de presentación enviada correctamente.',
            'cantidad' => $cantidad,
            'ids' => $ids,
            'id' => $ids[0] ?? null,
            'registros' => $registros,
            'meta' => self::meta(),
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function esPayloadRegistro(array $payload): bool
    {
        if (isset($payload['presentados']) && is_array($payload['presentados'])) {
            return true;
        }

        if (trim((string) ($payload['nombre_presentado'] ?? '')) !== '') {
            return true;
        }

        if (trim((string) ($payload['representante_1_nombre'] ?? '')) !== '') {
            return true;
        }

        if (isset($payload['representantes']) && is_array($payload['representantes']) && $payload['representantes'] !== []) {
            return true;
        }

        return trim((string) ($payload['nombre_padre'] ?? '')) !== '';
    }

    public static function entregarDiploma(int $id): array
    {
        return self::actualizarEstado($id, 'entregar_diploma', Permisos::ADMIN);
    }

    public static function actualizarEstado(int $id, string $estado, string $rol): array
    {
        if ($id <= 0 || $estado === '') {
            throw new InvalidArgumentException('ID y estado son obligatorios.');
        }

        if (! in_array($estado, self::estadosValidos(), true)) {
            throw new InvalidArgumentException('Estado no válido.');
        }

        $presentacion = PresentacionNino::query()->find($id);

        if (! $presentacion) {
            throw new InvalidArgumentException('No se encontró el registro.');
        }

        $puedeControlTotal = Permisos::controlTotal($rol);
        $bloqueado = (bool) $presentacion->estado_bloqueado;
        $estadoActual = (string) $presentacion->estado;

        if (! $puedeControlTotal && $bloqueado) {
            throw new InvalidArgumentException('El estado ya fue marcado como Presentado y no puede modificarse.');
        }

        if ($estado !== 'presentado' && $estadoActual === 'presentado' && ! $puedeControlTotal) {
            throw new InvalidArgumentException('Solo un administrador o superadministrador puede cambiar el estado desde Presentado.');
        }

        if ($estado === 'entregar_diploma' && ! in_array($estadoActual, ['contactado', 'confirmado'], true)) {
            throw new InvalidArgumentException('Solo puedes marcar Entregar Diploma desde Contactado o Confirmado.');
        }

        $fechaPresentacion = null;
        if ($estado === 'presentado') {
            $fechaPresentacion = ($estadoActual === 'presentado' && $presentacion->fecha_presentacion)
                ? optional($presentacion->fecha_presentacion)->toDateString()
                : now()->toDateString();
        }

        $marcarBloqueado = ($estado === 'presentado') ? true : ($puedeControlTotal ? false : $bloqueado);

        $presentacion->estado = $estado;
        $presentacion->fecha_presentacion = $fechaPresentacion;
        $presentacion->estado_bloqueado = $marcarBloqueado;
        $presentacion->save();

        return [
            'exito' => true,
            'mensaje' => 'Estado actualizado a '.self::etiquetaEstado($estado).'.',
            'id' => $id,
            'estado' => $estado,
            'estado_etiqueta' => self::etiquetaEstado($estado),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function formatearPublica(PresentacionNino $fila): array
    {
        $presentacion = self::formatear($fila);
        unset($presentacion['creado_en']);

        $presentacion['representante_1'] = self::acortarRepresentante($presentacion['representante_1'] ?? null);
        $presentacion['representante_2'] = self::acortarRepresentante($presentacion['representante_2'] ?? null);

        $representantes = [];
        if ($presentacion['representante_1']) {
            $representantes[] = $presentacion['representante_1'];
        }
        if ($presentacion['representante_2']) {
            $representantes[] = $presentacion['representante_2'];
        }
        $presentacion['representantes'] = $representantes;

        return $presentacion;
    }

    /**
     * @return array<string, mixed>
     */
    public static function formatear(PresentacionNino $fila): array
    {
        $representante1 = self::formatearRepresentante($fila, 1);
        $representante2 = self::formatearRepresentante($fila, 2);
        $fecha = optional($fila->fecha_nacimiento)?->toDateString();

        return [
            'id' => (int) $fila->id,
            'nombre_presentado' => $fila->nombre_presentado,
            'fecha_nacimiento' => $fecha,
            'fecha_nacimiento_etiqueta' => self::formatearFechaNacimiento($fecha),
            'edad' => self::calcularEdad($fecha),
            'edad_etiqueta' => self::formatearEdad($fecha),
            'representantes' => array_values(array_filter([$representante1, $representante2])),
            'representante_1' => $representante1,
            'representante_2' => $representante2,
            'estado' => $fila->estado,
            'estado_etiqueta' => self::etiquetaEstado((string) $fila->estado),
            'creado_en' => optional($fila->creado_en)?->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function formatearRepresentante(PresentacionNino $fila, int $numero): ?array
    {
        $datos = $numero === 1
            ? [
                'parentesco' => trim((string) ($fila->parentesco_representante_1 ?? '')),
                'nombre' => trim((string) ($fila->nombre_padre ?? '')),
                'telefono' => trim((string) ($fila->telefono_papa ?? '')),
            ]
            : [
                'parentesco' => trim((string) ($fila->parentesco_representante_2 ?? '')),
                'nombre' => trim((string) ($fila->nombre_madre ?? '')),
                'telefono' => trim((string) ($fila->telefono_mama ?? '')),
            ];

        if ($numero === 2 && $datos['nombre'] === '') {
            return null;
        }

        $etiquetaParentesco = Permisos::parentescos()[$datos['parentesco']] ?? ($datos['parentesco'] !== '' ? $datos['parentesco'] : 'Representante');

        return [
            'parentesco' => $datos['parentesco'] !== '' ? $datos['parentesco'] : null,
            'parentesco_etiqueta' => $etiquetaParentesco,
            'nombre' => $datos['nombre'],
            'telefono' => $datos['telefono'],
            'etiqueta' => $datos['nombre'] !== '' ? $etiquetaParentesco.': '.$datos['nombre'] : '—',
        ];
    }

    /**
     * @param  array<string, string|null>  $representantes
     * @return array<string, mixed>|null
     */
    private static function formatearRepresentanteDesdeDatos(array $representantes, int $numero): ?array
    {
        $fila = new PresentacionNino([
            'parentesco_representante_1' => $representantes['parentesco_representante_1'] ?? '',
            'nombre_padre' => $representantes['nombre_padre'] ?? '',
            'telefono_papa' => $representantes['telefono_papa'] ?? '',
            'parentesco_representante_2' => $representantes['parentesco_representante_2'] ?? '',
            'nombre_madre' => $representantes['nombre_madre'] ?? '',
            'telefono_mama' => $representantes['telefono_mama'] ?? '',
        ]);

        return self::formatearRepresentante($fila, $numero);
    }

    /**
     * @param  array<string, mixed>|null  $representante
     * @return array<string, mixed>|null
     */
    private static function acortarRepresentante(?array $representante): ?array
    {
        if ($representante === null) {
            return null;
        }

        $nombre = trim((string) ($representante['nombre'] ?? ''));
        $partes = preg_split('/\s+/u', $nombre, 2) ?: [];
        $nombreCorto = $partes[0] ?? $nombre;
        $parentescoEtiqueta = (string) ($representante['parentesco_etiqueta'] ?? 'Representante');

        $representante['nombre'] = $nombreCorto;
        $representante['etiqueta'] = $nombreCorto !== ''
            ? $parentescoEtiqueta.': '.$nombreCorto
            : '—';

        return $representante;
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, string|null>
     */
    private static function normalizarRepresentantes(array $datos): array
    {
        $datos = self::mapearCamposLegacy($datos);

        $rep1 = [
            'parentesco' => trim((string) ($datos['representante_1_parentesco'] ?? '')),
            'nombre' => Texto::titulo($datos['representante_1_nombre'] ?? ''),
            'telefono' => trim((string) ($datos['representante_1_telefono'] ?? '')),
        ];
        $rep2 = [
            'parentesco' => trim((string) ($datos['representante_2_parentesco'] ?? '')),
            'nombre' => Texto::titulo($datos['representante_2_nombre'] ?? ''),
            'telefono' => trim((string) ($datos['representante_2_telefono'] ?? '')),
        ];

        $parentescos = Permisos::parentescos();

        if (! isset($parentescos[$rep1['parentesco']]) || $rep1['nombre'] === '' || $rep1['telefono'] === '') {
            throw new InvalidArgumentException('Completa los datos del representante legal.');
        }

        $segundo = $rep2['nombre'] !== '';

        if ($segundo && (! isset($parentescos[$rep2['parentesco']]) || $rep2['telefono'] === '')) {
            throw new InvalidArgumentException('Completa los datos del segundo representante o déjalo vacío.');
        }

        return [
            'parentesco_representante_1' => $rep1['parentesco'],
            'nombre_padre' => $rep1['nombre'],
            'telefono_papa' => $rep1['telefono'],
            'parentesco_representante_2' => $segundo ? $rep2['parentesco'] : null,
            'nombre_madre' => $segundo ? $rep2['nombre'] : null,
            'telefono_mama' => $segundo ? $rep2['telefono'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private static function mapearCamposLegacy(array $datos): array
    {
        if (isset($datos['representantes']) && is_array($datos['representantes'])) {
            $representantes = array_values($datos['representantes']);

            if (isset($representantes[0]) && is_array($representantes[0])) {
                $datos['representante_1_parentesco'] = $representantes[0]['parentesco'] ?? '';
                $datos['representante_1_nombre'] = $representantes[0]['nombre'] ?? '';
                $datos['representante_1_telefono'] = $representantes[0]['telefono'] ?? '';
            }

            if (isset($representantes[1]) && is_array($representantes[1])) {
                $datos['representante_2_parentesco'] = $representantes[1]['parentesco'] ?? '';
                $datos['representante_2_nombre'] = $representantes[1]['nombre'] ?? '';
                $datos['representante_2_telefono'] = $representantes[1]['telefono'] ?? '';
            }
        }

        if (trim((string) ($datos['representante_1_nombre'] ?? '')) === '' && trim((string) ($datos['nombre_padre'] ?? '')) !== '') {
            $datos['representante_1_nombre'] = $datos['nombre_padre'];
            $datos['representante_1_telefono'] = $datos['telefono_papa'] ?? '';
            if (trim((string) ($datos['representante_1_parentesco'] ?? '')) === '') {
                $datos['representante_1_parentesco'] = 'padre';
            }
        }

        if (trim((string) ($datos['representante_2_nombre'] ?? '')) === '' && trim((string) ($datos['nombre_madre'] ?? '')) !== '') {
            $datos['representante_2_nombre'] = $datos['nombre_madre'];
            $datos['representante_2_telefono'] = $datos['telefono_mama'] ?? '';
            if (trim((string) ($datos['representante_2_parentesco'] ?? '')) === '') {
                $datos['representante_2_parentesco'] = 'madre';
            }
        }

        return $datos;
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<int, array{nombre_presentado: string, fecha_nacimiento: string}>
     */
    private static function parsearPresentados(array $datos): array
    {
        if (isset($datos['presentados']) && is_array($datos['presentados'])) {
            $presentados = [];
            $numero = 1;

            foreach ($datos['presentados'] as $item) {
                if (! is_array($item)) {
                    $numero++;
                    continue;
                }

                $nombre = Texto::titulo($item['nombre'] ?? $item['nombre_presentado'] ?? '');
                if ($nombre === '') {
                    $numero++;
                    continue;
                }

                $fecha = self::parsearFechaNacimiento($item);
                if ($fecha === null) {
                    throw new InvalidArgumentException(
                        'Ingresa una fecha de nacimiento válida para el presentado '.$numero.'.'
                    );
                }

                $presentados[] = [
                    'nombre_presentado' => $nombre,
                    'fecha_nacimiento' => $fecha,
                ];
                $numero++;
            }

            if ($presentados === []) {
                throw new InvalidArgumentException('Agrega al menos un presentado con nombre y fecha de nacimiento.');
            }

            return $presentados;
        }

        $nombre = Texto::titulo($datos['nombre_presentado'] ?? '');
        if ($nombre === '') {
            throw new InvalidArgumentException('Agrega al menos un presentado con nombre y fecha de nacimiento.');
        }

        $fecha = self::parsearFechaNacimiento($datos);
        if ($fecha === null) {
            throw new InvalidArgumentException('Ingresa una fecha de nacimiento válida (día, mes y año).');
        }

        return [[
            'nombre_presentado' => $nombre,
            'fecha_nacimiento' => $fecha,
        ]];
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private static function parsearFechaNacimiento(array $datos): ?string
    {
        if (! empty($datos['fecha_nacimiento'])) {
            $fecha = trim((string) $datos['fecha_nacimiento']);

            try {
                $dt = Carbon::createFromFormat('Y-m-d', $fecha);
                if ($dt && $dt->format('Y-m-d') === $fecha && $fecha <= now()->toDateString() && (int) $dt->format('Y') >= 1900) {
                    return $fecha;
                }
            } catch (\Throwable) {
                // Continuar con día/mes/año.
            }
        }

        $dia = (int) ($datos['dia_nacimiento'] ?? 0);
        $mes = (int) ($datos['mes_nacimiento'] ?? 0);
        $anio = (int) ($datos['anio_nacimiento'] ?? 0);

        if ($anio < 1900 || ! checkdate($mes, $dia, $anio)) {
            return null;
        }

        $fecha = sprintf('%04d-%02d-%02d', $anio, $mes, $dia);

        return $fecha > now()->toDateString() ? null : $fecha;
    }

    private static function calcularEdad(?string $fecha): ?int
    {
        if ($fecha === null || $fecha === '') {
            return null;
        }

        try {
            $nacimiento = Carbon::createFromFormat('Y-m-d', $fecha)->startOfDay();
        } catch (\Throwable) {
            return null;
        }

        if ($nacimiento->gt(Carbon::today())) {
            return null;
        }

        return (int) $nacimiento->diffInYears(Carbon::today());
    }

    private static function formatearEdad(?string $fecha): string
    {
        $edad = self::calcularEdad($fecha);

        if ($edad === null) {
            return '—';
        }

        if ($edad === 0) {
            try {
                $nacimiento = Carbon::createFromFormat('Y-m-d', (string) $fecha)->startOfDay();
                $meses = (int) $nacimiento->diffInMonths(Carbon::today());
            } catch (\Throwable) {
                return '—';
            }

            return $meses.' mes'.($meses === 1 ? '' : 'es');
        }

        return $edad.' año'.($edad === 1 ? '' : 's');
    }

    private static function formatearFechaNacimiento(?string $fecha): string
    {
        if ($fecha === null || $fecha === '') {
            return '—';
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $fecha)->format('d/m/Y');
        } catch (\Throwable) {
            return $fecha;
        }
    }
}
