<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Throwable;

class Permisos
{
    public const SUPERADMIN = 'superadmin';

    public const ADMIN = 'administrador';

    public const COUNTER = 'counter';

    public const CONEXION = 'conexion';

    /** @var array<string, array<int, string>> */
    private static array $cache = [];

    public static function normalizarRol(string $rol): string
    {
        return $rol === 'contador' ? self::COUNTER : $rol;
    }

    /**
     * @return array<string, string>
     */
    public static function etiquetasRoles(): array
    {
        return [
            self::SUPERADMIN => 'Superadmin',
            self::ADMIN => 'Administrador',
            self::COUNTER => 'Counter',
            self::CONEXION => 'Conexión',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function etiquetas(): array
    {
        return [
            'generales' => 'Registros generales',
            'escol' => 'Escol',
            'academia' => 'Academy',
            'bautismo' => 'Bautismo',
            'conexion' => 'Conexión',
            'presentaciones' => 'Presentación niños',
            'networking' => 'Networking',
            'ofrendas' => 'Ofrendas',
            'valores_adicionales' => 'Valores adicionales',
            'eventos' => 'Eventos',
            'calendario' => 'Calendario',
            'consejeria' => 'Consejería',
            'generar_informe' => 'Generar informe',
            'estructura' => 'Estructura CDA',
            'avanzado' => 'Avanzado',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function iconos(): array
    {
        return [
            'generales' => 'grid',
            'escol' => 'book',
            'academia' => 'mortarboard',
            'bautismo' => 'droplet',
            'conexion' => 'person-plus',
            'presentaciones' => 'people',
            'networking' => 'briefcase',
            'ofrendas' => 'cash-stack',
            'valores_adicionales' => 'wallet2',
            'eventos' => 'calendar-event',
            'calendario' => 'calendar3',
            'consejeria' => 'chat-heart',
            'generar_informe' => 'file-earmark-bar-graph',
            'estructura' => 'diagram-3',
            'avanzado' => 'sliders',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function zonas(): array
    {
        return [
            'norte' => 'Norte',
            'sur' => 'Sur',
            'centro' => 'Centro',
            'samborondon' => 'Samborondón',
            'duran' => 'Durán',
            'otros' => 'Otros',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function tiposConsejeria(): array
    {
        return [
            'personal' => 'Personal',
            'matrimonial' => 'Matrimonial',
            'familiar' => 'Familiar',
            'jovenes' => 'Jóvenes',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function parentescos(): array
    {
        return [
            'padre' => 'Padre',
            'madre' => 'Madre',
            'abuelo' => 'Abuelo',
            'abuela' => 'Abuela',
            'tio' => 'Tío',
            'tia' => 'Tía',
            'tutor_legal' => 'Tutor legal',
            'otro' => 'Otro',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function ordenMenu(): array
    {
        return [
            'generales',
            'escol',
            'academia',
            'bautismo',
            'conexion',
            'presentaciones',
            'networking',
            'ofrendas',
            'eventos',
            'calendario',
            'valores_adicionales',
            'consejeria',
            'generar_informe',
            'estructura',
            'avanzado',
        ];
    }

    /**
     * @return array<string, array{etiqueta: string, permisos: array<string, string>}>
     */
    public static function catalogo(): array
    {
        return [
            'generales' => ['etiqueta' => 'Registros generales', 'permisos' => ['ver' => 'Ver registros del día']],
            'escol' => ['etiqueta' => 'Escol', 'permisos' => ['registros' => 'Registros', 'nuevo' => 'Nuevo registro']],
            'academia' => ['etiqueta' => 'Academy', 'permisos' => ['registros' => 'Registros', 'nuevo' => 'Nuevo registro']],
            'bautismo' => ['etiqueta' => 'Bautismo', 'permisos' => ['registros' => 'Registros', 'nuevo' => 'Nuevo registro']],
            'conexion' => ['etiqueta' => 'Conexión', 'permisos' => ['registros' => 'Registros', 'nuevo' => 'Nuevo registro']],
            'presentaciones' => ['etiqueta' => 'Presentación niños', 'permisos' => ['registros' => 'Registros', 'nuevo' => 'Nueva presentación']],
            'networking' => ['etiqueta' => 'Networking', 'permisos' => ['registros' => 'Directorio', 'nuevo' => 'Registrar negocio']],
            'ofrendas' => ['etiqueta' => 'Ofrendas', 'permisos' => ['registros' => 'Registros', 'nuevo' => 'Nueva ofrenda']],
            'eventos' => ['etiqueta' => 'Eventos', 'permisos' => [
                'tabla' => 'Tabla de eventos',
                'registrar' => 'Registro de eventos',
                'agregar' => 'Agregar eventos',
                'catalogo' => 'Eventos registrados',
                'participantes' => 'Participantes del evento',
                'informe' => 'Informe de evento',
            ]],
            'calendario' => ['etiqueta' => 'Calendario', 'permisos' => ['ver' => 'Calendario', 'gestionar' => 'Gestionar', 'nuevo' => 'Nuevo evento']],
            'valores_adicionales' => ['etiqueta' => 'Valores adicionales', 'permisos' => ['registros' => 'Registros', 'nuevo' => 'Nuevo valor', 'tipos' => 'Tipos de valores']],
            'consejeria' => ['etiqueta' => 'Consejería', 'permisos' => ['registros' => 'Registros', 'nuevo' => 'Nueva solicitud']],
            'generar_informe' => ['etiqueta' => 'Generar informe', 'permisos' => [
                'completo' => 'Informe general',
                'ofrendas' => 'Informe de ofrendas',
                'valores' => 'Informe de valores adicionales',
                'eventos' => 'Informe de eventos',
                'presentaciones' => 'Informe de presentación de niños',
                'bautismos' => 'Informe de personas bautizadas',
            ]],
            'estructura' => ['etiqueta' => 'Estructura CDA', 'permisos' => [
                'lideres' => 'Miembros',
                'territorios' => 'Ministerios',
                'casas' => 'Casas de vida',
                'piramide' => 'Pirámide',
                'importar' => 'Importar estructura',
            ]],
        ];
    }

    public static function seccionOculta(string $seccion): bool
    {
        return $seccion === 'transporte_aniversario';
    }

    /**
     * @return array<int, string>
     */
    public static function clavesConfigurables(): array
    {
        $claves = [];

        foreach (self::catalogo() as $modulo => $info) {
            foreach ($info['permisos'] as $detalle => $_etiqueta) {
                $claves[] = $modulo.':'.$detalle;
            }
        }

        return $claves;
    }

    /**
     * @param  array<int, string>  $modulos
     * @return array<int, string>
     */
    public static function expandir(array $modulos, ?string $rol = null): array
    {
        $permisos = [];

        foreach ($modulos as $modulo) {
            $info = self::catalogo()[$modulo] ?? null;

            if ($info === null) {
                $permisos[] = $modulo;

                continue;
            }

            foreach ($info['permisos'] as $detalle => $_etiqueta) {
                if ($modulo === 'eventos' && $detalle === 'agregar' && $rol !== self::SUPERADMIN) {
                    continue;
                }

                if ($modulo === 'valores_adicionales' && $detalle === 'tipos' && $rol !== self::SUPERADMIN) {
                    continue;
                }

                $permisos[] = $modulo.':'.$detalle;
            }
        }

        return array_values(array_unique($permisos));
    }

    /**
     * @return array<int, string>
     */
    public static function permisosPorDefecto(string $rol): array
    {
        $rol = self::normalizarRol($rol);

        $modulos = match ($rol) {
            self::ADMIN => [
                'generales', 'escol', 'academia', 'bautismo', 'conexion', 'presentaciones',
                'networking', 'ofrendas', 'valores_adicionales', 'eventos', 'calendario',
                'consejeria', 'generar_informe', 'estructura',
            ],
            self::COUNTER => [
                'generales', 'escol', 'academia', 'bautismo', 'presentaciones', 'networking',
                'ofrendas', 'valores_adicionales', 'consejeria', 'generar_informe',
            ],
            self::CONEXION => ['generales', 'conexion'],
            default => [],
        };

        return self::expandir($modulos, $rol);
    }

    /**
     * @return array<int, string>
     */
    public static function permisosDe(string $rol): array
    {
        $rol = self::normalizarRol($rol);

        if ($rol === self::SUPERADMIN) {
            return self::clavesConfigurables();
        }

        if (isset(self::$cache[$rol])) {
            return self::$cache[$rol];
        }

        try {
            $filas = DB::table('rol_permisos')->where('rol', $rol)->orderBy('seccion')->pluck('seccion')->all();
        } catch (Throwable) {
            $filas = [];
        }

        self::$cache[$rol] = $filas === [] ? self::permisosPorDefecto($rol) : array_map('strval', $filas);

        return self::$cache[$rol];
    }

    public static function tieneSeccion(string $rol, string $seccion): bool
    {
        if (self::seccionOculta($seccion)) {
            return false;
        }

        $rol = self::normalizarRol($rol);

        if ($rol === self::SUPERADMIN) {
            return true;
        }

        $permisos = self::permisosDe($rol);

        if (in_array($seccion, $permisos, true)) {
            return true;
        }

        $prefijo = $seccion.':';

        foreach ($permisos as $permiso) {
            if (str_starts_with($permiso, $prefijo)) {
                return true;
            }
        }

        return false;
    }

    public static function tieneDetalle(string $rol, string $modulo, string $detalle): bool
    {
        if (self::seccionOculta($modulo)) {
            return false;
        }

        $rol = self::normalizarRol($rol);

        if ($rol === self::SUPERADMIN) {
            return true;
        }

        $permisos = self::permisosDe($rol);

        return in_array($modulo.':'.$detalle, $permisos, true) || in_array($modulo, $permisos, true);
    }

    public static function controlTotal(string $rol): bool
    {
        $rol = self::normalizarRol($rol);

        return in_array($rol, [self::SUPERADMIN, self::ADMIN], true);
    }

    /**
     * @return array<int, string>
     */
    public static function tiposInscripcion(string $rol): array
    {
        $permitidos = [];

        foreach (['escol', 'academia', 'bautismo', 'conexion'] as $tipo) {
            if (self::tieneSeccion($rol, $tipo)) {
                $permitidos[] = $tipo;
            }
        }

        return $permitidos;
    }

    public static function puedeVerGenerales(string $rol): bool
    {
        return self::tieneDetalle($rol, 'generales', 'ver')
            || self::tieneSeccion($rol, 'generales')
            || self::tiposInscripcion($rol) !== []
            || self::tieneSeccion($rol, 'presentaciones')
            || self::tieneSeccion($rol, 'ofrendas')
            || self::tieneSeccion($rol, 'valores_adicionales')
            || self::tieneSeccion($rol, 'eventos')
            || self::tieneSeccion($rol, 'consejeria');
    }

    public static function puedeVerItem(string $rol, string $clave): bool
    {
        $rol = self::normalizarRol($rol);

        if ($clave === 'generales') {
            return self::puedeVerGenerales($rol);
        }

        if ($clave === 'avanzado') {
            return $rol === self::SUPERADMIN;
        }

        if ($clave === 'generar_informe') {
            return self::tieneSeccion($rol, 'generar_informe') || self::tieneDetalle($rol, 'eventos', 'informe');
        }

        return self::tieneSeccion($rol, $clave);
    }

    public static function puedeRegistrar(string $rol, string $seccion): bool
    {
        $detalle = match ($seccion) {
            'escol', 'academia', 'bautismo', 'conexion', 'presentaciones', 'networking', 'ofrendas', 'valores_adicionales', 'consejeria' => 'nuevo',
            'eventos' => 'registrar',
            default => null,
        };

        return $detalle !== null && self::tieneDetalle($rol, $seccion, $detalle);
    }

    /** Acceso al portal del plugin WordPress (presentaciones u ofrendas). */
    public static function puedeAccederPortalPlugin(string $rol): bool
    {
        return self::tieneSeccion($rol, 'presentaciones') || self::puedeRegistrar($rol, 'ofrendas');
    }

    public static function puedeVerTablaEventos(string $rol): bool
    {
        return self::tieneDetalle($rol, 'eventos', 'tabla');
    }

    public static function puedeAgregarEventos(string $rol): bool
    {
        return self::tieneDetalle($rol, 'eventos', 'agregar');
    }

    public static function puedeVerCatalogoEventos(string $rol): bool
    {
        return self::tieneDetalle($rol, 'eventos', 'catalogo');
    }

    public static function puedeVerParticipantesEventos(string $rol): bool
    {
        return self::tieneDetalle($rol, 'eventos', 'participantes') || self::puedeVerCatalogoEventos($rol);
    }

    public static function puedeVerInformeEventos(string $rol): bool
    {
        return self::tieneDetalle($rol, 'eventos', 'informe') || self::tieneDetalle($rol, 'generar_informe', 'eventos');
    }

    public static function puedeUsarCheckoutEventos(string $rol): bool
    {
        return self::tieneSeccion($rol, 'eventos');
    }

    /**
     * @return array<int, string>
     */
    public static function pestanasEstructura(string $rol): array
    {
        $permitidas = [];

        foreach (['lideres', 'territorios', 'casas', 'piramide'] as $pestana) {
            if (self::tieneDetalle($rol, 'estructura', $pestana) || self::tieneDetalle($rol, 'estructura', 'acceso')) {
                $permitidas[] = $pestana;
            }
        }

        if ($permitidas === [] && self::tieneSeccion($rol, 'estructura')) {
            return ['lideres', 'territorios', 'casas', 'piramide'];
        }

        if ($permitidas !== [] && ! in_array('piramide', $permitidas, true) && self::tieneSeccion($rol, 'estructura')) {
            $permitidas[] = 'piramide';
        }

        return $permitidas;
    }

    /**
     * @return array<int, array{clave: string, ruta: string, etiqueta: string, icono: string, texto: string}>
     */
    public static function menuEstructura(string $rol): array
    {
        $items = [
            'lideres' => [
                'clave' => 'lideres',
                'ruta' => 'estructura.miembros',
                'etiqueta' => 'Miembros',
                'icono' => 'people',
                'texto' => 'Registra y consulta los miembros de la iglesia.',
            ],
            'territorios' => [
                'clave' => 'territorios',
                'ruta' => 'estructura.ministerios',
                'etiqueta' => 'Ministerios',
                'icono' => 'map',
                'texto' => 'Categorías y ministerios de la estructura.',
            ],
            'casas' => [
                'clave' => 'casas',
                'ruta' => 'estructura.casas',
                'etiqueta' => 'Casas de vida',
                'icono' => 'house-heart',
                'texto' => 'Casas asignadas a un territorio.',
            ],
            'piramide' => [
                'clave' => 'piramide',
                'ruta' => 'estructura.piramide',
                'etiqueta' => 'Pirámide',
                'icono' => 'diagram-3',
                'texto' => 'Equipos y niveles de liderazgo.',
            ],
        ];

        $permitidas = self::pestanasEstructura($rol);

        return array_values(array_filter(
            $items,
            fn (array $item, string $clave) => in_array($clave, $permitidas, true),
            ARRAY_FILTER_USE_BOTH
        ));
    }

    public static function rutaEstructura(string $pestana): string
    {
        return match ($pestana) {
            'lideres' => route('estructura.miembros'),
            'territorios' => route('estructura.ministerios'),
            'casas' => route('estructura.casas'),
            'piramide' => route('estructura.piramide'),
            default => route('estructura'),
        };
    }

    /**
     * @return array<int, string>
     */
    public static function pestanasAvanzado(string $rol): array
    {
        return self::puedeVerItem($rol, 'avanzado')
            ? ['usuarios', 'permisos', 'bd', 'importar', 'logs']
            : [];
    }

    /**
     * @return array<int, array{clave: string, ruta: string, etiqueta: string, icono: string, texto: string}>
     */
    public static function menuAvanzado(string $rol): array
    {
        $items = [
            'usuarios' => [
                'clave' => 'usuarios',
                'ruta' => 'avanzado.usuarios.ver',
                'etiqueta' => 'Usuarios',
                'icono' => 'people',
                'texto' => 'Crea accesos al panel y cambia contraseñas.',
            ],
            'permisos' => [
                'clave' => 'permisos',
                'ruta' => 'avanzado.permisos.ver',
                'etiqueta' => 'Permisos',
                'icono' => 'shield-check',
                'texto' => 'Elige qué puede ver o hacer cada rol.',
            ],
            'bd' => [
                'clave' => 'bd',
                'ruta' => 'avanzado.bd',
                'etiqueta' => 'Base de datos',
                'icono' => 'database-gear',
                'texto' => 'Revisa la conexión y las tablas instaladas.',
            ],
            'importar' => [
                'clave' => 'importar',
                'ruta' => 'avanzado.importar.ver',
                'etiqueta' => 'Importar',
                'icono' => 'file-earmark-arrow-up',
                'texto' => 'Carga participantes de eventos desde un CSV.',
            ],
            'logs' => [
                'clave' => 'logs',
                'ruta' => 'avanzado.logs',
                'etiqueta' => 'Actividad',
                'icono' => 'journal-text',
                'texto' => 'Consulta el historial de acciones del panel.',
            ],
        ];

        $permitidas = self::pestanasAvanzado($rol);

        return array_values(array_filter(
            $items,
            fn (array $item, string $clave) => in_array($clave, $permitidas, true),
            ARRAY_FILTER_USE_BOTH
        ));
    }

    public static function rutaAvanzado(string $pestana): string
    {
        return match ($pestana) {
            'usuarios' => route('avanzado.usuarios.ver'),
            'permisos' => route('avanzado.permisos.ver'),
            'bd' => route('avanzado.bd'),
            'importar' => route('avanzado.importar.ver'),
            'logs' => route('avanzado.logs'),
            default => route('avanzado'),
        };
    }

    /**
     * @return array<int, string>
     */
    public static function pestanasEventos(string $rol): array
    {
        $pestañas = [];

        if (self::puedeVerTablaEventos($rol) || self::puedeRegistrar($rol, 'eventos')) {
            $pestañas[] = 'registros';
        }

        if (self::puedeVerCatalogoEventos($rol) || self::puedeAgregarEventos($rol)) {
            $pestañas[] = 'eventos';
        }

        return $pestañas;
    }

    /**
     * @return array<int, array{clave: string, ruta: string, etiqueta: string, icono: string, texto: string}>
     */
    public static function menuEventos(string $rol): array
    {
        $items = [
            'registros' => [
                'clave' => 'registros',
                'ruta' => 'eventos.registros',
                'etiqueta' => 'Registros',
                'icono' => 'list-ul',
                'texto' => 'Registra y consulta participantes.',
            ],
            'eventos' => [
                'clave' => 'eventos',
                'ruta' => 'eventos.catalogo',
                'etiqueta' => 'Eventos',
                'icono' => 'calendar-event',
                'texto' => 'Catálogo de eventos y tipos de entrada.',
            ],
        ];

        $permitidas = self::pestanasEventos($rol);

        return array_values(array_filter(
            $items,
            fn (array $item, string $clave) => in_array($clave, $permitidas, true),
            ARRAY_FILTER_USE_BOTH
        ));
    }

    public static function rutaEventos(string $pestana): string
    {
        return match ($pestana) {
            'registros' => route('eventos.registros'),
            'eventos' => route('eventos.catalogo'),
            default => route('eventos'),
        };
    }

    /**
     * @return array<int, array{clave: string, ruta: string, etiqueta: string, icono: string, texto: string}>
     */
    public static function menuFlujo(string $rol, string $seccion): array
    {
        if (! self::puedeVerItem($rol, $seccion)) {
            return [];
        }

        $textos = match ($seccion) {
            'presentaciones' => [
                'nuevos' => 'Niños recibidos, pendientes de presentar.',
                'registros' => 'Niños ya presentados.',
            ],
            'bautismo' => [
                'nuevos' => 'Ingresados, pendientes de bautizar.',
                'registros' => 'Personas ya bautizadas.',
            ],
            'conexion' => [
                'nuevos' => 'Pendientes de contactar.',
                'registros' => 'Personas ya contactadas.',
            ],
            default => [
                'nuevos' => 'Pendientes de completar su proceso.',
                'registros' => 'Consulta de registros.',
            ],
        };

        $prefijo = $seccion === 'presentaciones' ? 'presentaciones' : $seccion;

        return [
            [
                'clave' => 'nuevos',
                'ruta' => $prefijo.'.nuevos',
                'etiqueta' => 'Nuevos registros',
                'icono' => 'inbox',
                'texto' => $textos['nuevos'],
            ],
            [
                'clave' => 'registros',
                'ruta' => $prefijo.'.registros',
                'etiqueta' => 'Registros',
                'icono' => 'list-ul',
                'texto' => $textos['registros'],
            ],
        ];
    }

    public static function rutaFlujo(string $seccion, string $pestana): string
    {
        $pestana = in_array($pestana, ['nuevos', 'registros'], true) ? $pestana : 'nuevos';

        return match ($seccion) {
            'presentaciones' => $pestana === 'registros'
                ? route('presentaciones.registros')
                : route('presentaciones.nuevos'),
            'bautismo' => $pestana === 'registros'
                ? route('bautismo.registros')
                : route('bautismo.nuevos'),
            'conexion' => $pestana === 'registros'
                ? route('conexion.registros')
                : route('conexion.nuevos'),
            default => self::url($seccion),
        };
    }

    /**
     * @return array<string, array<int, array{clave: string, ruta: string, etiqueta: string, icono: string, texto: string}>>
     */
    public static function menusLaterales(string $rol): array
    {
        $menus = [
            'estructura' => self::menuEstructura($rol),
            'avanzado' => self::menuAvanzado($rol),
            'eventos' => self::menuEventos($rol),
        ];

        foreach (['presentaciones', 'bautismo', 'conexion'] as $seccion) {
            $menus[$seccion] = self::menuFlujo($rol, $seccion);
        }

        return $menus;
    }

    public static function registroEventoRequierePagoInmediato(string $rol): bool
    {
        return self::normalizarRol($rol) === self::COUNTER;
    }

    public static function puedeCrearRegistroEventoPendiente(string $rol): bool
    {
        return self::controlTotal($rol);
    }

    public static function puedeGestionarEstadoConexion(string $rol): bool
    {
        return in_array(self::normalizarRol($rol), [self::SUPERADMIN, self::ADMIN, self::CONEXION], true);
    }

    /**
     * @return array<int, string>
     */
    public static function itemsMenu(string $rol): array
    {
        $items = [];

        foreach (self::ordenMenu() as $clave) {
            if (self::puedeVerItem($rol, $clave)) {
                $items[] = $clave;
            }
        }

        return $items;
    }

    public static function urlInicio(string $rol): string
    {
        $primera = self::itemsMenu($rol)[0] ?? 'generales';

        return self::url($primera);
    }

    /**
     * @return array<string, string>
     */
    public static function rolesConfigurables(): array
    {
        $roles = self::etiquetasRoles();
        unset($roles[self::SUPERADMIN]);

        return $roles;
    }

    /**
     * @param  array<int, string>  $permisos
     */
    public static function guardarRol(string $rol, array $permisos): void
    {
        $rol = self::normalizarRol($rol);

        if (! array_key_exists($rol, self::rolesConfigurables())) {
            throw new InvalidArgumentException('Rol no configurable.');
        }

        $permitidas = self::clavesConfigurables();
        $permisos = array_values(array_unique(array_filter(
            $permisos,
            static fn (string $permiso): bool => in_array($permiso, $permitidas, true)
        )));

        DB::transaction(function () use ($rol, $permisos) {
            DB::table('rol_permisos')->where('rol', $rol)->delete();

            foreach ($permisos as $permiso) {
                DB::table('rol_permisos')->insert([
                    'rol' => $rol,
                    'seccion' => $permiso,
                ]);
            }
        });

        self::$cache = [];
    }

    public static function url(string $clave): string
    {
        return match ($clave) {
            'generales' => route('generales'),
            'escol' => route('escol'),
            'academia' => route('academia'),
            'bautismo' => route('bautismo.nuevos'),
            'conexion' => route('conexion.nuevos'),
            'presentaciones' => route('presentaciones.nuevos'),
            'networking' => route('networking'),
            'ofrendas' => route('ofrendas'),
            'eventos' => route('eventos.registros'),
            'calendario' => route('calendario'),
            'valores_adicionales' => route('valores'),
            'consejeria' => route('consejeria'),
            'generar_informe' => route('informes'),
            'estructura' => route('estructura.miembros'),
            'avanzado' => route('avanzado.usuarios.ver'),
            default => route('generales'),
        };
    }
}
