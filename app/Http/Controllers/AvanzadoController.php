<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Actividad;
use App\Support\BaseDatos;
use App\Support\ImportarEventos;
use App\Support\Permisos;
use App\Support\Texto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class AvanzadoController extends Controller
{
    public function inicio(Request $request): View|RedirectResponse
    {
        $rol = $request->user()->rolNormalizado();

        if (! Permisos::puedeVerItem($rol, 'avanzado')) {
            return redirect(Permisos::urlInicio($rol));
        }

        if ($request->filled('pestaña')) {
            return redirect(Permisos::rutaAvanzado($this->pestana((string) $request->query('pestaña'))));
        }

        return redirect(Permisos::rutaAvanzado('usuarios'));
    }

    public function index(Request $request, string $seccion = ''): View|RedirectResponse
    {
        $rol = $request->user()->rolNormalizado();

        if (! Permisos::puedeVerItem($rol, 'avanzado')) {
            return redirect(Permisos::urlInicio($rol));
        }

        $pestana = $this->pestana($seccion !== '' ? $seccion : (string) $request->query('pestaña', 'usuarios'));
        $errorBd = null;
        $usuarios = collect();
        $logs = null;
        $estadoBd = null;
        $rolesPermisos = Permisos::rolesConfigurables();
        $rolPermisos = (string) $request->query('rol', Permisos::ADMIN);

        if (! array_key_exists($rolPermisos, $rolesPermisos)) {
            $rolPermisos = Permisos::ADMIN;
        }

        try {
            if ($pestana === 'usuarios') {
                $usuarios = User::query()->orderBy('usuario')->get();
            }

            if ($pestana === 'logs') {
                $logs = Actividad::consulta($this->filtrosLog($request))->paginate(20)->withQueryString();
            }

            if ($pestana === 'bd') {
                $estadoBd = BaseDatos::estado();
            }
        } catch (Throwable) {
            $errorBd = 'No se pudieron cargar los datos de esta sección.';
        }

        return view('avanzado.index', [
            'tituloPagina' => 'Avanzado',
            'seccionActiva' => 'avanzado',
            'pestaña' => $pestana,
            'menuAvanzado' => Permisos::menuAvanzado($rol),
            'usuarios' => $usuarios,
            'logs' => $logs,
            'estadoBd' => $estadoBd,
            'errorBd' => $errorBd,
            'roles' => Permisos::etiquetasRoles(),
            'rolesNuevo' => Permisos::rolesConfigurables(),
            'rolesPermisos' => $rolesPermisos,
            'rolPermisos' => $rolPermisos,
            'permisosRol' => Permisos::permisosDe($rolPermisos),
            'catalogo' => Permisos::catalogo(),
            'iconos' => Permisos::iconos(),
            'etiquetasAcciones' => Actividad::etiquetasAcciones(),
            'etiquetasSeccionesLog' => Actividad::etiquetasSecciones(),
            'filtrosLog' => $this->filtrosLog($request),
            'resultadoImport' => session('import_eventos'),
            'titulo' => 'Avanzado',
        ]);
    }

    public function guardarUsuario(Request $request): RedirectResponse
    {
        $this->exigirSuperadmin($request);

        $datos = $request->validate([
            'usuario' => ['required', 'string', 'max:50', 'unique:usuarios,usuario'],
            'nombre' => ['nullable', 'string', 'max:100'],
            'clave' => ['required', 'string', 'min:6', 'max:100'],
            'rol' => ['required', 'in:'.implode(',', array_keys(Permisos::rolesConfigurables()))],
        ]);

        $usuario = User::query()->create([
            'usuario' => trim($datos['usuario']),
            'nombre' => Texto::titulo($datos['nombre'] ?? ''),
            'clave' => Hash::make($datos['clave']),
            'rol' => $datos['rol'],
        ]);

        Actividad::registrar('crear_usuario', 'usuarios', 'usuario', $usuario->id, 'Crear usuario · '.$usuario->usuario);

        return redirect()->route('avanzado.usuarios.ver')->with('ok', 'Usuario creado correctamente.');
    }

    public function cambiarClave(Request $request, User $usuario): RedirectResponse
    {
        $this->exigirSuperadmin($request);

        $datos = $request->validate([
            'clave' => ['required', 'string', 'min:6', 'max:100', 'confirmed'],
        ], [], [
            'clave' => 'contraseña',
        ]);

        $usuario->clave = Hash::make($datos['clave']);
        $usuario->save();
        Actividad::registrar('cambiar_clave_usuario', 'usuarios', 'usuario', $usuario->id, 'Cambiar contraseña · '.$usuario->usuario);

        return redirect()->route('avanzado.usuarios.ver')->with('ok', 'Contraseña actualizada correctamente.');
    }

    public function eliminarUsuario(Request $request, User $usuario): RedirectResponse
    {
        $this->exigirSuperadmin($request);
        abort_if($usuario->id === $request->user()->id, 403, 'No puedes eliminar tu propio usuario.');

        $id = $usuario->id;
        $login = $usuario->usuario;
        $usuario->delete();
        Actividad::registrar('eliminar_usuario', 'usuarios', 'usuario', $id, 'Eliminar usuario · '.$login);

        return redirect()->route('avanzado.usuarios.ver')->with('ok', 'Usuario eliminado.');
    }

    public function guardarPermisos(Request $request): RedirectResponse
    {
        $this->exigirSuperadmin($request);

        $datos = $request->validate([
            'rol' => ['required', 'in:'.implode(',', array_keys(Permisos::rolesConfigurables()))],
            'permisos' => ['nullable', 'array'],
            'permisos.*' => ['string', 'max:80'],
        ]);

        Permisos::guardarRol($datos['rol'], $datos['permisos'] ?? []);
        Actividad::registrar(
            'guardar_permisos_rol',
            'usuarios',
            'permisos',
            null,
            'Guardar permisos · '.(Permisos::rolesConfigurables()[$datos['rol']] ?? $datos['rol'])
        );

        return redirect()->route('avanzado.permisos.ver', ['rol' => $datos['rol']])
            ->with('ok', 'Permisos actualizados correctamente.');
    }

    public function plantillaImport(): StreamedResponse
    {
        abort_unless(Permisos::puedeVerItem(auth()->user()->rolNormalizado(), 'avanzado'), 403);

        return ImportarEventos::plantilla();
    }

    public function importar(Request $request): RedirectResponse
    {
        $this->exigirSuperadmin($request);

        $request->validate([
            'archivo' => ['required', 'file', 'max:5120'],
        ]);

        try {
            $resultado = ImportarEventos::procesar($request->file('archivo'), $request->user());
        } catch (InvalidArgumentException $e) {
            return redirect()->route('avanzado.importar.ver')->withErrors(['archivo' => $e->getMessage()]);
        }

        Actividad::registrar(
            'importar_registros_eventos',
            'eventos',
            'registro_evento',
            null,
            'Importar registros · '.$resultado['importados'].' importado(s), '.$resultado['omitidos'].' error(es)'
        );

        $mensaje = $resultado['importados'] > 0 && $resultado['omitidos'] === 0
            ? 'Importación completada: '.$resultado['importados'].' registro(s).'
            : ($resultado['importados'] > 0
                ? 'Importación parcial: '.$resultado['importados'].' importado(s), '.$resultado['omitidos'].' con error.'
                : 'No se importó ningún registro. Revisa los errores.');

        return redirect()->route('avanzado.importar.ver')
            ->with('ok', $mensaje)
            ->with('import_eventos', $resultado);
    }

    public function limpiarLogs(Request $request): RedirectResponse
    {
        $this->exigirSuperadmin($request);

        $modo = $request->input('modo', 'todo');
        $filtros = $this->filtrosLog($request);
        $eliminados = Actividad::limpiar($filtros, $modo === 'filtrados');

        Actividad::registrar(
            'limpiar_actividad_log',
            'actividad',
            'actividad_log',
            null,
            ($modo === 'filtrados' ? 'Limpiar log filtrado' : 'Limpiar todo el log').' · '.$eliminados.' registro(s)'
        );

        return redirect()->route('avanzado.logs')->with(
            'ok',
            $eliminados === 1 ? 'Se eliminó 1 registro del log.' : 'Se eliminaron '.$eliminados.' registro(s) del log.'
        );
    }

    private function exigirSuperadmin(Request $request): void
    {
        abort_unless($request->user()->rolNormalizado() === Permisos::SUPERADMIN, 403);
    }

    private function pestana(string $seccion): string
    {
        $permitidas = ['usuarios', 'permisos', 'bd', 'importar', 'logs'];

        return in_array($seccion, $permitidas, true) ? $seccion : 'usuarios';
    }

    /**
     * @return array<string, string>
     */
    private function filtrosLog(Request $request): array
    {
        return [
            'buscar' => trim((string) $request->input('buscar', $request->query('buscar', ''))),
            'accion' => trim((string) $request->input('accion', $request->query('accion', ''))),
            'seccion' => trim((string) $request->input('seccion', $request->query('seccion', ''))),
            'fecha_desde' => trim((string) $request->input('fecha_desde', $request->query('fecha_desde', ''))),
            'fecha_hasta' => trim((string) $request->input('fecha_hasta', $request->query('fecha_hasta', ''))),
        ];
    }
}
