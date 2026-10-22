<?php

namespace App\Support;

use App\Models\CalendarioEvento;
use App\Models\CasaVida;
use App\Models\Consejeria;
use App\Models\Evento;
use App\Models\Lider;
use App\Models\Inscripcion;
use App\Models\NetworkingEmprendedor;
use App\Models\Ofrenda;
use App\Models\PresentacionNino;
use App\Models\Territorio;
use App\Models\User;
use App\Models\ValorAdicional;
use Illuminate\Support\Carbon;

class DetalleRegistro
{
    public static function texto(mixed $valor): string
    {
        $valor = trim((string) $valor);

        return $valor !== '' ? $valor : '—';
    }

    public static function fecha(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }

        try {
            return Carbon::parse($valor)->format('d/m/Y');
        } catch (\Throwable) {
            return (string) $valor;
        }
    }

    public static function fechaHora(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }

        try {
            return Carbon::parse($valor)->format('d/m/Y H:i');
        } catch (\Throwable) {
            return (string) $valor;
        }
    }

    public static function dinero(mixed $valor): string
    {
        return '$ '.number_format((float) $valor, 2);
    }

    /**
     * @return array{etiqueta: string, valor: string, html?: bool}
     */
    public static function fila(string $etiqueta, mixed $valor, bool $html = false): array
    {
        $fila = [
            'etiqueta' => $etiqueta,
            'valor' => $html ? (string) $valor : self::texto($valor),
        ];

        if ($html) {
            $fila['html'] = true;
        }

        return $fila;
    }

    /**
     * @return array{etiqueta: string, valor: string, html?: bool}
     */
    public static function whatsapp(string $etiqueta, mixed $telefono, ?string $texto = null): array
    {
        $telefono = trim((string) $telefono);

        if ($telefono === '') {
            return self::fila($etiqueta, '—');
        }

        $digitos = preg_replace('/\D+/', '', $telefono) ?? '';

        if (str_starts_with($digitos, '0')) {
            $digitos = '593'.substr($digitos, 1);
        } elseif (strlen($digitos) === 9) {
            $digitos = '593'.$digitos;
        }

        if ($digitos === '') {
            return self::fila($etiqueta, $telefono);
        }

        $visible = e($texto ?: $telefono);

        return self::fila(
            $etiqueta,
            '<a class="link-whatsapp" href="https://wa.me/'.e($digitos).'" target="_blank" rel="noopener noreferrer">'.$visible.' <i class="bi bi-whatsapp" aria-hidden="true"></i></a>',
            true
        );
    }

    public static function contactosPresentacion(PresentacionNino $registro): string
    {
        $partes = [];

        foreach ([1, 2] as $numero) {
            $nombre = $numero === 1
                ? trim((string) $registro->nombre_padre)
                : trim((string) $registro->nombre_madre);
            $telefono = $numero === 1
                ? $registro->telefono_papa
                : $registro->telefono_mama;

            if (trim((string) $telefono) === '') {
                continue;
            }

            $etiqueta = $nombre !== ''
                ? $nombre
                : ($numero === 1 ? 'Representante' : 'Segundo representante');
            $fila = self::whatsapp('Teléfono', $telefono, $etiqueta);
            $partes[] = ! empty($fila['html']) ? $fila['valor'] : e($fila['valor']);
        }

        return $partes !== [] ? implode('<br>', $partes) : '—';
    }

    /**
     * @return array<int, array{etiqueta: string, valor: string, html?: bool}>
     */
    public static function meta(object $registro): array
    {
        $nombre = trim((string) ($registro->registrado_por_nombre ?? ''));

        if ($nombre === '') {
            $nombre = trim((string) ($registro->agente_usuario ?? ''));
        }

        if (str_starts_with($nombre, 'Sistema Web — ')) {
            $nombre = substr($nombre, strlen('Sistema Web — '));
        } elseif (str_starts_with($nombre, 'WordPress/')) {
            $nombre = 'Formulario público';
        }

        return [
            self::fila('Registrado por', $nombre !== '' ? $nombre : '—'),
            self::fila('Fecha y hora de registro', self::fechaHora($registro->creado_en ?? null)),
        ];
    }

    /**
     * @return array<int, array{etiqueta: string, valor: string, html?: bool}>
     */
    public static function inscripcion(Inscripcion $registro): array
    {
        $filas = [
            self::fila('Tipo', Permisos::etiquetas()[$registro->tipo_formulario] ?? $registro->tipo_formulario),
            self::fila('Nombre completo', trim($registro->nombre.' '.$registro->apellido)),
            self::whatsapp('Teléfono', $registro->celular),
            self::fila('Email', $registro->email),
        ];

        if ($registro->tipo_formulario === 'conexion' || trim((string) $registro->zona) !== '') {
            $filas[] = self::fila('Zona', Permisos::zonas()[$registro->zona] ?? $registro->zona);
            $filas[] = self::fila('Dirección', $registro->direccion);
        }

        if ($registro->tipo_formulario === 'conexion') {
            $filas[] = self::fila('Estado', $registro->contactado ? 'Contactado' : 'Pendiente');
        }

        if ($registro->tipo_formulario === 'bautismo') {
            $filas[] = self::fila('Estado', $registro->estado_bautismo === 'bautizado' ? 'Bautizado' : 'Ingresado');
            $filas[] = self::fila('Fecha de bautizo', self::fecha($registro->fecha_bautismo));
        }

        return array_merge($filas, self::meta($registro));
    }

    /**
     * @return array<int, array{etiqueta: string, valor: string, html?: bool}>
     */
    public static function presentacion(PresentacionNino $registro): array
    {
        $filas = [
            self::fila('Nombre del niño/a', $registro->nombre_presentado),
            self::fila('Fecha de nacimiento', self::fecha($registro->fecha_nacimiento)),
            self::fila('Edad', self::edad($registro->fecha_nacimiento)),
            self::fila('Representante', self::representante($registro, 1)),
            self::whatsapp('Teléfono representante', $registro->telefono_papa, $registro->nombre_padre),
        ];

        if (trim((string) $registro->nombre_madre) !== '' || trim((string) $registro->telefono_mama) !== '') {
            $filas[] = self::fila('Segundo representante', self::representante($registro, 2));
            $filas[] = self::whatsapp('Teléfono segundo representante', $registro->telefono_mama, $registro->nombre_madre);
        }

        $filas[] = self::fila('Estado', $registro->estado === 'presentado' ? 'Presentado' : 'Recibido');
        $filas[] = self::fila('Fecha de presentación', self::fecha($registro->fecha_presentacion));

        return array_merge($filas, self::meta($registro));
    }

    /**
     * @return array<int, array{etiqueta: string, valor: string, html?: bool}>
     */
    public static function ofrenda(Ofrenda $registro): array
    {
        return array_merge([
            self::fila('Casa de vida', $registro->casa_vida),
            self::fila('Líder', $registro->lider),
            self::fila('Fecha de ofrenda', self::fecha($registro->fecha_ofrenda)),
            self::fila('Monto', self::dinero($registro->monto)),
        ], self::meta($registro));
    }

    /**
     * @return array<int, array{etiqueta: string, valor: string, html?: bool}>
     */
    public static function consejeria(Consejeria $registro): array
    {
        $cita = '—';

        if ($registro->cita_fecha) {
            $cita = $registro->cita_fecha->format('d/m/Y');
            $hora = substr((string) $registro->cita_hora, 0, 5);

            if ($hora !== '') {
                $cita .= ' '.$hora;
            }
        }

        return array_merge([
            self::fila('Nombre completo', $registro->nombre_completo),
            self::whatsapp('Teléfono', $registro->telefono),
            self::fila('Tipo de consejería', Permisos::tiposConsejeria()[$registro->tipo_consejeria] ?? $registro->tipo_consejeria),
            self::fila('Años en CDA', (string) ((int) $registro->anio_en_cda)),
            self::fila('Primera consejería', $registro->primera_consejeria ? 'Sí' : 'No'),
            self::fila('Cita asignada', $cita),
        ], self::meta($registro));
    }

    /**
     * @return array<int, array{etiqueta: string, valor: string, html?: bool}>
     */
    public static function valor(ValorAdicional $registro): array
    {
        $filas = [
            self::fila('Tipo', $registro->tipo),
            self::fila('Nombre', $registro->nombre),
            self::whatsapp('Teléfono', $registro->telefono),
            self::fila('Fecha', self::fecha($registro->fecha)),
            self::fila('Valor', self::dinero($registro->valor)),
        ];

        if ($registro->evento_id || trim((string) $registro->tipo_entrada) !== '') {
            $filas[] = self::fila('Tipo de entrada', $registro->tipo_entrada);
            $filas[] = self::fila('Forma de pago', $registro->forma_pago);
            $filas[] = self::fila('Estado de pago', $registro->estado_pago);
            if ($registro->evento?->requiere_numeracion || trim((string) $registro->numeracion) !== '') {
                $filas[] = self::fila('Numeración', $registro->numeracion);
            }
            $filas[] = self::fila('Asistencia', $registro->asistio ? 'Asistió' : 'No asistió');
        }

        $filas[] = self::fila('Observación', $registro->observacion);

        return array_merge($filas, self::meta($registro));
    }

    /**
     * @return array<int, array{etiqueta: string, valor: string, html?: bool}>
     */
    public static function networking(NetworkingEmprendedor $registro): array
    {
        return array_merge([
            self::fila('Empresa', $registro->empresa),
            self::fila('Nombre', $registro->nombre),
            self::fila('Cargo', $registro->cargo),
            self::fila('Categoría', $registro->categoria),
            self::whatsapp('WhatsApp', $registro->whatsapp),
            self::fila('Correo', $registro->email),
            self::fila('Instagram personal', $registro->instagram_personal),
            self::fila('Instagram empresa', $registro->instagram_empresa),
            self::fila('Página web', $registro->pagina_web),
            self::fila('Ofrece', $registro->descripcion_ofrece),
            self::fila('Busca', $registro->descripcion_busca),
            self::fila('Autoriza directorio', $registro->autoriza_directorio ? 'Sí' : 'No'),
            self::fila('Visible', $registro->visible ? 'Visible' : 'Oculto'),
            self::fila('Origen', $registro->origen === 'panel' ? 'Panel' : 'Formulario público'),
        ], self::meta($registro));
    }

    /**
     * @return array<int, array{etiqueta: string, valor: string, html?: bool}>
     */
    public static function evento(Evento $registro): array
    {
        return [
            self::fila('Nombre', $registro->nombre),
            self::fila('Fecha', self::fecha($registro->fecha)),
            self::fila('Tipos de entrada', Eventos::formatearTipos($registro)),
            self::fila('Valor', self::dinero($registro->valor)),
            self::fila('Requiere numeración', $registro->requiere_numeracion ? 'Sí' : 'No'),
            self::fila('Estado', $registro->habilitado ? 'Habilitado' : 'Deshabilitado'),
            self::fila('Fecha de registro', self::fechaHora($registro->creado_en)),
        ];
    }

    /**
     * @return array<int, array{etiqueta: string, valor: string, html?: bool}>
     */
    public static function participante(ValorAdicional $registro): array
    {
        $filas = [
            self::fila('Nombre', $registro->nombre),
            self::whatsapp('Teléfono', $registro->telefono),
            self::fila('Evento', $registro->evento?->nombre),
            self::fila('Tipo de entrada', $registro->tipo_entrada),
        ];

        if ($registro->evento?->requiere_numeracion) {
            $filas[] = self::fila('Numeración', $registro->numeracion);
        }

        $filas = array_merge($filas, [
            self::fila('Valor', self::dinero($registro->valor)),
            self::fila('Forma de pago', Eventos::etiquetaForma($registro->forma_pago)),
            self::fila('Estado de pago', Eventos::etiquetaPago($registro->estado_pago)),
            self::fila('Asistencia', $registro->asistio ? 'Asistió' : 'No asistió'),
            self::fila('Fecha de registro', self::fecha($registro->fecha)),
        ]);

        foreach ($registro->respuestasAdicionales() as $respuesta) {
            $filas[] = self::fila((string) ($respuesta['etiqueta'] ?? 'Dato'), $respuesta['valor'] ?? '');
        }

        $filas[] = self::fila('Observación', $registro->observacion);

        return array_merge($filas, self::meta($registro));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function camposParticipante(ValorAdicional $registro): array
    {
        $campos = [
            self::campo('nombre', 'Nombre', 'text', $registro->nombre, true, 100),
            self::campo('telefono', 'Teléfono', 'tel', $registro->telefono, true, 30),
            self::campo('fecha', 'Fecha de registro', 'date', self::valorFecha($registro->fecha), true),
        ];

        if ($registro->evento?->requiere_numeracion) {
            $campos[] = self::campo('numeracion', 'Numeración', 'text', $registro->numeracion, true, 30);
        }

        $campos = array_merge($campos, [
            self::campo('valor', 'Valor', 'number', $registro->valor, true),
            self::campo('forma_pago', 'Forma de pago', 'select', $registro->forma_pago, false, null, Eventos::formasPago()),
            self::campo('estado_pago', 'Estado de pago', 'select', $registro->estado_pago ?: 'por_cancelar', true, null, Eventos::estadosPago()),
            self::campo('observacion', 'Observación', 'textarea', $registro->observacion, false, 500),
            self::campoEstatico('Evento', $registro->evento?->nombre ?: '—'),
            self::campoEstatico('Tipo de entrada', $registro->tipo_entrada ?: '—'),
        ]);

        foreach ($registro->respuestasAdicionales() as $respuesta) {
            $campos[] = self::campoEstatico((string) ($respuesta['etiqueta'] ?? 'Dato'), $respuesta['valor'] ?? '—');
        }

        return array_merge($campos, self::camposMeta($registro));
    }

    /**
     * @return array<int, array{etiqueta: string, valor: string, html?: bool}>
     */
    public static function calendario(CalendarioEvento $registro): array
    {
        return [
            self::fila('Título', $registro->titulo),
            self::fila('Descripción', $registro->descripcion),
            self::fila('Inicio', self::fecha($registro->fecha)),
            self::fila('Fin', self::fecha($registro->fecha_fin)),
            self::fila('Estado', $registro->activo ? 'Activo' : 'Inactivo'),
            self::fila('Fecha de registro', self::fechaHora($registro->creado_en)),
        ];
    }

    /**
     * @return array<int, array{etiqueta: string, valor: string, html?: bool}>
     */
    public static function usuario(User $usuario): array
    {
        return [
            self::fila('Usuario', $usuario->usuario),
            self::fila('Nombre', $usuario->nombre),
            self::fila('Rol', Permisos::etiquetasRoles()[$usuario->rolNormalizado()] ?? $usuario->rol),
            self::fila('Fecha de registro', self::fechaHora($usuario->creado_en)),
        ];
    }

    /**
     * @return array<int, array{etiqueta: string, valor: string, html?: bool}>
     */
    public static function actividad(object $fila): array
    {
        $usuario = trim((string) ($fila->usuario_nombre ?? ''));

        if ($usuario === '') {
            $usuario = trim((string) ($fila->usuario_login ?? ''));
        }

        $seccion = (string) ($fila->seccion ?? '');

        return [
            self::fila('Fecha y hora', self::fechaHora($fila->creado_en ?? null)),
            self::fila('Usuario', $usuario !== '' ? $usuario : '—'),
            self::fila('Acción', Actividad::etiquetaAccion((string) ($fila->accion ?? ''))),
            self::fila('Sección', Permisos::etiquetas()[$seccion] ?? ($seccion !== '' ? $seccion : '—')),
            self::fila('Detalle', $fila->detalle ?? ''),
        ];
    }

    /**
     * @param  array{origen?: string, modelo?: object, id?: int}  $registro
     * @return array<int, array{etiqueta: string, valor: string, html?: bool}>
     */
    public static function general(array $registro): array
    {
        $modelo = $registro['modelo'] ?? null;

        return match ($registro['origen'] ?? '') {
            'inscripcion' => $modelo instanceof Inscripcion ? self::inscripcion($modelo) : [],
            'presentacion' => $modelo instanceof PresentacionNino ? self::presentacion($modelo) : [],
            default => [],
        };
    }

    public static function tituloGeneral(array $registro): string
    {
        $prefijo = match ($registro['origen'] ?? '') {
            'inscripcion' => 'Inscripción',
            'presentacion' => 'Presentación',
            default => 'Registro',
        };

        return $prefijo.' #'.(int) ($registro['id'] ?? 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function camposInscripcion(Inscripcion $registro): array
    {
        $campos = [
            self::campo('nombre', 'Nombre', 'text', $registro->nombre, true, 100),
            self::campo('apellido', 'Apellido', 'text', $registro->apellido, true, 100),
            self::campo('celular', 'Teléfono', 'tel', $registro->celular, true, 30),
            self::campo('email', 'Correo electrónico', 'email', $registro->email, false, 100),
        ];

        if ($registro->tipo_formulario === 'conexion') {
            $campos[] = self::campo('zona', 'Zona', 'select', $registro->zona, true, null, Permisos::zonas());
            $campos[] = self::campo('direccion', 'Dirección', 'textarea', $registro->direccion, true, 255);
            $campos[] = self::campo('contactado', 'Estado', 'select', $registro->contactado ? '1' : '0', true, null, [
                '0' => 'Pendiente',
                '1' => 'Contactado',
            ]);
        }

        if ($registro->tipo_formulario === 'bautismo') {
            $campos[] = self::campoEstatico('Estado', $registro->estado_bautismo === 'bautizado' ? 'Bautizado' : 'Ingresado');
            $campos[] = self::campoEstatico('Fecha de bautizo', self::fecha($registro->fecha_bautismo));
        }

        $campos[] = self::campoEstatico('Tipo', Permisos::etiquetas()[$registro->tipo_formulario] ?? $registro->tipo_formulario);
        $campos = array_merge($campos, self::camposMeta($registro));

        return $campos;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function camposPresentacion(PresentacionNino $registro): array
    {
        $parentescos = Permisos::parentescos();
        $campos = [
            self::campo('nombre_presentado', 'Nombre del niño/a', 'text', $registro->nombre_presentado, true, 100),
            self::campo('fecha_nacimiento', 'Fecha de nacimiento', 'date', self::valorFecha($registro->fecha_nacimiento)),
            self::campoEstatico('Edad', self::edad($registro->fecha_nacimiento)),
            self::campo('parentesco_representante_1', 'Parentesco', 'select', self::claveOpcion((string) $registro->parentesco_representante_1, $parentescos), true, null, $parentescos),
            self::campo('nombre_padre', 'Representante legal', 'text', $registro->nombre_padre, true, 100),
            self::campo('telefono_papa', 'Teléfono representante', 'tel', $registro->telefono_papa, true, 30),
            self::campo('parentesco_representante_2', 'Segundo parentesco', 'select', self::claveOpcion((string) $registro->parentesco_representante_2, $parentescos), false, null, ['' => 'Seleccionar…'] + $parentescos),
            self::campo('nombre_madre', 'Segundo representante', 'text', $registro->nombre_madre, false, 100),
            self::campo('telefono_mama', 'Teléfono segundo representante', 'tel', $registro->telefono_mama, false, 30),
        ];

        if ($registro->estado === 'presentado') {
            $campos[] = self::campoEstatico('Estado', 'Presentado');
            $campos[] = self::campoEstatico('Fecha de presentación', self::fecha($registro->fecha_presentacion));
        } else {
            $campos[] = self::campo('estado', 'Estado', 'select', $registro->estado ?: 'recibido', true, null, [
                'recibido' => 'Recibido',
                'presentado' => 'Presentado',
            ]);
        }

        return array_merge($campos, self::camposMeta($registro));
    }

    /**
     * @param  array<int|string, string>  $casas
     * @return array<int, array<string, mixed>>
     */
    public static function camposOfrenda(Ofrenda $registro, array $casas = []): array
    {
        $lider = self::campo('lider', 'Líder', 'text', $registro->lider);
        $lider['readonly'] = true;

        return array_merge([
            self::campo('casa_id', 'Casa de vida', 'select', $registro->casa_id, true, null, ['' => 'Seleccionar…'] + $casas),
            $lider,
            self::campo('fecha_ofrenda', 'Fecha de ofrenda', 'date', self::valorFecha($registro->fecha_ofrenda), true),
            self::campo('monto', 'Monto', 'number', $registro->monto, true),
        ], self::camposMeta($registro));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function camposConsejeria(Consejeria $registro): array
    {
        $hora = substr((string) $registro->cita_hora, 0, 5);

        return array_merge([
            self::campo('nombre_completo', 'Nombre completo', 'text', $registro->nombre_completo, true, 200),
            self::campo('telefono', 'Teléfono', 'tel', $registro->telefono, true, 30),
            self::campo('tipo_consejeria', 'Tipo de consejería', 'select', $registro->tipo_consejeria, true, null, Permisos::tiposConsejeria()),
            self::campo('anio_en_cda', 'Años en CDA', 'number', $registro->anio_en_cda, true),
            self::campo('primera_consejeria', 'Primera consejería', 'select', $registro->primera_consejeria ? '1' : '0', true, null, [
                '1' => 'Sí',
                '0' => 'No',
            ]),
            self::campo('cita_fecha', 'Fecha de cita', 'date', self::valorFecha($registro->cita_fecha)),
            self::campo('cita_hora', 'Hora de cita', 'time', $hora),
        ], self::camposMeta($registro));
    }

    /**
     * @param  iterable<int, object>  $tipos
     * @return array<int, array<string, mixed>>
     */
    public static function camposValor(ValorAdicional $registro, iterable $tipos = []): array
    {
        $opciones = [];

        foreach ($tipos as $tipo) {
            $opciones[(string) $tipo->clave] = (string) $tipo->etiqueta;
        }

        $campoTipo = $opciones === []
            ? self::campo('tipo', 'Tipo', 'text', $registro->tipo, true, 50)
            : self::campo('tipo', 'Tipo', 'select', $registro->tipo, true, null, $opciones);

        return array_merge([
            $campoTipo,
            self::campo('nombre', 'Nombre', 'text', $registro->nombre, true, 100),
            self::campo('telefono', 'Teléfono', 'tel', $registro->telefono, true, 30),
            self::campo('fecha', 'Fecha', 'date', self::valorFecha($registro->fecha), true),
            self::campo('valor', 'Valor', 'number', $registro->valor, true),
            self::campo('observacion', 'Observación', 'textarea', $registro->observacion),
        ], self::camposMeta($registro));
    }

    /**
     * @return array<int, array{etiqueta: string, valor: string, html?: bool}>
     */
    public static function miembro(Lider $registro): array
    {
        return array_merge([
            self::fila('Nombres', $registro->nombre),
            self::fila('Apellidos', $registro->apellido),
            self::fila('Género', $registro->genero === 'femenino' ? 'Femenino' : ($registro->genero === 'masculino' ? 'Masculino' : $registro->genero)),
            self::fila('Cédula', $registro->cedula),
            self::whatsapp('Celular', $registro->celular),
            self::fila('Correo', $registro->email),
            self::fila('Notas', $registro->notas),
        ], self::meta($registro));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function camposMiembro(Lider $registro): array
    {
        return array_merge([
            self::campo('nombre', 'Nombres', 'text', $registro->nombre, true, 100),
            self::campo('apellido', 'Apellidos', 'text', $registro->apellido, true, 100),
            self::campo('genero', 'Género', 'select', $registro->genero, true, null, [
                'masculino' => 'Masculino',
                'femenino' => 'Femenino',
            ]),
            self::campo('cedula', 'Cédula', 'text', $registro->cedula, false, 30),
            self::campo('celular', 'Celular', 'tel', $registro->celular, false, 30),
            self::campo('email', 'Correo', 'email', $registro->email, false, 100),
            self::campo('notas', 'Notas', 'textarea', $registro->notas, false, 500),
        ], self::camposMeta($registro));
    }

    /**
     * @return array<int, array{etiqueta: string, valor: string, html?: bool}>
     */
    public static function territorio(Territorio $registro): array
    {
        return array_merge([
            self::fila('Categoría', $registro->nombreCategoria()),
            self::fila('Ministerio', $registro->nombre),
            self::fila('Coordinadores', implode(', ', $registro->nombresRol('coordinador')) ?: 'Sin asignar'),
            self::fila('Encargados', implode(', ', $registro->nombresRol('encargado')) ?: 'Sin asignar'),
        ], self::meta($registro));
    }

    /**
     * @param  array<int, string>  $categorias
     * @return array<int, array<string, mixed>>
     */
    public static function camposTerritorio(Territorio $registro, array $categorias = []): array
    {
        return array_merge([
            self::campo('categoria_id', 'Categoría', 'select', $registro->categoriaId(), false, null, [0 => 'Sin asignar'] + $categorias),
            self::campo('nombre', 'Ministerio', 'text', $registro->nombre, true, 100),
            self::campo('categoria_nueva', 'Nueva categoría', 'text', '', false, 100),
        ], self::camposMeta($registro));
    }

    /**
     * @return array<int, array{etiqueta: string, valor: string, html?: bool}>
     */
    public static function casa(CasaVida $registro): array
    {
        return array_merge([
            self::fila('Casa', $registro->nombre),
            self::fila('Ministerio', $registro->territorio?->nombre),
            self::fila('Líder', $registro->lider?->nombreCompleto()),
            self::fila('Colaborador', $registro->colaborador?->nombreCompleto()),
            self::fila('Anfitrión', $registro->anfitrion?->nombreCompleto()),
            self::fila('Dirección', $registro->direccion),
        ], self::meta($registro));
    }

    /**
     * @param  array<int, string>  $territorios
     * @param  array<int, string>  $miembros
     * @return array<int, array<string, mixed>>
     */
    public static function camposCasa(CasaVida $registro, array $territorios, array $miembros): array
    {
        $miembrosOpcional = [0 => 'Ninguno'] + $miembros;

        if ($registro->territorio_id && ! isset($territorios[$registro->territorio_id]) && $registro->territorio) {
            $territorios = [$registro->territorio_id => $registro->territorio->nombre] + $territorios;
        }

        return array_merge([
            self::campo('nombre', 'Nombre de la casa', 'text', $registro->nombre, true, 100),
            self::campo('territorio_id', 'Ministerio', 'select', $registro->territorio_id, true, null, $territorios),
            self::campo('lider_id', 'Líder', 'select', $registro->lider_id, true, null, $miembros),
            self::campo('colaborador_id', 'Colaborador', 'select', $registro->colaborador_id ?: 0, false, null, $miembrosOpcional),
            self::campo('anfitrion_id', 'Anfitrión', 'select', $registro->anfitrion_id ?: 0, false, null, $miembrosOpcional),
            self::campo('direccion', 'Dirección', 'text', $registro->direccion, true, 255),
        ], self::camposMeta($registro));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function camposGeneral(array $registro): array
    {
        $modelo = $registro['modelo'] ?? null;

        return match ($registro['origen'] ?? '') {
            'inscripcion' => $modelo instanceof Inscripcion ? self::camposInscripcion($modelo) : [],
            'presentacion' => $modelo instanceof PresentacionNino ? self::camposPresentacion($modelo) : [],
            default => [],
        };
    }

    /**
     * @param  array<string, string>  $opciones
     * @return array<string, mixed>
     */
    private static function campo(string $name, string $label, string $type, mixed $value, bool $required = false, ?int $maxlength = null, array $opciones = []): array
    {
        return [
            'name' => $name,
            'label' => $label,
            'type' => $type,
            'value' => $value ?? '',
            'required' => $required,
            'maxlength' => $maxlength,
            'options' => $opciones,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function campoEstatico(string $label, mixed $valor): array
    {
        return [
            'name' => null,
            'label' => $label,
            'type' => 'static',
            'value' => self::texto($valor),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function camposMeta(object $registro): array
    {
        $filas = self::meta($registro);

        return array_map(
            fn (array $fila) => self::campoEstatico($fila['etiqueta'], $fila['valor']),
            $filas
        );
    }

    private static function valorFecha(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '';
        }

        try {
            return Carbon::parse($valor)->toDateString();
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * @param  array<string, string>  $opciones
     */
    private static function claveOpcion(string $valor, array $opciones): string
    {
        if ($valor === '' || isset($opciones[$valor])) {
            return $valor;
        }

        $buscado = mb_strtolower($valor);

        foreach ($opciones as $clave => $etiqueta) {
            if (mb_strtolower((string) $clave) === $buscado || mb_strtolower((string) $etiqueta) === $buscado) {
                return (string) $clave;
            }
        }

        return $valor;
    }

    private static function edad(mixed $fecha): string
    {
        if ($fecha === null || $fecha === '') {
            return '—';
        }

        try {
            $anios = Carbon::parse($fecha)->age;

            return $anios.' '.($anios === 1 ? 'año' : 'años');
        } catch (\Throwable) {
            return '—';
        }
    }

    private static function representante(PresentacionNino $registro, int $numero): string
    {
        $parentesco = $numero === 1
            ? (string) $registro->parentesco_representante_1
            : (string) $registro->parentesco_representante_2;
        $nombre = $numero === 1
            ? trim((string) $registro->nombre_padre)
            : trim((string) $registro->nombre_madre);
        $etiqueta = Permisos::parentescos()[$parentesco] ?? $parentesco;

        if ($nombre === '') {
            return self::texto($etiqueta);
        }

        return $etiqueta !== '' ? $nombre.' ('.$etiqueta.')' : $nombre;
    }
}
