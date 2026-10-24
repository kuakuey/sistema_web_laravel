<?php

namespace App\Support;

use App\Models\CalendarioEvento;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ApiCalendario
{
    /**
     * @return array<int, string>
     */
    public static function nombresMeses(): array
    {
        return [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];
    }

    /**
     * @return array{
     *   meses: array<int, array{anio: int, mes: int, etiqueta: string, eventos: array<int, array<string, mixed>>}>,
     *   total: int
     * }
     */
    public static function proximosEventos(): array
    {
        $hoy = Carbon::today();
        $mesesMeta = self::mesesProximos();
        $inicioVentana = sprintf('%04d-%02d-01', $mesesMeta[0]['anio'], $mesesMeta[0]['mes']);
        $ultimo = $mesesMeta[count($mesesMeta) - 1];
        $finVentana = Carbon::create($ultimo['anio'], $ultimo['mes'], 1)->endOfMonth()->toDateString();
        $hoyStr = $hoy->toDateString();

        $porMes = [];
        foreach ($mesesMeta as $meta) {
            $clave = sprintf('%04d-%02d', $meta['anio'], $meta['mes']);
            $porMes[$clave] = [
                'anio' => $meta['anio'],
                'mes' => $meta['mes'],
                'etiqueta' => $meta['etiqueta'],
                'eventos' => [],
            ];
        }

        $vistos = [];
        $total = 0;

        foreach (self::eventosActivos() as $evento) {
            $id = (int) $evento->id;
            if ($id > 0 && isset($vistos[$id])) {
                continue;
            }

            $fecha = optional($evento->fecha)?->toDateString() ?? '';
            $fechaFin = self::fechaFinEfectiva($evento);

            if ($fecha === '' || $fechaFin < $hoyStr) {
                continue;
            }

            if ($fecha > $finVentana || $fechaFin < $inicioVentana) {
                continue;
            }

            $claveMes = $fecha >= $inicioVentana
                ? substr($fecha, 0, 7)
                : substr($inicioVentana, 0, 7);

            if (! isset($porMes[$claveMes])) {
                continue;
            }

            $porMes[$claveMes]['eventos'][] = [
                'id' => $id,
                'titulo' => (string) $evento->titulo,
                'descripcion' => (string) ($evento->descripcion ?? ''),
                'fecha' => $fecha,
                'fecha_fin' => ($fechaFin !== $fecha) ? $fechaFin : null,
                'badge' => self::badgeFecha($fecha, $fechaFin !== $fecha ? $fechaFin : null),
                'activo' => 1,
            ];
            $vistos[$id] = true;
            $total++;
        }

        foreach ($porMes as &$mes) {
            usort($mes['eventos'], static function (array $a, array $b): int {
                return strcmp((string) $a['fecha'], (string) $b['fecha'])
                    ?: strcmp((string) $a['titulo'], (string) $b['titulo']);
            });
        }
        unset($mes);

        return [
            'meses' => array_values($porMes),
            'total' => $total,
        ];
    }

    public static function htmlTexto(): string
    {
        $datos = self::proximosEventos();
        $mesesConEventos = array_values(array_filter(
            $datos['meses'],
            static fn (array $mes): bool => $mes['eventos'] !== []
        ));

        return trim(view('api.calendario-texto', [
            'mesesConEventos' => $mesesConEventos,
        ])->render());
    }

    /**
     * @return array{
     *   anio: int,
     *   mes: int,
     *   etiqueta: string,
     *   imagenes: array<int, array{id: int, titulo: string, fecha: string, fecha_fin: ?string, url: string}>,
     *   total: int
     * }
     */
    public static function bannersMesActual(): array
    {
        $hoy = Carbon::today();
        $anio = (int) $hoy->year;
        $mes = (int) $hoy->month;
        $nombres = self::nombresMeses();
        $imagenes = [];
        $vistos = [];

        foreach (self::eventosActivos($anio, $mes) as $evento) {
            $id = (int) $evento->id;
            if ($id > 0 && isset($vistos[$id])) {
                continue;
            }

            $url = self::urlAbsolutaFoto($evento->foto ?? null);
            if ($url === '') {
                continue;
            }

            $fecha = optional($evento->fecha)?->toDateString() ?? '';
            $fechaFin = self::fechaFinEfectiva($evento);

            $imagenes[] = [
                'id' => $id,
                'titulo' => (string) $evento->titulo,
                'fecha' => $fecha,
                'fecha_fin' => ($fechaFin !== $fecha) ? $fechaFin : null,
                'url' => $url,
            ];
            $vistos[$id] = true;
        }

        usort($imagenes, static function (array $a, array $b): int {
            return strcmp((string) $a['fecha'], (string) $b['fecha'])
                ?: strcmp((string) $a['titulo'], (string) $b['titulo']);
        });

        return [
            'anio' => $anio,
            'mes' => $mes,
            'etiqueta' => $nombres[$mes] ?? $hoy->format('F'),
            'imagenes' => $imagenes,
            'total' => count($imagenes),
        ];
    }

    /**
     * @return array<int, array{anio: int, mes: int, etiqueta: string}>
     */
    private static function mesesProximos(): array
    {
        $nombres = self::nombresMeses();
        $hoy = Carbon::today()->startOfMonth();
        $meses = [];

        for ($i = 0; $i < 2; $i++) {
            $ref = $hoy->copy()->addMonths($i);
            $mes = (int) $ref->month;
            $meses[] = [
                'anio' => (int) $ref->year,
                'mes' => $mes,
                'etiqueta' => $nombres[$mes] ?? $ref->format('F'),
            ];
        }

        return $meses;
    }

    /**
     * @return Collection<int, CalendarioEvento>
     */
    private static function eventosActivos(?int $anio = null, ?int $mes = null): Collection
    {
        $consulta = CalendarioEvento::query()->where('activo', 1);

        if ($anio !== null && $mes !== null && $anio > 0 && $mes >= 1 && $mes <= 12) {
            $inicio = sprintf('%04d-%02d-01', $anio, $mes);
            $fin = Carbon::create($anio, $mes, 1)->endOfMonth()->toDateString();

            $consulta
                ->where('fecha', '<=', $fin)
                ->whereRaw('COALESCE(fecha_fin, fecha) >= ?', [$inicio])
                ->orderBy('fecha')
                ->orderBy('titulo')
                ->orderBy('id');
        } else {
            $consulta->orderByDesc('fecha')->orderBy('titulo')->orderBy('id');
        }

        return $consulta->get();
    }

    private static function fechaFinEfectiva(CalendarioEvento $evento): string
    {
        $fecha = optional($evento->fecha)?->toDateString() ?? '';
        $fechaFin = optional($evento->fecha_fin)?->toDateString() ?? '';

        if ($fechaFin !== '' && $fechaFin >= $fecha) {
            return $fechaFin;
        }

        return $fecha;
    }

    private static function badgeFecha(string $fecha, ?string $fechaFin = null): string
    {
        $fecha = trim($fecha);
        $fechaFin = trim((string) $fechaFin);

        if ($fecha === '') {
            return '';
        }

        try {
            $inicio = Carbon::createFromFormat('Y-m-d', $fecha);
        } catch (\Throwable) {
            return $fecha;
        }

        if ($fechaFin === '' || $fechaFin === $fecha) {
            return (string) (int) $inicio->format('j');
        }

        try {
            $fin = Carbon::createFromFormat('Y-m-d', $fechaFin);
        } catch (\Throwable) {
            return (string) (int) $inicio->format('j');
        }

        if ($fechaFin < $fecha) {
            return (string) (int) $inicio->format('j');
        }

        $cortos = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        ];

        $diaInicio = (int) $inicio->format('j');
        $diaFin = (int) $fin->format('j');

        if ($inicio->format('Y-m') === $fin->format('Y-m')) {
            return $diaInicio.'–'.$diaFin;
        }

        $mesInicio = $cortos[(int) $inicio->format('n')] ?? $inicio->format('M');
        $mesFin = $cortos[(int) $fin->format('n')] ?? $fin->format('M');

        return $diaInicio.' '.$mesInicio.'–'.$diaFin.' '.$mesFin;
    }

    private static function urlAbsolutaFoto(?string $rutaRelativa): string
    {
        $ruta = str_replace('\\', '/', trim((string) $rutaRelativa));

        if ($ruta === '' || str_contains($ruta, '..')) {
            return '';
        }

        if (preg_match('#^https?://#i', $ruta)) {
            return $ruta;
        }

        return rtrim((string) config('app.url'), '/').'/'.ltrim($ruta, '/');
    }
}
