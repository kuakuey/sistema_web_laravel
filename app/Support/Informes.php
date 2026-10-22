<?php

namespace App\Support;

use App\Models\Evento;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Informes
{
    /**
     * @return array<string, string>
     */
    public static function etiquetas(): array
    {
        return [
            'completo' => 'General',
            'ofrendas' => 'Ofrendas',
            'valores' => 'Valores adicionales',
            'eventos' => 'Eventos',
            'presentaciones' => 'Presentación de niños',
            'bautismos' => 'Personas bautizadas',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function turnos(): array
    {
        return [
            'manana' => 'Matutina',
            'tarde' => 'Vespertina',
            'todos' => 'Todas',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function estadosPresentacion(): array
    {
        return ['recibido', 'contactado', 'confirmado', 'entregar_diploma', 'presentado'];
    }

    /**
     * @return array<string, string>
     */
    public static function etiquetasPresentacion(): array
    {
        return [
            'recibido' => 'Recibido',
            'contactado' => 'Contactado',
            'confirmado' => 'Confirmado',
            'entregar_diploma' => 'Entregar Diploma',
            'presentado' => 'Presentado',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function estadosBautismo(): array
    {
        return [
            'todos' => 'Todos',
            'ingresado' => 'Ingresados',
            'bautizado' => 'Bautizados',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function secciones(string $rol): array
    {
        $permitidas = [];

        foreach (self::etiquetas() as $clave => $etiqueta) {
            if (self::puede($rol, $clave)) {
                $permitidas[$clave] = $etiqueta;
            }
        }

        return $permitidas;
    }

    public static function puede(string $rol, string $seccion): bool
    {
        $seccion = self::normalizarSeccion($seccion);

        if (Permisos::tieneDetalle($rol, 'generar_informe', $seccion) || Permisos::tieneDetalle($rol, 'generar_informe', 'acceso')) {
            return true;
        }

        return $seccion === 'eventos' && Permisos::puedeVerInformeEventos($rol);
    }

    public static function titulo(string $seccion): string
    {
        return match (self::normalizarSeccion($seccion)) {
            'ofrendas' => 'Informe de ofrendas',
            'eventos' => 'Informe de eventos',
            'valores' => 'Informe de valores adicionales',
            'presentaciones' => 'Informe de presentación de niños',
            'bautismos' => 'Informe de personas bautizadas',
            default => 'Informe financiero CDA',
        };
    }

    public static function normalizarSeccion(string $seccion): string
    {
        $seccion = trim(mb_strtolower($seccion));

        return array_key_exists($seccion, self::etiquetas()) ? $seccion : 'completo';
    }

    public static function normalizarTurno(string $turno): string
    {
        $turno = trim(mb_strtolower($turno));

        return array_key_exists($turno, self::turnos()) ? $turno : 'todos';
    }

    /**
     * @param  array<string, mixed>  $entrada
     * @return array<string, mixed>
     */
    public static function filtros(array $entrada): array
    {
        $estados = $entrada['estados_presentacion'] ?? self::estadosPresentacion();

        return [
            'fecha_desde' => trim((string) ($entrada['fecha_desde'] ?? '')),
            'fecha_hasta' => trim((string) ($entrada['fecha_hasta'] ?? '')),
            'turno' => self::normalizarTurno((string) ($entrada['turno'] ?? 'todos')),
            'seccion' => self::normalizarSeccion((string) ($entrada['seccion'] ?? 'completo')),
            'evento_id' => max(0, (int) ($entrada['evento_id'] ?? 0)),
            'mostrar_sin_entregar' => (string) ($entrada['mostrar_sin_entregar'] ?? '') === '1',
            'estados_presentacion' => self::normalizarEstados(is_array($estados) ? $estados : self::estadosPresentacion()),
            'estado_bautismo' => self::normalizarBautismo((string) ($entrada['estado_bautismo'] ?? 'todos')),
        ];
    }

    /**
     * @param  array<string, mixed>  $entrada
     * @return array<string, mixed>
     */
    public static function generar(string $rol, array $entrada): array
    {
        $filtros = self::filtros($entrada);
        $seccion = $filtros['seccion'];

        if (! self::puede($rol, $seccion)) {
            throw new InvalidArgumentException('No tienes permiso para generar este informe.');
        }

        if ($seccion !== 'ofrendas') {
            $filtros['mostrar_sin_entregar'] = false;
        }

        if ($seccion !== 'eventos') {
            $filtros['evento_id'] = 0;
        } elseif ($filtros['evento_id'] <= 0) {
            throw new InvalidArgumentException('Selecciona un evento para generar el informe.');
        } else {
            $evento = Evento::query()->find($filtros['evento_id']);

            if (! $evento || ! $evento->habilitado) {
                throw new InvalidArgumentException('Selecciona un evento habilitado.');
            }
        }

        $informe = match ($seccion) {
            'presentaciones' => self::presentaciones(
                $filtros['fecha_desde'],
                $filtros['fecha_hasta'],
                $filtros['turno'],
                $filtros['estados_presentacion'],
            ),
            'bautismos' => self::bautismos(
                $filtros['fecha_desde'],
                $filtros['fecha_hasta'],
                $filtros['turno'],
                $filtros['estado_bautismo'],
            ),
            'eventos' => self::evento(
                $filtros['evento_id'],
                $filtros['fecha_desde'],
                $filtros['fecha_hasta'],
                $filtros['turno'],
            ),
            default => self::financiero(
                $filtros['fecha_desde'],
                $filtros['fecha_hasta'],
                $filtros['mostrar_sin_entregar'],
                $filtros['turno'],
            ),
        };

        $informe['seccion_exportacion'] = $seccion;

        return $informe;
    }

    /**
     * @return array<string, mixed>
     */
    public static function evento(int $eventoId, string $desde = '', string $hasta = '', string $turno = 'todos'): array
    {
        $evento = Evento::query()->with(['tiposEntrada', 'camposAdicionales'])->find($eventoId);

        if (! $evento) {
            throw new InvalidArgumentException('Evento no encontrado.');
        }

        $rango = self::rango($desde, $hasta);
        $turno = self::normalizarTurno($turno);
        $registros = self::prepararRegistros(self::registrosEvento($rango['desde'], $rango['hasta'], $turno, $eventoId));
        $eventoDatos = self::datosEvento($evento);
        $resumenTipos = self::resumenTipos($registros, $eventoDatos);
        $grupos = self::agruparPorTipo($registros, $eventoDatos);
        $columnas = self::columnasAdicionales($eventoDatos, $registros);
        $financiero = self::resumenFinanciero($registros);

        foreach ($grupos as $indiceGrupo => $grupo) {
            foreach ($grupo['registros'] as $indice => $registro) {
                $grupos[$indiceGrupo]['registros'][$indice]['datos_adicionales'] = array_map(
                    fn (array $columna) => self::datoAdicional($registro['info_adicional'] ?? '', $columna),
                    $columnas,
                );
            }
        }

        return self::base($rango, $turno, [
            'evento' => $eventoDatos,
            'registros' => $registros,
            'registros_por_tipo' => $grupos,
            'columnas_adicionales' => $columnas,
            'evento_id' => $eventoId,
            'evento_etiqueta' => (string) $evento->nombre,
            'evento_fecha_etiqueta' => self::fecha($evento->fecha?->toDateString()),
            'evento_numeracion_etiqueta' => $evento->requiere_numeracion ? 'Sí' : 'No',
            'evento_estado_etiqueta' => $evento->habilitado ? 'Habilitado' : 'Deshabilitado',
            'seccion_exportacion' => 'eventos',
            'resumen' => [
                'total_participantes' => count($registros),
                'total_entradas' => array_sum(array_column($resumenTipos, 'cantidad')),
                'monto_por_cancelar' => $financiero['monto_por_cancelar'],
                'monto_recaudado' => $financiero['monto_recaudado'],
                'entradas_por_cancelar' => $financiero['entradas_por_cancelar'],
                'entradas_recaudadas' => $financiero['entradas_recaudadas'],
                'monto_total' => $financiero['monto_total'],
                'por_tipo_entrada' => $resumenTipos,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $informe
     */
    public static function descargar(array $informe): Response
    {
        $seccion = self::normalizarSeccion((string) ($informe['seccion_exportacion'] ?? 'completo'));

        if (ini_get('memory_limit') !== '-1') {
            ini_set('memory_limit', '1024M');
        }

        if (function_exists('set_time_limit')) {
            set_time_limit(300);
        }

        $opciones = new Options;
        $opciones->set('isHtml5ParserEnabled', false);
        $opciones->set('isRemoteEnabled', false);
        $opciones->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($opciones);
        $dompdf->loadHtml(view('informes.pdf', ['informe' => $informe])->render());
        $dompdf->setPaper('A4', $seccion === 'eventos' ? 'landscape' : 'portrait');
        $dompdf->render();

        $nombre = self::nombreArchivo($informe, $seccion);

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$nombre.'"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    /**
     * @param  array<string, mixed>  $informe
     */
    public static function excel(array $informe): Response
    {
        $seccion = self::normalizarSeccion((string) ($informe['seccion_exportacion'] ?? 'completo'));
        $html = '<html><head><meta charset="UTF-8"></head><body>';
        $html .= '<h2>'.e(self::titulo($seccion)).'</h2>';
        $html .= '<p>'.e(self::encabezadoExcel($informe, $seccion)).'</p>';
        $html .= match ($seccion) {
            'ofrendas' => self::excelOfrendas($informe, true),
            'valores' => self::excelValores($informe),
            'eventos' => self::excelEvento($informe),
            'presentaciones' => self::excelPresentaciones($informe),
            'bautismos' => self::excelBautismos($informe),
            default => self::excelOfrendas($informe, true).self::excelEventosGenerales($informe).self::excelValores($informe),
        };
        $html .= '</body></html>';

        return response("\xEF\xBB\xBF".$html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.self::nombreArchivo($informe, $seccion, 'xls').'"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public static function monto(mixed $monto): string
    {
        return '$'.number_format((float) $monto, 2, '.', ',');
    }

    public static function fecha(mixed $fecha): string
    {
        return self::formatearFecha($fecha, 'd/m/Y');
    }

    public static function fechaHora(mixed $fecha): string
    {
        return self::formatearFecha($fecha, 'd/m/Y H:i');
    }

    public static function texto(mixed $valor): string
    {
        $texto = trim((string) ($valor ?? ''));

        return $texto !== '' ? $texto : '—';
    }

    public static function edad(mixed $fecha): string
    {
        $nacimiento = self::dia($fecha);

        if (! $nacimiento || $nacimiento->isFuture()) {
            return '—';
        }

        $hoy = now()->startOfDay();
        $anios = (int) $nacimiento->diff($hoy)->y;

        if ($anios > 0) {
            return $anios.' año'.($anios === 1 ? '' : 's');
        }

        $meses = ((int) $hoy->format('Y') - (int) $nacimiento->format('Y')) * 12
            + ((int) $hoy->format('m') - (int) $nacimiento->format('m'));

        if ((int) $hoy->format('d') < (int) $nacimiento->format('d')) {
            $meses--;
        }

        $meses = max(0, $meses);

        return $meses.' mes'.($meses === 1 ? '' : 'es');
    }

    /**
     * @param  array<string, mixed>  $fila
     */
    public static function representante(array $fila, int $numero): string
    {
        if ($numero === 1) {
            $parentesco = trim((string) ($fila['parentesco_representante_1'] ?? ''));
            $nombre = trim((string) ($fila['nombre_padre'] ?? ''));
        } else {
            $parentesco = trim((string) ($fila['parentesco_representante_2'] ?? ''));
            $nombre = trim((string) ($fila['nombre_madre'] ?? ''));
        }

        if ($nombre === '') {
            return '—';
        }

        $etiqueta = $parentesco === ''
            ? 'Representante'
            : (Permisos::parentescos()[$parentesco] ?? $parentesco);

        return $etiqueta.': '.$nombre;
    }

    /**
     * @param  array<string, mixed>  $fila
     */
    public static function tieneSegundoRepresentante(array $fila): bool
    {
        return trim((string) ($fila['nombre_madre'] ?? '')) !== '';
    }

    public static function etiquetaTipoValor(string $tipo): string
    {
        $tipos = self::tiposValor();

        if (array_key_exists($tipo, $tipos)) {
            return $tipos[$tipo];
        }

        return $tipo === Eventos::TIPO ? 'Eventos' : ($tipo !== '' ? $tipo : '—');
    }

    /**
     * @param  array<string, mixed>  $registro
     */
    public static function etiquetaPago(array $registro): string
    {
        if (! empty($registro['entrada_gratis'])) {
            return 'Completado';
        }

        $estado = trim((string) ($registro['estado_pago'] ?? ''));

        if ($estado === '') {
            return '—';
        }

        $normalizado = self::estadoPago($estado);

        return Eventos::estadosPago()[$normalizado] ?? $estado;
    }

    public static function etiquetaForma(mixed $forma): string
    {
        $original = trim((string) $forma);

        if ($original === '') {
            return '—';
        }

        $clave = mb_strtolower($original);

        return Eventos::formasPago()[$clave] ?? $original;
    }

    public static function etiquetaBautismo(string $estado): string
    {
        return match ($estado) {
            'ingresado' => 'Ingresado',
            'bautizado' => 'Bautizado',
            default => $estado !== '' ? $estado : '—',
        };
    }

    /**
     * @param  array{id?: int, etiqueta?: string}  $campo
     */
    public static function datoAdicional(mixed $info, array $campo): string
    {
        $respuestas = self::decodificarInfo($info);
        $campoId = (int) ($campo['id'] ?? 0);
        $etiqueta = trim((string) ($campo['etiqueta'] ?? ''));

        foreach ($respuestas as $item) {
            if ($campoId > 0 && (int) ($item['id'] ?? 0) === $campoId) {
                return $item['valor'] !== '' ? $item['valor'] : '—';
            }
        }

        if ($etiqueta !== '') {
            foreach ($respuestas as $item) {
                if (strcasecmp((string) ($item['etiqueta'] ?? ''), $etiqueta) === 0) {
                    return $item['valor'] !== '' ? $item['valor'] : '—';
                }
            }
        }

        return '—';
    }

    /**
     * @param  array<string, mixed>  $rango
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private static function base(array $rango, string $turno, array $datos): array
    {
        return array_merge([
            'fecha_desde' => $rango['desde'] ?? 'todo',
            'fecha_hasta' => $rango['hasta'] ?? 'todo',
            'fecha_desde_etiqueta' => $rango['fecha_desde_etiqueta'],
            'fecha_hasta_etiqueta' => $rango['fecha_hasta_etiqueta'],
            'periodo_etiqueta' => $rango['periodo_etiqueta'],
            'sin_filtro_fecha' => $rango['sin_filtro_fecha'],
            'turno' => $turno,
            'turno_etiqueta' => self::turnos()[$turno] ?? 'Todas',
            'generado_en' => now()->format('d/m/Y H:i'),
        ], $datos);
    }

    /**
     * @return array{desde: ?string, hasta: ?string, fecha_desde_etiqueta: string, fecha_hasta_etiqueta: string, periodo_etiqueta: string, sin_filtro_fecha: bool}
     */
    private static function rango(string $desde, string $hasta): array
    {
        $desde = trim($desde);
        $hasta = trim($hasta);

        if ($desde === '' && $hasta === '') {
            return [
                'desde' => null,
                'hasta' => null,
                'fecha_desde_etiqueta' => '—',
                'fecha_hasta_etiqueta' => '—',
                'periodo_etiqueta' => 'Todos los registros',
                'sin_filtro_fecha' => true,
            ];
        }

        if ($desde === '' || $hasta === '') {
            throw new InvalidArgumentException('Indica ambas fechas (desde y hasta) o déjalas vacías para descargar todo.');
        }

        $inicio = \DateTime::createFromFormat('Y-m-d', $desde);
        $fin = \DateTime::createFromFormat('Y-m-d', $hasta);

        if (! $inicio || ! $fin || $inicio->format('Y-m-d') !== $desde || $fin->format('Y-m-d') !== $hasta) {
            throw new InvalidArgumentException('Las fechas no son válidas.');
        }

        if ($inicio > $fin) {
            throw new InvalidArgumentException('La fecha desde no puede ser posterior a la fecha hasta.');
        }

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'fecha_desde_etiqueta' => self::fecha($desde),
            'fecha_hasta_etiqueta' => self::fecha($hasta),
            'periodo_etiqueta' => self::fecha($desde).' al '.self::fecha($hasta),
            'sin_filtro_fecha' => false,
        ];
    }

    /**
     * @param  array<int, string>  $estados
     * @return array<string, mixed>
     */
    private static function presentaciones(string $desde, string $hasta, string $turno, array $estados): array
    {
        $rango = self::rango($desde, $hasta);
        $turno = self::normalizarTurno($turno);
        $estados = self::normalizarEstados($estados);

        if ($estados === []) {
            throw new InvalidArgumentException('Selecciona al menos un estado de presentación.');
        }

        $consulta = DB::table('presentaciones_ninos')->whereIn('estado', $estados)->orderByDesc('creado_en')->orderByDesc('id');
        self::aplicarPeriodo($consulta, $rango['desde'], $rango['hasta'], $turno, 'creado_en');
        $filas = self::filas($consulta);

        foreach ($filas as $indice => $fila) {
            $filas[$indice]['edad_etiqueta'] = self::edad($fila['fecha_nacimiento'] ?? null);
        }

        return self::base($rango, $turno, [
            'estados_presentacion' => $estados,
            'estados_presentacion_etiqueta' => self::etiquetasDeEstados($estados),
            'seccion_exportacion' => 'presentaciones',
            'resumen' => ['cantidad_presentaciones' => count($filas)],
            'presentaciones' => $filas,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function bautismos(string $desde, string $hasta, string $turno, string $estado): array
    {
        $rango = self::rango($desde, $hasta);
        $turno = self::normalizarTurno($turno);
        $estado = self::normalizarBautismo($estado);
        $consulta = DB::table('inscripciones')
            ->where('tipo_formulario', 'bautismo')
            ->orderByDesc('creado_en')
            ->orderByDesc('id');

        if ($estado !== 'todos') {
            $consulta->where('estado_bautismo', $estado);
        }

        self::aplicarPeriodo($consulta, $rango['desde'], $rango['hasta'], $turno, 'creado_en');
        $filas = self::filas($consulta);

        return self::base($rango, $turno, [
            'estado_bautismo' => $estado,
            'estado_bautismo_etiqueta' => self::estadosBautismo()[$estado] ?? 'Todos',
            'seccion_exportacion' => 'bautismos',
            'resumen' => ['cantidad_bautismos' => count($filas)],
            'bautismos' => $filas,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function financiero(string $desde, string $hasta, bool $mostrarSinEntregar, string $turno): array
    {
        $rango = self::rango($desde, $hasta);
        $turno = self::normalizarTurno($turno);
        $casas = self::casas();
        $ofrendas = self::ofrendas($rango['desde'], $rango['hasta'], $turno);
        $registrosEventos = self::prepararRegistros(self::registrosEvento($rango['desde'], $rango['hasta'], $turno, 0));
        $valores = self::valores($rango['desde'], $rango['hasta'], $turno);
        $territorioPorCasa = [];

        foreach ($casas as $casa) {
            $territorioPorCasa[(int) $casa['id']] = (string) ($casa['territorio_nombre'] ?? '');
        }

        foreach ($ofrendas as $indice => $ofrenda) {
            $casaId = (int) ($ofrenda['casa_id'] ?? 0);
            $ofrendas[$indice]['territorio'] = $casaId > 0 ? ($territorioPorCasa[$casaId] ?? '') : '';
        }

        $porCasa = [];
        $porNombre = [];

        foreach ($ofrendas as $ofrenda) {
            $casaId = (int) ($ofrenda['casa_id'] ?? 0);

            if ($casaId > 0) {
                $porCasa[$casaId][] = $ofrenda;

                continue;
            }

            $nombre = mb_strtolower(trim((string) ($ofrenda['casa_vida'] ?? '')));

            if ($nombre !== '') {
                $porNombre[$nombre][] = $ofrenda;
            }
        }

        $dieron = [];
        $noDieron = [];
        $totalOfrendas = 0.0;

        foreach ($casas as $casa) {
            $casaId = (int) $casa['id'];
            $nombre = mb_strtolower(trim((string) $casa['nombre']));
            $deLaCasa = $porCasa[$casaId] ?? [];

            if ($deLaCasa === [] && isset($porNombre[$nombre])) {
                $deLaCasa = $porNombre[$nombre];
            }

            $monto = 0.0;

            foreach ($deLaCasa as $ofrenda) {
                $monto += (float) $ofrenda['monto'];
            }

            $fila = [
                'id' => $casaId,
                'nombre' => $casa['nombre'],
                'territorio' => $casa['territorio_nombre'] ?? '',
                'lider' => trim(($casa['lider_nombre'] ?? '').' '.($casa['lider_apellido'] ?? '')),
                'total_monto' => $monto,
                'cantidad_ofrendas' => count($deLaCasa),
            ];

            if ($deLaCasa !== []) {
                $dieron[] = $fila;
                $totalOfrendas += $monto;
            } else {
                $noDieron[] = $fila;
            }
        }

        usort($dieron, fn (array $a, array $b) => strcasecmp((string) $a['nombre'], (string) $b['nombre']));
        usort($noDieron, fn (array $a, array $b) => strcasecmp((string) $a['nombre'], (string) $b['nombre']));

        $totalEventos = 0.0;
        $totalValores = 0.0;

        foreach ($registrosEventos as $registro) {
            $totalEventos += (float) $registro['valor'];
        }

        foreach ($valores as $valor) {
            $totalValores += (float) $valor['valor'];
        }

        return self::base($rango, $turno, [
            'mostrar_sin_entregar' => $mostrarSinEntregar,
            'seccion_exportacion' => 'completo',
            'resumen' => [
                'total_casas' => count($casas),
                'casas_dieron' => count($dieron),
                'casas_no_dieron' => count($noDieron),
                'cantidad_registros_ofrendas' => count($ofrendas),
                'total_monto_ofrendas' => $totalOfrendas,
                'cantidad_registros_eventos' => count($registrosEventos),
                'total_monto_eventos' => $totalEventos,
                'cantidad_valores_adicionales' => count($valores),
                'total_monto_valores' => $totalValores,
                'total_general' => $totalOfrendas + $totalEventos + $totalValores,
            ],
            'casas_no_dieron' => $noDieron,
            'ofrendas_por_fecha' => self::agruparOfrendas($ofrendas),
            'registros_eventos' => $registrosEventos,
            'valores_adicionales' => $valores,
        ]);
    }

    private static function aplicarPeriodo(Builder $consulta, ?string $desde, ?string $hasta, string $turno, string $columna): void
    {
        if (! in_array($columna, ['creado_en', 'v.creado_en'], true)) {
            throw new InvalidArgumentException('Columna de fecha no válida.');
        }

        if ($desde !== null && $hasta !== null) {
            $consulta->where($columna, '>=', $desde.' 00:00:00')->where($columna, '<=', $hasta.' 23:59:59');
        }

        $turno = self::normalizarTurno($turno);

        if ($turno === 'manana') {
            $consulta->whereRaw('TIME('.$columna.') >= ? AND TIME('.$columna.') <= ?', ['00:01:00', '15:59:59']);
        } elseif ($turno === 'tarde') {
            $consulta->whereRaw('TIME('.$columna.') >= ? AND TIME('.$columna.') <= ?', ['16:00:00', '23:59:59']);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function ofrendas(?string $desde, ?string $hasta, string $turno): array
    {
        $consulta = DB::table('ofrendas')->orderBy('fecha_ofrenda')->orderBy('casa_vida')->orderBy('id');
        self::aplicarPeriodo($consulta, $desde, $hasta, $turno, 'creado_en');

        return self::filas($consulta);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function valores(?string $desde, ?string $hasta, string $turno): array
    {
        $consulta = DB::table('valores_adicionales as v')
            ->where('v.tipo', '!=', Eventos::TIPO)
            ->orderByDesc('v.creado_en')
            ->orderByDesc('v.id')
            ->select('v.*');
        self::aplicarPeriodo($consulta, $desde, $hasta, $turno, 'v.creado_en');

        return self::filas($consulta);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function registrosEvento(?string $desde, ?string $hasta, string $turno, int $eventoId): array
    {
        $consulta = DB::table('valores_adicionales as v')
            ->leftJoin('eventos as e', 'e.id', '=', 'v.evento_id')
            ->where('v.tipo', Eventos::TIPO)
            ->orderByDesc('v.creado_en')
            ->orderByDesc('v.id')
            ->select('v.*', 'e.nombre as evento_nombre');

        if ($eventoId > 0) {
            $consulta->where('v.evento_id', $eventoId);
        }

        self::aplicarPeriodo($consulta, $desde, $hasta, $turno, 'v.creado_en');

        return self::filas($consulta);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function casas(): array
    {
        return self::filas(DB::table('casas_vida as c')
            ->join('territorios as t', 't.id', '=', 'c.territorio_id')
            ->join('lideres as l', 'l.id', '=', 'c.lider_id')
            ->select('c.*', 't.nombre as territorio_nombre', 'l.nombre as lider_nombre', 'l.apellido as lider_apellido')
            ->orderByDesc('c.creado_en')
            ->orderByDesc('c.id'));
    }

    /**
     * @param  array<int, array<string, mixed>>  $ofrendas
     * @return array<int, array<string, mixed>>
     */
    private static function agruparOfrendas(array $ofrendas): array
    {
        $grupos = [];

        foreach ($ofrendas as $ofrenda) {
            $fecha = substr((string) ($ofrenda['fecha_ofrenda'] ?? ''), 0, 10);

            if ($fecha === '') {
                continue;
            }

            if (! isset($grupos[$fecha])) {
                $grupos[$fecha] = [
                    'fecha' => $fecha,
                    'fecha_etiqueta' => self::fechaLarga($fecha),
                    'ofrendas' => [],
                ];
            }

            $grupos[$fecha]['ofrendas'][] = $ofrenda;
        }

        ksort($grupos);

        return array_values($grupos);
    }

    /**
     * @param  array<int, array<string, mixed>>  $registros
     * @return array<int, array<string, mixed>>
     */
    private static function prepararRegistros(array $registros): array
    {
        $gratis = self::tiposGratis($registros);

        foreach ($registros as $indice => $registro) {
            $registros[$indice]['entrada_gratis'] = self::esGratis($registro, $gratis) ? 1 : 0;
        }

        return $registros;
    }

    /**
     * @param  array<int, array<string, mixed>>  $registros
     * @return array<int, int>
     */
    private static function tiposGratis(array $registros): array
    {
        $ids = [];

        foreach ($registros as $registro) {
            $id = (int) ($registro['tipo_entrada_id'] ?? 0);

            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        if ($ids === []) {
            return [];
        }

        return DB::table('eventos_tipos_entrada')
            ->whereIn('id', array_values($ids))
            ->pluck('es_gratis', 'id')
            ->map(fn ($gratis) => (int) $gratis)
            ->all();
    }

    /**
     * @param  array<string, mixed>  $registro
     * @param  array<int, int>  $tiposGratis
     */
    private static function esGratis(array $registro, array $tiposGratis): bool
    {
        if (self::forma((string) ($registro['forma_pago'] ?? '')) === 'gratuito') {
            return true;
        }

        $tipoId = (int) ($registro['tipo_entrada_id'] ?? 0);

        return $tipoId > 0 && (($tiposGratis[$tipoId] ?? 0) === 1);
    }

    /**
     * @param  array<int, array<string, mixed>>  $registros
     * @return array{monto_por_cancelar: float, monto_recaudado: float, monto_total: float, entradas_por_cancelar: int, entradas_recaudadas: int}
     */
    private static function resumenFinanciero(array $registros): array
    {
        $porCancelar = 0.0;
        $recaudado = 0.0;
        $entradasPorCancelar = 0;
        $entradasRecaudadas = 0;

        foreach ($registros as $registro) {
            if (! empty($registro['entrada_gratis'])) {
                continue;
            }

            $valor = (float) ($registro['valor'] ?? 0);
            $estado = self::estadoPago((string) ($registro['estado_pago'] ?? '')) ?: 'por_cancelar';
            $forma = self::forma((string) ($registro['forma_pago'] ?? ''));

            if ($estado === 'pagado' && in_array($forma, ['efectivo', 'transferencia'], true)) {
                $recaudado += $valor;
                $entradasRecaudadas++;
            } else {
                $porCancelar += $valor;
                $entradasPorCancelar++;
            }
        }

        return [
            'monto_por_cancelar' => $porCancelar,
            'monto_recaudado' => $recaudado,
            'monto_total' => $porCancelar + $recaudado,
            'entradas_por_cancelar' => $entradasPorCancelar,
            'entradas_recaudadas' => $entradasRecaudadas,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function datosEvento(Evento $evento): array
    {
        return [
            'id' => $evento->id,
            'nombre' => (string) $evento->nombre,
            'fecha' => $evento->fecha?->toDateString(),
            'valor' => (float) $evento->valor,
            'habilitado' => $evento->habilitado ? 1 : 0,
            'requiere_numeracion' => $evento->requiere_numeracion ? 1 : 0,
            'tipos_entrada' => $evento->tiposEntrada->map(fn ($tipo) => [
                'id' => (int) $tipo->id,
                'nombre' => (string) $tipo->nombre,
                'valor' => (float) $tipo->valor,
                'es_gratis' => $tipo->es_gratis ? 1 : 0,
            ])->all(),
            'campos_adicionales' => $evento->camposAdicionales->map(fn ($campo) => [
                'id' => (int) $campo->id,
                'etiqueta' => (string) $campo->etiqueta,
            ])->all(),
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $registros
     * @param  array<string, mixed>  $evento
     * @return array<int, array{nombre: string, cantidad: int, valor_catalogo: string}>
     */
    private static function resumenTipos(array $registros, array $evento): array
    {
        $resumen = [];
        $idAClave = [];

        foreach ($evento['tipos_entrada'] ?? [] as $tipo) {
            $tipoId = (int) ($tipo['id'] ?? 0);
            $nombre = trim((string) ($tipo['nombre'] ?? ''));
            $clave = self::claveTipo($nombre, $tipoId);

            if (! isset($resumen[$clave])) {
                $resumen[$clave] = [
                    'nombre' => $nombre !== '' ? $nombre : 'Sin nombre',
                    'cantidad' => 0,
                    'valor_catalogo' => self::valorCatalogo($tipo),
                ];
            }

            if ($tipoId > 0) {
                $idAClave[$tipoId] = $clave;
            }
        }

        foreach ($registros as $registro) {
            $tipoId = (int) ($registro['tipo_entrada_id'] ?? 0);
            $nombre = trim((string) ($registro['tipo_entrada'] ?? ''));
            $clave = $tipoId > 0 && isset($idAClave[$tipoId]) ? $idAClave[$tipoId] : self::claveTipo($nombre, $tipoId);

            if (! isset($resumen[$clave])) {
                $resumen[$clave] = [
                    'nombre' => $nombre !== '' ? $nombre : 'Sin tipo',
                    'cantidad' => 0,
                    'valor_catalogo' => '—',
                ];
            }

            $resumen[$clave]['cantidad']++;
        }

        return array_values($resumen);
    }

    /**
     * @param  array<int, array<string, mixed>>  $registros
     * @param  array<string, mixed>  $evento
     * @return array<int, array{tipo_entrada: string, registros: array<int, array<string, mixed>>}>
     */
    private static function agruparPorTipo(array $registros, array $evento): array
    {
        $grupos = [];
        $idAClave = [];

        foreach ($evento['tipos_entrada'] ?? [] as $tipo) {
            $tipoId = (int) ($tipo['id'] ?? 0);
            $nombre = trim((string) ($tipo['nombre'] ?? ''));
            $clave = self::claveTipo($nombre, $tipoId);

            if (! isset($grupos[$clave])) {
                $grupos[$clave] = [
                    'tipo_entrada' => $nombre !== '' ? $nombre : 'Sin nombre',
                    'registros' => [],
                ];
            }

            if ($tipoId > 0) {
                $idAClave[$tipoId] = $clave;
            }
        }

        $sinTipo = [
            'tipo_entrada' => 'Sin tipo de entrada',
            'registros' => [],
        ];

        foreach ($registros as $registro) {
            $tipoId = (int) ($registro['tipo_entrada_id'] ?? 0);
            $nombre = trim((string) ($registro['tipo_entrada'] ?? ''));

            if ($tipoId > 0 && isset($idAClave[$tipoId])) {
                $grupos[$idAClave[$tipoId]]['registros'][] = $registro;

                continue;
            }

            $clave = self::claveTipo($nombre, $tipoId);

            if ($nombre !== '' && isset($grupos[$clave])) {
                $grupos[$clave]['registros'][] = $registro;

                continue;
            }

            if ($nombre !== '') {
                $grupos[$clave] ??= [
                    'tipo_entrada' => $nombre,
                    'registros' => [],
                ];
                $grupos[$clave]['registros'][] = $registro;

                continue;
            }

            $sinTipo['registros'][] = $registro;
        }

        $resultado = [];

        foreach ($grupos as $grupo) {
            if ($grupo['registros'] === []) {
                continue;
            }

            usort($grupo['registros'], [self::class, 'compararNumeracion']);
            $resultado[] = $grupo;
        }

        if ($sinTipo['registros'] !== []) {
            usort($sinTipo['registros'], [self::class, 'compararNumeracion']);
            $resultado[] = $sinTipo;
        }

        return $resultado;
    }

    /**
     * @param  array<string, mixed>  $evento
     * @param  array<int, array<string, mixed>>  $registros
     * @return array<int, array{id: int, etiqueta: string}>
     */
    private static function columnasAdicionales(array $evento, array $registros): array
    {
        $vistos = [];
        $columnas = [];

        foreach ($evento['campos_adicionales'] ?? [] as $campo) {
            $etiqueta = trim((string) ($campo['etiqueta'] ?? ''));

            if ($etiqueta === '') {
                continue;
            }

            $vistos[mb_strtolower($etiqueta)] = true;
            $columnas[] = ['id' => (int) ($campo['id'] ?? 0), 'etiqueta' => $etiqueta];
        }

        if ($columnas !== []) {
            return $columnas;
        }

        foreach ($registros as $registro) {
            foreach (self::decodificarInfo($registro['info_adicional'] ?? '') as $item) {
                $etiqueta = $item['etiqueta'] !== '' ? $item['etiqueta'] : 'Dato';
                $clave = mb_strtolower($etiqueta);

                if (isset($vistos[$clave])) {
                    continue;
                }

                $vistos[$clave] = true;
                $columnas[] = ['id' => (int) ($item['id'] ?? 0), 'etiqueta' => $etiqueta];
            }
        }

        return $columnas;
    }

    /**
     * @param  array<string, mixed>  $tipo
     */
    private static function valorCatalogo(array $tipo): string
    {
        if ((int) ($tipo['es_gratis'] ?? 0) === 1) {
            return 'Gratuito';
        }

        if ((float) ($tipo['valor'] ?? 0) <= 0) {
            return 'Pendiente de pago';
        }

        return self::monto($tipo['valor'] ?? 0);
    }

    private static function claveTipo(string $nombre, int $tipoId): string
    {
        $nombre = mb_strtolower(trim($nombre));

        return $nombre !== '' ? 'n:'.$nombre : 'id:'.$tipoId;
    }

    /**
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     */
    private static function compararNumeracion(array $a, array $b): int
    {
        $numeracionA = trim((string) ($a['numeracion'] ?? ''));
        $numeracionB = trim((string) ($b['numeracion'] ?? ''));

        if ($numeracionA === '' && $numeracionB === '') {
            return strcasecmp((string) ($a['nombre'] ?? ''), (string) ($b['nombre'] ?? ''));
        }

        if ($numeracionA === '') {
            return 1;
        }

        if ($numeracionB === '') {
            return -1;
        }

        $comparacion = strnatcasecmp($numeracionA, $numeracionB);

        return $comparacion !== 0
            ? $comparacion
            : strcasecmp((string) ($a['nombre'] ?? ''), (string) ($b['nombre'] ?? ''));
    }

    /**
     * @return array<int, array{id: int, etiqueta: string, valor: string}>
     */
    private static function decodificarInfo(mixed $info): array
    {
        if (is_array($info)) {
            $decodificado = $info;
        } else {
            $crudo = trim((string) $info);

            if ($crudo === '') {
                return [];
            }

            $decodificado = json_decode($crudo, true);

            if (! is_array($decodificado)) {
                return [];
            }
        }

        $resultado = [];

        foreach ($decodificado as $item) {
            if (! is_array($item)) {
                continue;
            }

            $etiqueta = trim((string) ($item['etiqueta'] ?? ''));
            $valor = trim((string) ($item['valor'] ?? ''));

            if ($etiqueta === '' && $valor === '') {
                continue;
            }

            $resultado[] = [
                'id' => (int) ($item['id'] ?? 0),
                'etiqueta' => $etiqueta,
                'valor' => $valor,
            ];
        }

        return $resultado;
    }

    /**
     * @param  array<int, mixed>  $estados
     * @return array<int, string>
     */
    private static function normalizarEstados(array $estados): array
    {
        $validos = self::estadosPresentacion();
        $filtrados = [];

        foreach ($estados as $estado) {
            $estado = trim((string) $estado);

            if ($estado !== '' && in_array($estado, $validos, true)) {
                $filtrados[] = $estado;
            }
        }

        return array_values(array_unique($filtrados));
    }

    /**
     * @param  array<int, string>  $estados
     */
    private static function etiquetasDeEstados(array $estados): string
    {
        if ($estados === []) {
            return '—';
        }

        $etiquetas = self::etiquetasPresentacion();
        $partes = [];

        foreach ($estados as $estado) {
            $partes[] = $etiquetas[$estado] ?? $estado;
        }

        return implode(', ', $partes);
    }

    private static function normalizarBautismo(string $estado): string
    {
        $estado = trim(mb_strtolower($estado));

        return array_key_exists($estado, self::estadosBautismo()) ? $estado : 'todos';
    }

    private static function estadoPago(string $estado): string
    {
        $estado = trim(mb_strtolower($estado));

        return array_key_exists($estado, Eventos::estadosPago()) ? $estado : '';
    }

    private static function forma(string $forma): string
    {
        $forma = trim(mb_strtolower($forma));

        return array_key_exists($forma, Eventos::formasPago()) ? $forma : '';
    }

    /**
     * @return array<string, string>
     */
    private static function tiposValor(): array
    {
        try {
            $filas = DB::table('tipos_valor_adicional')->orderBy('etiqueta')->orderBy('id')->get(['clave', 'etiqueta']);
        } catch (\Throwable) {
            $filas = collect();
        }

        if ($filas->isEmpty()) {
            return [
                'casa_vida_relampago' => 'Casa de vida relampago',
                'ofrenda_personal' => 'Ofrenda Personal',
                'siembra' => 'Siembra',
                'otros' => 'Otros',
            ];
        }

        $tipos = [];

        foreach ($filas as $fila) {
            $tipos[(string) $fila->clave] = (string) $fila->etiqueta;
        }

        return $tipos;
    }

    /**
     * @param  array<string, mixed>  $informe
     */
    private static function nombreArchivo(array $informe, string $seccion, string $extension = 'pdf'): string
    {
        $extension = ltrim($extension, '.');

        if ($seccion === 'eventos') {
            $nombre = (string) ($informe['evento_etiqueta'] ?? 'evento');

            return 'reporte_'.self::slug($nombre).'.'.$extension;
        }

        $prefijo = $seccion === 'completo' ? 'informe' : 'informe-'.$seccion;

        return $prefijo.'-'.($informe['fecha_desde'] ?? 'todo').'-'.($informe['fecha_hasta'] ?? 'todo').'.'.$extension;
    }

    /**
     * @param  array<string, mixed>  $informe
     */
    private static function encabezadoExcel(array $informe, string $seccion): string
    {
        $partes = [
            'Periodo de registro: '.($informe['periodo_etiqueta'] ?? '—'),
            'Jornada: '.($informe['turno_etiqueta'] ?? 'Todas'),
        ];

        if ($seccion === 'eventos') {
            $partes[] = 'Evento: '.($informe['evento_etiqueta'] ?? '—');
        }

        if ($seccion === 'presentaciones') {
            $partes[] = 'Estados: '.($informe['estados_presentacion_etiqueta'] ?? '—');
        }

        if ($seccion === 'bautismos') {
            $partes[] = 'Estado: '.($informe['estado_bautismo_etiqueta'] ?? '—');
        }

        $partes[] = 'Generado: '.($informe['generado_en'] ?? '—');

        return implode(' · ', $partes);
    }

    /**
     * @param  array<string, mixed>  $informe
     */
    private static function excelOfrendas(array $informe, bool $incluirSinEntregar): string
    {
        $resumen = $informe['resumen'] ?? [];
        $html = '<h3>Ofrendas</h3>';
        $html .= self::tablaHtml(
            ['Casas de vida', 'Dieron ofrenda', 'Aún no dieron', 'Registros', 'Total'],
            [[
                (int) ($resumen['total_casas'] ?? 0),
                (int) ($resumen['casas_dieron'] ?? 0),
                (int) ($resumen['casas_no_dieron'] ?? 0),
                (int) ($resumen['cantidad_registros_ofrendas'] ?? 0),
                self::numero($resumen['total_monto_ofrendas'] ?? 0),
            ]],
        );

        $filas = [];

        foreach ($informe['ofrendas_por_fecha'] ?? [] as $grupo) {
            foreach ($grupo['ofrendas'] ?? [] as $ofrenda) {
                $filas[] = [
                    $ofrenda['id'] ?? '',
                    $ofrenda['casa_vida'] ?? '',
                    $ofrenda['territorio'] ?? '',
                    $ofrenda['lider'] ?? '',
                    self::fecha($ofrenda['fecha_ofrenda'] ?? null),
                    self::numero($ofrenda['monto'] ?? 0),
                    $ofrenda['registrado_por_nombre'] ?? '',
                    self::fechaHora($ofrenda['creado_en'] ?? null),
                ];
            }
        }

        $html .= '<h3>Detalle de ofrendas</h3>';
        $html .= self::tablaHtml(
            ['ID', 'Casa de vida', 'Territorio', 'Líder', 'Fecha de ofrenda', 'Monto', 'Registró', 'Registrado el'],
            $filas,
        );

        if (! $incluirSinEntregar) {
            return $html;
        }

        $sinEntregar = [];

        foreach ($informe['casas_no_dieron'] ?? [] as $casa) {
            $sinEntregar[] = [
                $casa['nombre'] ?? '',
                $casa['territorio'] ?? '',
                $casa['lider'] ?? '',
                'Sin ofrenda',
            ];
        }

        $html .= '<h3>Casas de vida que no entregaron</h3>';
        $html .= self::tablaHtml(['Casa de vida', 'Territorio', 'Líder', 'Estado'], $sinEntregar);

        return $html;
    }

    /**
     * @param  array<string, mixed>  $informe
     */
    private static function excelValores(array $informe): string
    {
        $filas = [];

        foreach ($informe['valores_adicionales'] ?? [] as $valor) {
            $filas[] = [
                $valor['id'] ?? '',
                self::etiquetaTipoValor((string) ($valor['tipo'] ?? '')),
                $valor['nombre'] ?? '',
                $valor['telefono'] ?? '',
                self::fecha($valor['fecha'] ?? null),
                self::numero($valor['valor'] ?? 0),
                $valor['observacion'] ?? '',
                $valor['registrado_por_nombre'] ?? '',
                self::fechaHora($valor['creado_en'] ?? null),
            ];
        }

        return '<h3>Valores adicionales</h3>'.self::tablaHtml(
            ['ID', 'Tipo', 'Nombre', 'Teléfono', 'Fecha', 'Valor', 'Observación', 'Registró', 'Registrado el'],
            $filas,
        );
    }

    /**
     * @param  array<string, mixed>  $informe
     */
    private static function excelEventosGenerales(array $informe): string
    {
        $resumen = $informe['resumen'] ?? [];
        $html = '<h3>Eventos</h3>';
        $html .= self::tablaHtml(
            ['Registros', 'Total'],
            [[
                (int) ($resumen['cantidad_registros_eventos'] ?? 0),
                self::numero($resumen['total_monto_eventos'] ?? 0),
            ]],
        );

        $filas = [];

        foreach ($informe['registros_eventos'] ?? [] as $registro) {
            $filas[] = self::filaEventoCompleta($registro, true);
        }

        return $html.self::tablaHtml(self::columnasEvento(true), $filas);
    }

    /**
     * @param  array<string, mixed>  $informe
     */
    private static function excelEvento(array $informe): string
    {
        $evento = $informe['evento'] ?? [];
        $resumen = $informe['resumen'] ?? [];
        $columnasExtra = $informe['columnas_adicionales'] ?? [];
        $html = '<h3>Datos del evento</h3>';
        $html .= self::tablaHtml(
            ['Evento', 'Fecha', 'Estado', 'Requiere numeración', 'Participantes', 'Por cancelar', 'Recaudado', 'Total'],
            [[
                $evento['nombre'] ?? ($informe['evento_etiqueta'] ?? ''),
                $informe['evento_fecha_etiqueta'] ?? '',
                $informe['evento_estado_etiqueta'] ?? '',
                $informe['evento_numeracion_etiqueta'] ?? '',
                (int) ($resumen['total_participantes'] ?? 0),
                self::numero($resumen['monto_por_cancelar'] ?? 0),
                self::numero($resumen['monto_recaudado'] ?? 0),
                self::numero($resumen['monto_total'] ?? 0),
            ]],
        );

        $tipos = [];

        foreach ($resumen['por_tipo_entrada'] ?? [] as $tipo) {
            $tipos[] = [
                $tipo['nombre'] ?? '',
                (int) ($tipo['cantidad'] ?? 0),
                $tipo['valor_catalogo'] ?? '',
            ];
        }

        $html .= '<h3>Entradas por tipo</h3>';
        $html .= self::tablaHtml(['Tipo de entrada', 'Cantidad', 'Valor catálogo'], $tipos);

        $filas = [];

        foreach ($informe['registros'] ?? [] as $registro) {
            $fila = self::filaEventoCompleta($registro, false, false);

            foreach ($columnasExtra as $columna) {
                $fila[] = self::datoAdicional($registro['info_adicional'] ?? '', $columna);
            }

            $filas[] = $fila;
        }

        $columnas = self::columnasEvento(false, false);

        foreach ($columnasExtra as $columna) {
            $columnas[] = (string) ($columna['etiqueta'] ?? 'Dato');
        }

        $html .= '<h3>Participantes</h3>';
        $html .= self::tablaHtml($columnas, $filas);

        return $html;
    }

    /**
     * @param  array<string, mixed>  $informe
     */
    private static function excelPresentaciones(array $informe): string
    {
        $filas = [];
        $parentescos = Permisos::parentescos();
        $estados = self::etiquetasPresentacion();

        foreach ($informe['presentaciones'] ?? [] as $fila) {
            $parentesco1 = trim((string) ($fila['parentesco_representante_1'] ?? ''));
            $parentesco2 = trim((string) ($fila['parentesco_representante_2'] ?? ''));
            $estado = (string) ($fila['estado'] ?? '');
            $filas[] = [
                $fila['id'] ?? '',
                $fila['nombre_presentado'] ?? '',
                self::fecha($fila['fecha_nacimiento'] ?? null),
                $fila['edad_etiqueta'] ?? self::edad($fila['fecha_nacimiento'] ?? null),
                $estados[$estado] ?? $estado,
                self::fecha($fila['fecha_presentacion'] ?? null),
                $parentesco1 === '' ? '' : ($parentescos[$parentesco1] ?? $parentesco1),
                $fila['nombre_padre'] ?? '',
                $fila['telefono_papa'] ?? '',
                $parentesco2 === '' ? '' : ($parentescos[$parentesco2] ?? $parentesco2),
                $fila['nombre_madre'] ?? '',
                $fila['telefono_mama'] ?? '',
                self::fechaHora($fila['creado_en'] ?? null),
                self::fechaHora($fila['actualizado_en'] ?? null),
            ];
        }

        return '<h3>Presentación de niños</h3>'.self::tablaHtml(
            [
                'ID', 'Nombre niño/a', 'Fecha de nacimiento', 'Edad', 'Estado', 'Fecha de presentación',
                'Parentesco representante 1', 'Nombre representante 1', 'Teléfono representante 1',
                'Parentesco representante 2', 'Nombre representante 2', 'Teléfono representante 2',
                'Registrado el', 'Actualizado el',
            ],
            $filas,
        );
    }

    /**
     * @param  array<string, mixed>  $informe
     */
    private static function excelBautismos(array $informe): string
    {
        $filas = [];
        $zonas = Permisos::zonas();

        foreach ($informe['bautismos'] ?? [] as $fila) {
            $zona = trim((string) ($fila['zona'] ?? ''));
            $filas[] = [
                $fila['id'] ?? '',
                $fila['nombre'] ?? '',
                $fila['apellido'] ?? '',
                $fila['celular'] ?? '',
                $fila['email'] ?? '',
                $zona === '' ? '' : ($zonas[$zona] ?? $zona),
                $fila['direccion'] ?? '',
                self::etiquetaBautismo((string) ($fila['estado_bautismo'] ?? 'ingresado')),
                self::fecha($fila['fecha_bautismo'] ?? null),
                (int) ($fila['contactado'] ?? 0) === 1 ? 'Sí' : 'No',
                self::fechaHora($fila['creado_en'] ?? null),
            ];
        }

        return '<h3>Personas bautizadas</h3>'.self::tablaHtml(
            ['ID', 'Nombre', 'Apellido', 'Teléfono', 'Email', 'Zona', 'Dirección', 'Estado', 'Fecha de bautizo', 'Contactado', 'Registrado el'],
            $filas,
        );
    }

    /**
     * @return array<int, string>
     */
    private static function columnasEvento(bool $general, bool $infoCombinada = true): array
    {
        $columnas = ['ID'];

        if ($general) {
            $columnas[] = 'Evento';
        }

        $columnas = array_merge($columnas, [
            'Nombre', 'Teléfono', 'Tipo de entrada', 'Numeración', 'Valor', 'Estado de pago', 'Forma de pago',
            'Fecha', 'Asistió', 'Asistió el', 'Marcó asistencia', 'Observación',
        ]);

        if ($infoCombinada) {
            $columnas[] = 'Información adicional';
        }

        return array_merge($columnas, ['Registró', 'Registrado el']);
    }

    /**
     * @param  array<string, mixed>  $registro
     * @return array<int, mixed>
     */
    private static function filaEventoCompleta(array $registro, bool $general, bool $infoCombinada = true): array
    {
        $fila = [$registro['id'] ?? ''];

        if ($general) {
            $fila[] = $registro['evento_nombre'] ?? '';
        }

        $fila = array_merge($fila, [
            $registro['nombre'] ?? '',
            $registro['telefono'] ?? '',
            $registro['tipo_entrada'] ?? '',
            $registro['numeracion'] ?? '',
            self::numero($registro['valor'] ?? 0),
            self::etiquetaPago($registro),
            self::etiquetaForma($registro['forma_pago'] ?? null),
            self::fecha($registro['fecha'] ?? null),
            (int) ($registro['asistio'] ?? 0) === 1 ? 'Sí' : 'No',
            self::fechaHora($registro['asistio_en'] ?? null),
            $registro['asistio_por_nombre'] ?? '',
            $registro['observacion'] ?? '',
        ]);

        if ($infoCombinada) {
            $fila[] = self::textoInfo($registro['info_adicional'] ?? '');
        }

        return array_merge($fila, [
            $registro['registrado_por_nombre'] ?? '',
            self::fechaHora($registro['creado_en'] ?? null),
        ]);
    }

    private static function textoInfo(mixed $info): string
    {
        $partes = [];

        foreach (self::decodificarInfo($info) as $item) {
            $etiqueta = $item['etiqueta'] !== '' ? $item['etiqueta'] : 'Dato';
            $valor = $item['valor'] !== '' ? $item['valor'] : '—';
            $partes[] = $etiqueta.': '.$valor;
        }

        return implode(' · ', $partes);
    }

    private static function numero(mixed $valor): string
    {
        return number_format((float) $valor, 2, '.', '');
    }

    /**
     * @param  array<int, string>  $columnas
     * @param  array<int, array<int, mixed>>  $filas
     */
    private static function tablaHtml(array $columnas, array $filas): string
    {
        $html = '<table border="1" cellpadding="4" cellspacing="0"><tr>';

        foreach ($columnas as $columna) {
            $html .= '<th>'.e($columna).'</th>';
        }

        $html .= '</tr>';

        if ($filas === []) {
            $html .= '<tr><td colspan="'.count($columnas).'">—</td></tr></table>';

            return $html;
        }

        foreach ($filas as $fila) {
            $html .= '<tr>';

            foreach ($fila as $valor) {
                $texto = trim((string) ($valor ?? ''));
                $html .= '<td>'.e($texto !== '' ? $texto : '—').'</td>';
            }

            $html .= '</tr>';
        }

        return $html.'</table>';
    }

    private static function slug(string $texto): string
    {
        $texto = strtolower(Str::ascii($texto));
        $texto = trim((string) preg_replace('/[^a-z0-9]+/', '_', $texto), '_');

        return $texto !== '' ? $texto : 'evento';
    }

    private static function fechaLarga(mixed $fecha): string
    {
        $dia = self::dia($fecha);

        if (! $dia) {
            return '—';
        }

        $meses = [
            1 => 'enero', 2 => 'febrero', 3 => 'marzo', 4 => 'abril',
            5 => 'mayo', 6 => 'junio', 7 => 'julio', 8 => 'agosto',
            9 => 'septiembre', 10 => 'octubre', 11 => 'noviembre', 12 => 'diciembre',
        ];

        return $dia->format('d').' '.ucfirst($meses[(int) $dia->format('n')] ?? $dia->format('m'));
    }

    private static function formatearFecha(mixed $fecha, string $formato): string
    {
        $dia = self::dia($fecha);

        return $dia ? $dia->format($formato) : ($fecha === null || $fecha === '' ? '—' : (string) $fecha);
    }

    private static function dia(mixed $fecha): ?Carbon
    {
        if ($fecha instanceof \DateTimeInterface) {
            return Carbon::instance(\DateTime::createFromInterface($fecha));
        }

        $texto = trim((string) $fecha);

        if ($texto === '') {
            return null;
        }

        try {
            return Carbon::parse($texto);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function filas(Builder $consulta): array
    {
        return array_map(fn ($fila) => (array) $fila, $consulta->get()->all());
    }
}
