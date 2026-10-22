<?php

namespace App\Support;

use App\Models\PiramideNodo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class Piramide
{
    public static function asegurarTabla(): void
    {
        if (! Schema::hasTable('piramide_equipos')) {
            Schema::create('piramide_equipos', function (Blueprint $table) {
                $table->increments('id');
                $table->string('nombre', 100);
                $table->unsignedInteger('padre_id')->nullable();
                $table->unsignedInteger('orden')->default(0);
                $table->timestamp('creado_en')->useCurrent();
                $table->index('padre_id');
            });
        } else {
            if (! Schema::hasColumn('piramide_equipos', 'nombre')) {
                Schema::table('piramide_equipos', function (Blueprint $table) {
                    $table->string('nombre', 100)->default('')->after('id');
                });
            }

            try {
                Schema::table('piramide_equipos', function (Blueprint $table) {
                    $table->dropUnique('uniq_piramide_lider');
                });
            } catch (Throwable) {
                // El índice viejo ya no existe.
            }

            if (Schema::hasColumn('piramide_equipos', 'lider_id')) {
                try {
                    DB::statement('ALTER TABLE piramide_equipos MODIFY lider_id INT NULL DEFAULT NULL');
                } catch (Throwable) {
                    // Si no se puede relajar la columna, se rellena al crear.
                }
            }
        }

        if (! Schema::hasTable('piramide_equipo_miembros')) {
            Schema::create('piramide_equipo_miembros', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('equipo_id');
                $table->unsignedInteger('lider_id');
                $table->unsignedInteger('orden')->default(0);
                $table->timestamp('creado_en')->useCurrent();
                $table->unique(['equipo_id', 'lider_id'], 'uniq_piramide_equipo_lider');
                $table->index('lider_id');
            });

            self::migrarNodosAntiguos();
        }
    }

    /**
     * @return Collection<int, PiramideNodo>
     */
    public static function arbol(): Collection
    {
        self::asegurarTabla();

        $todos = PiramideNodo::query()
            ->with(['miembros.miembro.asignacionesMinisterio.territorio'])
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        $porPadre = $todos->groupBy(fn (PiramideNodo $nodo) => (int) ($nodo->padre_id ?: 0));

        $colgar = function (int $padreId) use (&$colgar, $porPadre) {
            return $porPadre->get($padreId, collect())->values()->map(function (PiramideNodo $nodo) use (&$colgar) {
                $nodo->setRelation('hijos', $colgar($nodo->id));

                return $nodo;
            });
        };

        return $colgar(0);
    }

    private static function migrarNodosAntiguos(): void
    {
        if (! Schema::hasColumn('piramide_equipos', 'lider_id')) {
            return;
        }

        $filas = DB::table('piramide_equipos')->orderBy('id')->get();

        foreach ($filas as $fila) {
            $liderId = (int) ($fila->lider_id ?? 0);
            $nombre = trim((string) ($fila->nombre ?? ''));

            if ($nombre === '') {
                $nivel = (int) ($fila->nivel ?? 1);
                $nombre = $nivel <= 1 ? 'Cabezas de la iglesia' : 'Equipo';
                DB::table('piramide_equipos')->where('id', $fila->id)->update(['nombre' => $nombre]);
            }

            if ($liderId > 0) {
                DB::table('piramide_equipo_miembros')->insertOrIgnore([
                    'equipo_id' => $fila->id,
                    'lider_id' => $liderId,
                    'orden' => 1,
                    'creado_en' => now(),
                ]);
            }
        }
    }
}
