<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PDO;
use Throwable;

class BaseDatos
{
    /**
     * @return array<int, string>
     */
    public static function tablasEsperadas(): array
    {
        return [
            'usuarios',
            'inscripciones',
            'presentaciones_ninos',
            'ofrendas',
            'territorios',
            'lideres',
            'territorio_asignaciones',
            'miembro_parentescos',
            'miembro_cursos',
            'casas_vida',
            'sesiones_api',
            'eventos',
            'eventos_tipos_entrada',
            'eventos_campos_adicionales',
            'valores_adicionales',
            'tipos_valor_adicional',
            'consejerias',
            'networking_emprendedores',
            'calendario_eventos',
            'transporte_aniversario',
            'rol_permisos',
            'actividad_log',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function estado(): array
    {
        $config = config('database.connections.'.config('database.default'), []);
        $error = null;
        $servidor = false;
        $conectada = false;
        $version = null;
        $tablas = [];

        try {
            $pdo = DB::connection()->getPdo();
            $servidor = true;
            $conectada = true;
            $version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);

            foreach (self::tablasEsperadas() as $nombre) {
                $instalada = Schema::hasTable($nombre);
                $registros = null;

                if ($instalada) {
                    try {
                        $registros = (int) DB::table($nombre)->count();
                    } catch (Throwable) {
                        $registros = null;
                    }
                }

                $tablas[] = [
                    'nombre' => $nombre,
                    'instalada' => $instalada,
                    'registros' => $registros,
                ];
            }
        } catch (Throwable $e) {
            $error = 'No se pudo consultar la base de datos.';
        }

        $instaladas = collect($tablas)->where('instalada', true)->count();
        $total = count(self::tablasEsperadas());

        return [
            'host' => $config['host'] ?? '',
            'puerto' => $config['port'] ?? '',
            'base_datos' => $config['database'] ?? '',
            'usuario' => $config['username'] ?? '',
            'servidor_conectado' => $servidor,
            'bd_conectada' => $conectada,
            'version_mysql' => $version,
            'error' => $error,
            'tablas' => $tablas,
            'tablas_instaladas' => $instaladas,
            'total_tablas' => $total,
            'sistema_listo' => $conectada && $instaladas === $total && $total > 0,
        ];
    }
}
