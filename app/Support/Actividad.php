<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

class Actividad
{
    public static function registrar(string $accion, string $seccion, string $entidad, ?int $entidadId, string $detalle, ?User $usuario = null): void
    {
        $usuario ??= auth()->user();

        try {
            DB::table('actividad_log')->insert([
                'usuario_id' => $usuario?->id,
                'usuario_nombre' => $usuario?->nombre,
                'usuario_login' => $usuario?->usuario,
                'rol_usuario' => $usuario?->rol,
                'accion' => $accion,
                'seccion' => $seccion,
                'entidad' => $entidad,
                'entidad_id' => $entidadId,
                'detalle' => $detalle,
                'ip_cliente' => request()->ip(),
                'agente_usuario' => substr((string) request()->userAgent(), 0, 255),
                'creado_en' => now(),
            ]);
        } catch (Throwable) {
            // El registro de actividad no debe interrumpir la operación.
        }
    }

    /**
     * @return array<string, string>
     */
    public static function etiquetasAcciones(): array
    {
        $etiquetas = [
            'login' => 'Inicio de sesión',
            'logout' => 'Cierre de sesión',
            'crear_usuario' => 'Crear usuario',
            'eliminar_usuario' => 'Eliminar usuario',
            'cambiar_clave_usuario' => 'Cambiar contraseña',
            'guardar_permisos_rol' => 'Guardar permisos de rol',
            'crear_inscripcion' => 'Crear inscripción',
            'actualizar_inscripcion' => 'Editar inscripción',
            'eliminar_inscripcion' => 'Eliminar inscripción',
            'crear_presentacion' => 'Crear presentación',
            'actualizar_presentacion' => 'Editar presentación',
            'eliminar_presentacion' => 'Eliminar presentación',
            'crear_ofrenda' => 'Crear ofrenda',
            'actualizar_ofrenda' => 'Editar ofrenda',
            'eliminar_ofrenda' => 'Eliminar ofrenda',
            'crear_valor_adicional' => 'Crear valor adicional',
            'actualizar_valor_adicional' => 'Editar valor adicional',
            'registrar_evento' => 'Registrar participante de evento',
            'importar_registros_eventos' => 'Importar registros de eventos',
            'actualizar_registro_evento' => 'Editar registro de evento',
            'eliminar_registro_evento' => 'Eliminar registro de evento',
            'eliminar_registros_evento' => 'Eliminar registros de evento',
            'crear_evento' => 'Crear evento',
            'actualizar_evento_catalogo' => 'Editar evento',
            'eliminar_evento' => 'Eliminar evento',
            'actualizar_estado_pago_evento' => 'Cambiar estado de pago de evento',
            'marcar_asistencia_evento' => 'Marcar asistencia a evento',
            'reversar_asistencia_evento' => 'Reversar asistencia de evento',
            'crear_miembro' => 'Crear miembro',
            'actualizar_miembro' => 'Editar miembro',
            'eliminar_miembro' => 'Eliminar miembro',
            'crear_territorio' => 'Crear ministerio',
            'actualizar_territorio' => 'Editar ministerio',
            'eliminar_territorio' => 'Eliminar ministerio',
            'crear_casa' => 'Crear casa de vida',
            'actualizar_casa' => 'Editar casa de vida',
            'eliminar_casa' => 'Eliminar casa de vida',
            'asignar_piramide' => 'Crear equipo de la pirámide',
            'actualizar_piramide' => 'Editar equipo de la pirámide',
            'quitar_piramide' => 'Quitar equipo de la pirámide',
            'crear_consejeria' => 'Crear consejería',
            'actualizar_consejeria' => 'Editar consejería',
            'eliminar_consejeria' => 'Eliminar consejería',
            'asignar_cita_consejeria' => 'Asignar cita de consejería',
            'crear_networking' => 'Registrar negocio',
            'sincronizar_bd' => 'Sincronizar base de datos',
            'limpiar_actividad_log' => 'Limpiar log de actividad',
            'generar_informe' => 'Generar informe',
        ];

        asort($etiquetas, SORT_NATURAL | SORT_FLAG_CASE);

        return $etiquetas;
    }

    /**
     * @return array<string, string>
     */
    public static function etiquetasSecciones(): array
    {
        return array_merge(Permisos::etiquetas(), [
            'auth' => 'Sesión',
            'usuarios' => 'Usuarios',
            'actividad' => 'Actividad',
        ]);
    }

    public static function etiquetaAccion(string $accion): string
    {
        return self::etiquetasAcciones()[$accion] ?? str_replace('_', ' ', $accion);
    }

    /**
     * @param  array<string, string>  $filtros
     */
    public static function consulta(array $filtros = []): Builder
    {
        return self::filtrar(DB::table('actividad_log'), $filtros)
            ->orderByDesc('creado_en')
            ->orderByDesc('id');
    }

    /**
     * @param  array<string, string>  $filtros
     */
    public static function limpiar(array $filtros = [], bool $soloFiltrados = false): int
    {
        $consulta = DB::table('actividad_log');

        if ($soloFiltrados) {
            $consulta = self::filtrar($consulta, $filtros);
        }

        return $consulta->delete();
    }

    /**
     * @param  array<string, string>  $filtros
     */
    private static function filtrar(Builder $consulta, array $filtros): Builder
    {
        if (($filtros['buscar'] ?? '') !== '') {
            $like = '%'.addcslashes($filtros['buscar'], '%_\\').'%';
            $consulta->where(function (Builder $grupo) use ($like) {
                $grupo->where('usuario_nombre', 'like', $like)
                    ->orWhere('usuario_login', 'like', $like)
                    ->orWhere('detalle', 'like', $like)
                    ->orWhere('accion', 'like', $like);
            });
        }

        if (($filtros['accion'] ?? '') !== '') {
            $consulta->where('accion', $filtros['accion']);
        }

        if (($filtros['seccion'] ?? '') !== '') {
            $consulta->where('seccion', $filtros['seccion']);
        }

        if (($filtros['fecha_desde'] ?? '') !== '') {
            $consulta->whereDate('creado_en', '>=', $filtros['fecha_desde']);
        }

        if (($filtros['fecha_hasta'] ?? '') !== '') {
            $consulta->whereDate('creado_en', '<=', $filtros['fecha_hasta']);
        }

        return $consulta;
    }
}
