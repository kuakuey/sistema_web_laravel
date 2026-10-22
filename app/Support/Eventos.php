<?php

namespace App\Support;

use App\Models\Evento;
use App\Models\EventoCampoAdicional;
use App\Models\EventoTipoEntrada;
use App\Models\User;
use App\Models\ValorAdicional;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class Eventos
{
    public const TIPO = 'eventos';

    /**
     * @return array<string, string>
     */
    public static function formasPago(): array
    {
        return [
            'pago' => 'Pago',
            'gratuito' => 'Gratuito',
            'pendiente' => 'Pendiente',
            'efectivo' => 'Efectivo',
            'transferencia' => 'Transferencia',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function estadosPago(): array
    {
        return [
            'por_cancelar' => 'Por cancelar',
            'pagado' => 'Pagado',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function tiposCampo(): array
    {
        return [
            'texto' => 'Texto',
            'lista' => 'Lista',
            'numero' => 'Número',
            'fecha' => 'Fecha',
        ];
    }

    public static function etiquetaPago(?string $estado): string
    {
        $estado = self::normalizarEstado((string) $estado);

        return self::estadosPago()[$estado] ?? '—';
    }

    public static function etiquetaForma(?string $forma): string
    {
        $forma = trim(mb_strtolower((string) $forma));

        return self::formasPago()[$forma] ?? ($forma !== '' ? $forma : '—');
    }

    public static function slug(Evento $evento): string
    {
        $base = self::slugificar((string) $evento->nombre);

        return $base !== '' ? $base.'-'.$evento->id : (string) $evento->id;
    }

    public static function slugificar(string $nombre): string
    {
        $texto = trim($nombre);
        $texto = strtr($texto, [
            'Á' => 'a', 'À' => 'a', 'Ä' => 'a', 'Â' => 'a',
            'É' => 'e', 'È' => 'e', 'Ë' => 'e', 'Ê' => 'e',
            'Í' => 'i', 'Ì' => 'i', 'Ï' => 'i', 'Î' => 'i',
            'Ó' => 'o', 'Ò' => 'o', 'Ö' => 'o', 'Ô' => 'o',
            'Ú' => 'u', 'Ù' => 'u', 'Ü' => 'u', 'Û' => 'u',
            'Ñ' => 'n', 'Ç' => 'c',
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'ñ' => 'n', 'ç' => 'c',
        ]);

        $texto = strtolower($texto);
        $texto = str_replace(["'", '`', '^', '~', '"'], '', $texto);
        $texto = trim((string) preg_replace('/[^a-z0-9]+/', '-', $texto), '-');

        return $texto !== '' ? $texto : 'evento';
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    public static function normalizarCatalogo(array $datos): array
    {
        $nombre = Texto::titulo($datos['nombre'] ?? '');
        $fecha = trim((string) ($datos['fecha'] ?? ''));

        if ($nombre === '' || $fecha === '') {
            throw new InvalidArgumentException('Nombre y fecha son obligatorios.');
        }

        $tipos = self::normalizarTipos($datos['tipo_entrada'] ?? $datos['tipos_entrada'] ?? []);
        $campos = self::normalizarCampos($datos['campo_adicional'] ?? $datos['campos_adicionales'] ?? []);

        return [
            'nombre' => $nombre,
            'fecha' => $fecha,
            'valor' => self::valorDesdeTipos($tipos),
            'habilitado' => ! empty($datos['habilitado']) ? 1 : 0,
            'requiere_numeracion' => ! empty($datos['requiere_numeracion']) ? 1 : 0,
            'tipos_entrada' => $tipos,
            'campos_adicionales' => $campos,
        ];
    }

    /**
     * @param  array<string, mixed>|array<int, mixed>  $tipos
     * @return array<int, array{nombre: string, valor: float, visible_publico: int, es_gratis: int, prefijo: string}>
     */
    public static function normalizarTipos(array $tipos): array
    {
        if (isset($tipos['nombre']) || isset($tipos['valor'])) {
            $nombres = (array) ($tipos['nombre'] ?? []);
            $valores = (array) ($tipos['valor'] ?? []);
            $visibles = (array) ($tipos['visible_publico'] ?? []);
            $gratis = (array) ($tipos['es_gratis'] ?? []);
            $prefijos = (array) ($tipos['prefijo'] ?? []);
            $tipos = [];
            $total = max(count($nombres), count($valores), count($visibles), count($gratis), count($prefijos));

            for ($i = 0; $i < $total; $i++) {
                $tipos[] = [
                    'nombre' => $nombres[$i] ?? '',
                    'valor' => $valores[$i] ?? 0,
                    'visible_publico' => $visibles[$i] ?? 1,
                    'es_gratis' => $gratis[$i] ?? 0,
                    'prefijo' => $prefijos[$i] ?? '',
                ];
            }
        }

        $normalizados = [];

        foreach ($tipos as $tipo) {
            if (! is_array($tipo)) {
                continue;
            }

            $nombre = Texto::titulo($tipo['nombre'] ?? '');

            if ($nombre === '') {
                continue;
            }

            $esGratis = ! empty($tipo['es_gratis']);
            $valor = (float) ($tipo['valor'] ?? 0);

            if ($esGratis || $valor < 0) {
                $valor = 0.0;
            }

            $normalizados[] = [
                'nombre' => $nombre,
                'valor' => $valor,
                'visible_publico' => ! empty($tipo['visible_publico']) ? 1 : 0,
                'es_gratis' => $esGratis ? 1 : 0,
                'prefijo' => self::prefijo($tipo['prefijo'] ?? ''),
            ];
        }

        if ($normalizados === []) {
            throw new InvalidArgumentException('Agrega al menos un tipo de entrada.');
        }

        return $normalizados;
    }

    /**
     * @param  array<string, mixed>|array<int, mixed>  $campos
     * @return array<int, array{etiqueta: string, tipo: string, opciones: array<int, string>, obligatorio: int}>
     */
    public static function normalizarCampos(array $campos): array
    {
        if (isset($campos['etiqueta']) || isset($campos['tipo'])) {
            $etiquetas = (array) ($campos['etiqueta'] ?? []);
            $tipos = (array) ($campos['tipo'] ?? []);
            $obligatorios = (array) ($campos['obligatorio'] ?? []);
            $opciones = (array) ($campos['opciones'] ?? []);
            $campos = [];
            $total = max(count($etiquetas), count($tipos), count($obligatorios), count($opciones));

            for ($i = 0; $i < $total; $i++) {
                $campos[] = [
                    'etiqueta' => $etiquetas[$i] ?? '',
                    'tipo' => $tipos[$i] ?? 'texto',
                    'obligatorio' => $obligatorios[$i] ?? 0,
                    'opciones' => $opciones[$i] ?? '',
                ];
            }
        }

        $normalizados = [];
        $vistas = [];

        foreach ($campos as $campo) {
            if (! is_array($campo)) {
                continue;
            }

            $etiqueta = Texto::titulo($campo['etiqueta'] ?? '');

            if ($etiqueta === '') {
                continue;
            }

            $clave = mb_strtolower($etiqueta);

            if (isset($vistas[$clave])) {
                throw new InvalidArgumentException('No repitas el mismo dato adicional: '.$etiqueta.'.');
            }

            $vistas[$clave] = true;
            $tipo = array_key_exists((string) ($campo['tipo'] ?? ''), self::tiposCampo())
                ? (string) $campo['tipo']
                : 'texto';
            $lista = $tipo === 'lista' ? self::opcionesLista($campo['opciones'] ?? '') : [];

            if ($tipo === 'lista' && count($lista) < 2) {
                throw new InvalidArgumentException('La lista «'.$etiqueta.'» necesita al menos 2 opciones (una por línea).');
            }

            $normalizados[] = [
                'etiqueta' => $etiqueta,
                'tipo' => $tipo,
                'opciones' => $lista,
                'obligatorio' => ! empty($campo['obligatorio']) ? 1 : 0,
            ];
        }

        if (count($normalizados) > 10) {
            throw new InvalidArgumentException('Puedes solicitar como máximo 10 datos adicionales por evento.');
        }

        return $normalizados;
    }

    public static function guardarCatalogo(?Evento $evento, array $datos): Evento
    {
        $datos = self::normalizarCatalogo($datos);

        return DB::transaction(function () use ($evento, $datos) {
            $evento ??= new Evento;
            $evento->fill([
                'nombre' => $datos['nombre'],
                'fecha' => $datos['fecha'],
                'valor' => $datos['valor'],
                'habilitado' => $datos['habilitado'],
                'requiere_numeracion' => $datos['requiere_numeracion'],
            ]);
            $evento->save();

            EventoTipoEntrada::query()->where('evento_id', $evento->id)->delete();
            EventoCampoAdicional::query()->where('evento_id', $evento->id)->delete();

            foreach ($datos['tipos_entrada'] as $orden => $tipo) {
                EventoTipoEntrada::query()->create([
                    'evento_id' => $evento->id,
                    'nombre' => $tipo['nombre'],
                    'valor' => $tipo['valor'],
                    'orden' => $orden,
                    'visible_publico' => $tipo['visible_publico'],
                    'es_gratis' => $tipo['es_gratis'],
                    'prefijo' => $tipo['prefijo'] ?: null,
                ]);
            }

            foreach ($datos['campos_adicionales'] as $orden => $campo) {
                EventoCampoAdicional::query()->create([
                    'evento_id' => $evento->id,
                    'etiqueta' => $campo['etiqueta'],
                    'tipo' => $campo['tipo'],
                    'opciones' => $campo['opciones'] === [] ? null : json_encode($campo['opciones'], JSON_UNESCAPED_UNICODE),
                    'obligatorio' => $campo['obligatorio'],
                    'orden' => $orden,
                ]);
            }

            return $evento->fresh(['tiposEntrada', 'camposAdicionales']);
        });
    }

    /**
     * @param  array<string, mixed>  $entrada
     * @return array<string, mixed>
     */
    public static function validarRegistro(array $entrada, string $rol, ?Evento $evento = null): array
    {
        $eventoId = (int) ($entrada['evento_id'] ?? 0);

        if ($evento === null) {
            $evento = Evento::query()->with(['tiposEntrada', 'camposAdicionales'])->find($eventoId);

            if (! $evento || ! $evento->habilitado) {
                throw new InvalidArgumentException('Selecciona un evento habilitado.');
            }
        }

        $nombre = Texto::titulo($entrada['nombre'] ?? '');
        $fecha = trim((string) ($entrada['fecha'] ?? ''));
        $telefono = trim((string) ($entrada['telefono'] ?? ''));
        $observacion = Texto::titulo($entrada['observacion'] ?? '');
        $numeracion = trim((string) ($entrada['numeracion'] ?? ''));
        $tipoEntradaId = (int) ($entrada['tipo_entrada_id'] ?? 0);
        $valor = isset($entrada['valor']) && $entrada['valor'] !== '' ? (float) $entrada['valor'] : null;
        $formaPago = trim(mb_strtolower((string) ($entrada['forma_pago'] ?? '')));
        $estadoPago = self::normalizarEstado((string) ($entrada['estado_pago'] ?? ''));

        $tipos = $evento->tiposEntrada;
        $tipo = $tipos->firstWhere('id', $tipoEntradaId);

        if ($tipos->isNotEmpty() && ! $tipo) {
            throw new InvalidArgumentException('Selecciona un tipo de entrada.');
        }

        if ($tipo && ! $tipo->visible_publico && ! Permisos::controlTotal($rol)) {
            throw new InvalidArgumentException('No tienes permiso para usar este tipo de entrada.');
        }

        if ($valor === null) {
            $valor = $tipo ? (float) $tipo->valor : (float) $evento->valor;
        }

        if ($valor < 0) {
            throw new InvalidArgumentException('El valor no puede ser negativo.');
        }

        $gratis = $tipo && $tipo->es_gratis;

        if ($gratis) {
            $formaPago = 'gratuito';
            $valor = 0;
            $estadoPago = 'pagado';
        } elseif (Permisos::registroEventoRequierePagoInmediato($rol)) {
            $estadoPago = 'pagado';

            if (! in_array($formaPago, ['efectivo', 'transferencia'], true)) {
                throw new InvalidArgumentException('Selecciona Efectivo o Transferencia.');
            }
        } elseif (! Permisos::puedeCrearRegistroEventoPendiente($rol)) {
            $estadoPago = 'por_cancelar';
            $formaPago = 'pendiente';
        } elseif ($estadoPago === 'pagado') {
            if (! in_array($formaPago, ['efectivo', 'transferencia'], true)) {
                throw new InvalidArgumentException('Selecciona Efectivo o Transferencia.');
            }
        } else {
            $estadoPago = 'por_cancelar';
            $formaPago = 'pendiente';
        }

        if ($nombre === '' || $fecha === '' || $telefono === '') {
            throw new InvalidArgumentException('Completa todos los campos obligatorios.');
        }

        if ($evento->requiere_numeracion && $numeracion === '') {
            throw new InvalidArgumentException('La numeración es obligatoria para este evento.');
        }

        if ($numeracion !== '' && $tipo) {
            $numeracion = self::componerNumeracion((string) $tipo->prefijo, $numeracion);
        }

        return [
            'tipo' => self::TIPO,
            'evento_id' => $evento->id,
            'nombre' => $nombre,
            'fecha' => $fecha,
            'telefono' => $telefono,
            'valor' => $valor,
            'observacion' => $observacion,
            'numeracion' => $numeracion !== '' ? $numeracion : null,
            'forma_pago' => $formaPago,
            'tipo_entrada_id' => $tipo?->id,
            'tipo_entrada' => $tipo?->nombre,
            'estado_pago' => $estadoPago,
            'info_adicional' => self::respuestasAdicionales($entrada, $evento->camposAdicionales),
        ];
    }

    public static function registrarParticipante(array $entrada, User $usuario): ValorAdicional
    {
        $datos = self::validarRegistro($entrada, $usuario->rolNormalizado());
        $datos['registrado_por_id'] = $usuario->id;
        $datos['registrado_por_nombre'] = $usuario->nombreVisible();

        return ValorAdicional::query()->create($datos);
    }

    /**
     * @return array<string, mixed>
     */
    public static function ticket(ValorAdicional $registro, string $rol): array
    {
        $asistio = (bool) $registro->asistio;

        return [
            'id' => $registro->id,
            'nombre' => $registro->nombre,
            'numeracion' => (string) $registro->numeracion,
            'tipo_entrada' => (string) $registro->tipo_entrada,
            'estado' => self::etiquetaPago($registro->estado_pago),
            'asistio' => $asistio,
            'asistencia' => $asistio
                ? ('Asistió'.($registro->asistio_en ? ' · '.$registro->asistio_en->format('d/m/Y H:i') : ''))
                : 'No asistió',
            'puede_reversar' => $asistio && Permisos::controlTotal($rol),
        ];
    }

    public static function marcarAsistencia(ValorAdicional $registro, User $usuario): array
    {
        $evento = $registro->evento;

        if (! $evento || ! $evento->habilitado) {
            throw new InvalidArgumentException('Solo se puede marcar asistencia en eventos habilitados.');
        }

        if (! $registro->asistio) {
            $registro->fill([
                'asistio' => true,
                'asistio_en' => now(),
                'asistio_por_id' => $usuario->id,
                'asistio_por_nombre' => $usuario->nombreVisible(),
            ])->save();
        }

        return self::ticket($registro->fresh(), $usuario->rolNormalizado());
    }

    public static function reversarAsistencia(ValorAdicional $registro, User $usuario): array
    {
        if (! Permisos::controlTotal($usuario->rolNormalizado())) {
            throw new InvalidArgumentException('Solo un administrador puede reversar la asistencia.');
        }

        $registro->fill([
            'asistio' => false,
            'asistio_en' => null,
            'asistio_por_id' => null,
            'asistio_por_nombre' => null,
        ])->save();

        return self::ticket($registro->fresh(), $usuario->rolNormalizado());
    }

    public static function formatearTipos(Evento $evento): string
    {
        $tipos = $evento->tiposEntrada;

        if ($tipos->isEmpty()) {
            $valor = (float) $evento->valor;

            return $valor <= 0 ? 'Gratuito' : '$ '.number_format($valor, 2);
        }

        return $tipos->map(function (EventoTipoEntrada $tipo) {
            if ($tipo->es_gratis) {
                return $tipo->nombre.' (Gratis)';
            }

            return $tipo->nombre.' ($ '.number_format((float) $tipo->valor, 2).')';
        })->implode(', ');
    }

    /**
     * @param  iterable<int, EventoTipoEntrada>  $tipos
     * @return array<int, EventoTipoEntrada>
     */
    public static function tiposVisibles(iterable $tipos, string $rol): array
    {
        $lista = collect($tipos);

        if (Permisos::controlTotal($rol)) {
            return $lista->all();
        }

        return $lista->filter(fn (EventoTipoEntrada $tipo) => $tipo->visible_publico)->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function eventoParaJs(Evento $evento, string $rol): array
    {
        return [
            'id' => $evento->id,
            'valor' => (float) $evento->valor,
            'requiere_numeracion' => (bool) $evento->requiere_numeracion,
            'tipos' => collect(self::tiposVisibles($evento->tiposEntrada, $rol))->map(fn (EventoTipoEntrada $tipo) => [
                'id' => $tipo->id,
                'nombre' => $tipo->nombre,
                'valor' => (float) $tipo->valor,
                'es_gratis' => (bool) $tipo->es_gratis,
                'prefijo' => (string) $tipo->prefijo,
            ])->values()->all(),
            'campos' => $evento->camposAdicionales->map(fn (EventoCampoAdicional $campo) => [
                'id' => $campo->id,
                'etiqueta' => $campo->etiqueta,
                'tipo' => $campo->tipo,
                'opciones' => $campo->opcionesLista(),
                'obligatorio' => (bool) $campo->obligatorio,
            ])->values()->all(),
        ];
    }

    private static function normalizarEstado(string $estado): string
    {
        $estado = trim(mb_strtolower($estado));

        return array_key_exists($estado, self::estadosPago()) ? $estado : '';
    }

    private static function prefijo(mixed $prefijo): string
    {
        $prefijo = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $prefijo) ?? '');

        return mb_substr($prefijo, 0, 10);
    }

    private static function componerNumeracion(string $prefijo, string $numeracion): string
    {
        $prefijo = self::prefijo($prefijo);
        $numeracion = trim($numeracion);

        if ($prefijo !== '' && preg_match('/^'.preg_quote($prefijo, '/').'-?/i', $numeracion)) {
            $numeracion = trim((string) preg_replace('/^'.preg_quote($prefijo, '/').'-?/i', '', $numeracion, 1));
        }

        if ($numeracion === '') {
            return '';
        }

        $codigo = $prefijo.$numeracion;

        if (mb_strlen($codigo) > 30) {
            throw new InvalidArgumentException('El código de numeración no puede superar 30 caracteres.');
        }

        return $codigo;
    }

    /**
     * @param  array<int, array{nombre: string, valor: float, es_gratis: int}>  $tipos
     */
    private static function valorDesdeTipos(array $tipos): float
    {
        $pagos = array_values(array_filter($tipos, fn (array $tipo) => empty($tipo['es_gratis'])));

        if ($pagos === []) {
            return 0.0;
        }

        return min(array_map(fn (array $tipo) => (float) $tipo['valor'], $pagos));
    }

    /**
     * @return array<int, string>
     */
    private static function opcionesLista(mixed $opciones): array
    {
        if (is_array($opciones)) {
            return array_values(array_filter(array_map(fn ($item) => trim((string) $item), $opciones)));
        }

        $crudo = trim((string) $opciones);

        if ($crudo === '') {
            return [];
        }

        $json = json_decode($crudo, true);

        if (is_array($json)) {
            return array_values(array_filter(array_map(fn ($item) => trim((string) $item), $json)));
        }

        return array_values(array_filter(array_map('trim', preg_split('/\r\n|\n|\r/', $crudo) ?: [])));
    }

    /**
     * @param  array<string, mixed>  $entrada
     * @param  iterable<int, EventoCampoAdicional>  $campos
     */
    private static function respuestasAdicionales(array $entrada, iterable $campos): ?string
    {
        $respuestas = [];

        foreach ($campos as $campo) {
            $valor = trim((string) ($entrada['campo_adicional'][$campo->id] ?? $entrada['campo_adicional_'.$campo->id] ?? ''));

            if ($campo->obligatorio && $valor === '') {
                throw new InvalidArgumentException('Completa el dato «'.$campo->etiqueta.'».');
            }

            if ($valor === '') {
                continue;
            }

            $respuestas[] = [
                'id' => $campo->id,
                'etiqueta' => $campo->etiqueta,
                'valor' => $valor,
            ];
        }

        return $respuestas === [] ? null : json_encode($respuestas, JSON_UNESCAPED_UNICODE);
    }
}
