<?php

namespace App\Support;

use App\Models\NetworkingEmprendedor;
use Illuminate\Http\UploadedFile;
use InvalidArgumentException;

class ApiNetworking
{
    public const UPLOAD_DIR = 'uploads/networking';

    public const LOGO_MAX_BYTES = 2097152;

    /**
     * @return array<string, string>
     */
    public static function categorias(): array
    {
        return [
            'alimentos' => 'Alimentos',
            'construccion' => 'Construcción',
            'marketing' => 'Marketing',
            'salud' => 'Salud',
            'belleza' => 'Belleza',
            'tecnologia' => 'Tecnología',
            'servicios_profesionales' => 'Servicios profesionales',
            'educacion' => 'Educación',
            'otros' => 'Otros',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function opcionesOfrece(): array
    {
        return [
            'productos' => 'Productos',
            'servicios' => 'Servicios',
            'asesoria' => 'Asesoría / Conocimiento',
            'experiencia' => 'Experiencia',
            'otro' => 'Otro (especifica)',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function opcionesBusca(): array
    {
        return [
            'clientes' => 'Clientes',
            'proveedores' => 'Proveedores',
            'socios' => 'Socios estratégicos',
            'alianzas' => 'Alianzas comerciales',
            'inversion' => 'Inversión',
            'servicios_empresa' => 'Servicios para mi empresa',
            'otro' => 'Otro (especifica)',
        ];
    }

    /**
     * @param  array<string, string>  $mapa
     * @return array<int, array{clave: string, etiqueta: string}>
     */
    public static function catalogo(array $mapa): array
    {
        $lista = [];

        foreach ($mapa as $clave => $etiqueta) {
            $lista[] = [
                'clave' => $clave,
                'etiqueta' => $etiqueta,
            ];
        }

        return $lista;
    }

    /**
     * @param  array{buscar?: string, categoria?: string}  $filtros
     * @return array<int, array<string, mixed>>
     */
    public static function buscarPublicos(array $filtros, int $limite = 500): array
    {
        $consulta = NetworkingEmprendedor::query()
            ->where('visible', 1)
            ->where('autoriza_directorio', 1);

        $categoria = trim((string) ($filtros['categoria'] ?? ''));
        if ($categoria !== '' && isset(self::categorias()[$categoria])) {
            $consulta->where('categoria', $categoria);
        }

        $buscar = trim((string) ($filtros['buscar'] ?? ''));
        if ($buscar !== '') {
            $like = '%'.addcslashes($buscar, '%_\\').'%';
            $consulta->where(function ($grupo) use ($like) {
                $grupo->where('empresa', 'like', $like)
                    ->orWhere('nombre', 'like', $like)
                    ->orWhere('cargo', 'like', $like)
                    ->orWhere('descripcion_ofrece', 'like', $like)
                    ->orWhere('descripcion_busca', 'like', $like)
                    ->orWhere('email', 'like', $like);
            });
        }

        return $consulta
            ->orderBy('empresa')
            ->orderByDesc('id')
            ->limit(max(1, min($limite, 500)))
            ->get()
            ->map(fn (NetworkingEmprendedor $fila) => self::presentarPublico($fila))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function presentarPublico(NetworkingEmprendedor $fila): array
    {
        $instagram = trim((string) ($fila->instagram_empresa ?? ''));
        if ($instagram === '') {
            $instagram = trim((string) ($fila->instagram_personal ?? ''));
        }

        return [
            'id' => (int) $fila->id,
            'empresa' => (string) $fila->empresa,
            'nombre' => (string) $fila->nombre,
            'cargo' => (string) $fila->cargo,
            'categoria' => (string) $fila->categoria,
            'categoria_etiqueta' => self::categorias()[(string) $fila->categoria] ?? (string) $fila->categoria,
            'iniciales' => self::iniciales((string) $fila->empresa),
            'logo_url' => self::urlAbsolutaLogo($fila->logo),
            'ofrece' => (string) $fila->descripcion_ofrece,
            'busca' => (string) $fila->descripcion_busca,
            'whatsapp_url' => self::whatsappUrl((string) $fila->whatsapp),
            'instagram_url' => self::instagramUrl($instagram),
        ];
    }

    /**
     * @param  array<string, mixed>  $entrada
     * @return array<string, mixed>
     */
    public static function armarDesdeEntrada(array $entrada, ?UploadedFile $archivoLogo, bool $autorizacionObligatoria, string $origen): array
    {
        $datos = self::validar($entrada, $autorizacionObligatoria);
        $datos['origen'] = $origen;
        $datos['visible'] = ! empty($entrada['visible']) && (string) $entrada['visible'] !== '0' ? 1 : 0;
        $datos['logo'] = null;
        $datos['logo_nuevo'] = null;
        $datos['logo_anterior'] = null;

        if ($archivoLogo instanceof UploadedFile && $archivoLogo->isValid()) {
            $datos['logo_nuevo'] = self::guardarLogoArchivo($archivoLogo);
            $datos['logo'] = $datos['logo_nuevo'];
        } elseif (! empty($entrada['logo_base64'])) {
            $datos['logo_nuevo'] = self::guardarLogoBase64((string) $entrada['logo_base64']);
            $datos['logo'] = $datos['logo_nuevo'];
        }

        return $datos;
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public static function insertar(array $datos): int
    {
        if (self::existeDuplicado((string) $datos['email'], (string) $datos['empresa'])) {
            throw new InvalidArgumentException('Este negocio ya está registrado con ese correo.');
        }

        $registro = NetworkingEmprendedor::query()->create([
            'nombre' => $datos['nombre'],
            'whatsapp' => $datos['whatsapp'],
            'email' => $datos['email'],
            'instagram_personal' => $datos['instagram_personal'],
            'empresa' => $datos['empresa'],
            'cargo' => $datos['cargo'],
            'categoria' => $datos['categoria'],
            'instagram_empresa' => $datos['instagram_empresa'],
            'pagina_web' => $datos['pagina_web'],
            'logo' => $datos['logo'] ?? null,
            'ofrece' => $datos['ofrece'],
            'ofrece_otro' => $datos['ofrece_otro'],
            'descripcion_ofrece' => $datos['descripcion_ofrece'],
            'busca' => $datos['busca'],
            'busca_otro' => $datos['busca_otro'],
            'descripcion_busca' => $datos['descripcion_busca'],
            'autoriza_directorio' => (int) $datos['autoriza_directorio'],
            'visible' => isset($datos['visible']) ? (int) $datos['visible'] : 1,
            'origen' => (string) ($datos['origen'] ?? 'publico'),
            'registrado_por_id' => $datos['registrado_por_id'] ?? null,
            'registrado_por_nombre' => $datos['registrado_por_nombre'] ?? null,
            'ip_cliente' => $datos['ip_cliente'] ?? null,
            'agente_usuario' => $datos['agente_usuario'] ?? null,
        ]);

        return (int) $registro->id;
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public static function confirmarLogo(array $datos, bool $exito): void
    {
        if ($exito && ! empty($datos['logo_anterior']) && ($datos['logo_anterior'] !== ($datos['logo'] ?? null))) {
            self::eliminarLogo((string) $datos['logo_anterior']);

            return;
        }

        if (! $exito && ! empty($datos['logo_nuevo'])) {
            self::eliminarLogo((string) $datos['logo_nuevo']);
        }
    }

    /**
     * @param  array<string, mixed>  $entrada
     * @return array<string, mixed>
     */
    private static function validar(array $entrada, bool $autorizacionObligatoria): array
    {
        $nombre = self::texto((string) ($entrada['nombre'] ?? ''), 150, true, 'Nombre y apellido', true);
        $whatsapp = self::whatsapp((string) ($entrada['whatsapp'] ?? ''));
        $email = self::email((string) ($entrada['email'] ?? ''));
        $instagramPersonal = self::instagram((string) ($entrada['instagram_personal'] ?? ''), 'Instagram personal');
        $empresa = self::texto((string) ($entrada['empresa'] ?? ''), 200, true, 'Nombre de empresa o emprendimiento');
        $cargo = self::texto((string) ($entrada['cargo'] ?? ''), 150, true, 'Cargo / profesión');
        $categoria = trim((string) ($entrada['categoria'] ?? ''));
        $instagramEmpresa = self::instagram((string) ($entrada['instagram_empresa'] ?? ''), 'Instagram de la empresa');
        $paginaWeb = self::paginaWeb((string) ($entrada['pagina_web'] ?? ''));

        if (! isset(self::categorias()[$categoria])) {
            throw new InvalidArgumentException('Selecciona una categoría o sector.');
        }

        $ofrece = self::normalizarLista($entrada['ofrece'] ?? [], self::opcionesOfrece());
        $busca = self::normalizarLista($entrada['busca'] ?? [], self::opcionesBusca());

        if ($ofrece === []) {
            throw new InvalidArgumentException('Selecciona al menos una opción en qué ofreces.');
        }

        if ($busca === []) {
            throw new InvalidArgumentException('Selecciona al menos una opción en qué estás buscando.');
        }

        $ofreceOtro = self::texto((string) ($entrada['ofrece_otro'] ?? ''), 150, false, 'Otro, qué ofreces');
        $buscaOtro = self::texto((string) ($entrada['busca_otro'] ?? ''), 150, false, 'Otro, qué buscas');

        if (in_array('otro', $ofrece, true) && $ofreceOtro === '') {
            throw new InvalidArgumentException('Especifica qué más ofrece tu empresa.');
        }

        if (in_array('otro', $busca, true) && $buscaOtro === '') {
            throw new InvalidArgumentException('Especifica qué más estás buscando.');
        }

        $descripcionOfrece = self::texto((string) ($entrada['descripcion_ofrece'] ?? ''), 600, true, 'Descripción de lo que ofrece');
        $descripcionBusca = self::texto((string) ($entrada['descripcion_busca'] ?? ''), 600, true, 'Descripción de lo que busca');

        $autoriza = ! empty($entrada['autoriza_directorio']) && (string) $entrada['autoriza_directorio'] !== '0';

        if ($autorizacionObligatoria && ! $autoriza) {
            throw new InvalidArgumentException('Debes autorizar que tus datos comerciales sean visibles en el directorio.');
        }

        return [
            'nombre' => $nombre,
            'whatsapp' => $whatsapp,
            'email' => $email,
            'instagram_personal' => $instagramPersonal !== '' ? $instagramPersonal : null,
            'empresa' => $empresa,
            'cargo' => $cargo,
            'categoria' => $categoria,
            'instagram_empresa' => $instagramEmpresa !== '' ? $instagramEmpresa : null,
            'pagina_web' => $paginaWeb !== '' ? $paginaWeb : null,
            'ofrece' => json_encode($ofrece, JSON_UNESCAPED_UNICODE),
            'ofrece_otro' => $ofreceOtro !== '' ? $ofreceOtro : null,
            'descripcion_ofrece' => $descripcionOfrece,
            'busca' => json_encode($busca, JSON_UNESCAPED_UNICODE),
            'busca_otro' => $buscaOtro !== '' ? $buscaOtro : null,
            'descripcion_busca' => $descripcionBusca,
            'autoriza_directorio' => $autoriza ? 1 : 0,
        ];
    }

    /**
     * @param  mixed  $valor
     * @param  array<string, string>  $permitidas
     * @return array<int, string>
     */
    private static function normalizarLista($valor, array $permitidas): array
    {
        if (is_string($valor) && $valor !== '') {
            $decodificado = json_decode($valor, true);
            $valor = is_array($decodificado) ? $decodificado : [$valor];
        }

        if (! is_array($valor)) {
            return [];
        }

        $claves = [];

        foreach ($valor as $item) {
            $clave = trim((string) $item);

            if ($clave !== '' && isset($permitidas[$clave]) && ! in_array($clave, $claves, true)) {
                $claves[] = $clave;
            }
        }

        return $claves;
    }

    private static function texto(string $valor, int $max, bool $requerido, string $etiqueta, bool $soloNombre = false): string
    {
        $valor = str_replace(["\r\n", "\r"], "\n", $valor);
        $valor = trim($valor);

        if ($valor === '') {
            if ($requerido) {
                throw new InvalidArgumentException('Completa el campo '.$etiqueta.'.');
            }

            return '';
        }

        if (mb_strlen($valor) > $max) {
            throw new InvalidArgumentException($etiqueta.' es demasiado largo.');
        }

        if (preg_match('/[<>]|javascript:|<\?php/i', $valor)) {
            throw new InvalidArgumentException('El texto de '.$etiqueta.' no es válido.');
        }

        $patron = $soloNombre
            ? '/^[\p{L}\p{M}\s\'\-\.]+$/u'
            : '/^[\p{L}\p{M}\p{N}\p{P}\p{S}\s]+$/u';

        if (! preg_match($patron, $valor)) {
            throw new InvalidArgumentException('Revisa el texto de '.$etiqueta.'.');
        }

        return $valor;
    }

    private static function email(string $valor): string
    {
        $valor = strtolower(trim($valor));

        if ($valor === '' || ! filter_var($valor, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Ingresa un correo electrónico válido.');
        }

        if (mb_strlen($valor) > 150) {
            throw new InvalidArgumentException('El correo es demasiado largo.');
        }

        return $valor;
    }

    private static function whatsapp(string $valor): string
    {
        $valor = trim($valor);

        if ($valor === '' || ! preg_match('/^[\d\s\(\)\+\-\.]{6,30}$/', $valor)) {
            throw new InvalidArgumentException('Ingresa un número de WhatsApp válido.');
        }

        $digitos = preg_replace('/\D/', '', $valor) ?? '';

        if (strlen($digitos) < 6) {
            throw new InvalidArgumentException('Ingresa un número de WhatsApp válido.');
        }

        return $valor;
    }

    private static function instagram(string $valor, string $etiqueta): string
    {
        $valor = trim($valor);

        if ($valor === '') {
            return '';
        }

        if (mb_strlen($valor) > 150) {
            throw new InvalidArgumentException($etiqueta.' es demasiado largo.');
        }

        if (preg_match('#^https?://#i', $valor)) {
            if (! filter_var($valor, FILTER_VALIDATE_URL) || stripos($valor, 'instagram.com') === false) {
                throw new InvalidArgumentException('Ingresa un Instagram válido.');
            }

            return $valor;
        }

        $handle = ltrim($valor, '@');

        if (! preg_match('/^[A-Za-z0-9._]{1,30}$/', $handle)) {
            throw new InvalidArgumentException('Ingresa un usuario de Instagram válido.');
        }

        return '@'.$handle;
    }

    private static function paginaWeb(string $valor): string
    {
        $valor = trim($valor);

        if ($valor === '') {
            return '';
        }

        if (! preg_match('#^https?://#i', $valor)) {
            $valor = 'https://'.$valor;
        }

        if (! filter_var($valor, FILTER_VALIDATE_URL) || mb_strlen($valor) > 255) {
            throw new InvalidArgumentException('Ingresa una página web válida.');
        }

        return $valor;
    }

    private static function existeDuplicado(string $email, string $empresa, int $exceptoId = 0): bool
    {
        $consulta = NetworkingEmprendedor::query()
            ->where('email', $email)
            ->where('empresa', $empresa);

        if ($exceptoId > 0) {
            $consulta->where('id', '<>', $exceptoId);
        }

        return $consulta->exists();
    }

    private static function iniciales(string $empresa): string
    {
        $partes = preg_split('/\s+/u', trim($empresa)) ?: [];
        $letras = '';

        foreach ($partes as $parte) {
            if ($parte === '') {
                continue;
            }

            $letras .= mb_strtoupper(mb_substr($parte, 0, 1));

            if (mb_strlen($letras) >= 2) {
                break;
            }
        }

        return $letras !== '' ? $letras : 'NE';
    }

    private static function whatsappUrl(string $telefono): string
    {
        $digitos = preg_replace('/\D/', '', $telefono) ?? '';

        if (str_starts_with($digitos, '00')) {
            $digitos = substr($digitos, 2);
        }

        if (strlen($digitos) === 10 && ($digitos[0] ?? '') === '0') {
            $digitos = '593'.substr($digitos, 1);
        } elseif (strlen($digitos) === 9 && ($digitos[0] ?? '') === '9') {
            $digitos = '593'.$digitos;
        }

        return $digitos === '' ? '' : 'https://wa.me/'.$digitos;
    }

    private static function instagramUrl(?string $valor): string
    {
        $valor = trim((string) $valor);

        if ($valor === '') {
            return '';
        }

        if (preg_match('#^https?://#i', $valor)) {
            return preg_match('#^https://#i', $valor) ? $valor : '';
        }

        $handle = ltrim($valor, '@');

        if (! preg_match('/^[A-Za-z0-9._]{1,30}$/', $handle)) {
            return '';
        }

        return 'https://instagram.com/'.rawurlencode($handle);
    }

    private static function urlAbsolutaLogo(?string $rutaRelativa): string
    {
        $rutaRelativa = str_replace('\\', '/', trim((string) $rutaRelativa));

        if ($rutaRelativa === '' || str_contains($rutaRelativa, '..')) {
            return '';
        }

        if (preg_match('#^https?://#i', $rutaRelativa)) {
            return $rutaRelativa;
        }

        if (! str_starts_with($rutaRelativa, self::UPLOAD_DIR.'/')) {
            return '';
        }

        return rtrim((string) config('app.url'), '/').'/'.ltrim($rutaRelativa, '/');
    }

    private static function guardarLogoArchivo(UploadedFile $archivo): string
    {
        $contenido = file_get_contents($archivo->getRealPath());

        if ($contenido === false) {
            throw new InvalidArgumentException('No se pudo leer el logo.');
        }

        return self::guardarLogoContenido($contenido);
    }

    private static function guardarLogoBase64(string $base64): string
    {
        $base64 = trim($base64);

        if (str_contains($base64, ',') && stripos($base64, 'base64') !== false) {
            $base64 = (string) substr($base64, (int) strpos($base64, ',') + 1);
        }

        $binario = base64_decode($base64, true);

        if ($binario === false) {
            throw new InvalidArgumentException('El logo no es válido.');
        }

        return self::guardarLogoContenido($binario);
    }

    private static function guardarLogoContenido(string $binario): string
    {
        if ($binario === '' || strlen($binario) > self::LOGO_MAX_BYTES) {
            throw new InvalidArgumentException('El logo debe pesar como máximo 2 MB.');
        }

        $info = @getimagesizefromstring($binario);
        $tipo = is_array($info) ? (int) ($info[2] ?? 0) : 0;
        $mapa = [
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_GIF => 'gif',
        ];

        if (defined('IMAGETYPE_WEBP')) {
            $mapa[IMAGETYPE_WEBP] = 'webp';
        }

        if (! isset($mapa[$tipo])) {
            throw new InvalidArgumentException('El logo debe ser JPG, PNG, WEBP o GIF.');
        }

        $directorio = public_path(self::UPLOAD_DIR);

        if (! is_dir($directorio) && ! @mkdir($directorio, 0775, true) && ! is_dir($directorio)) {
            throw new InvalidArgumentException('No se pudo crear la carpeta de logos. Verifica permisos de uploads/networking.');
        }

        $nombre = 'logo_'.bin2hex(random_bytes(8)).'.'.$mapa[$tipo];
        $destino = $directorio.DIRECTORY_SEPARATOR.$nombre;

        if (@file_put_contents($destino, $binario) === false) {
            throw new InvalidArgumentException('No se pudo guardar el logo.');
        }

        return self::UPLOAD_DIR.'/'.$nombre;
    }

    private static function eliminarLogo(?string $rutaRelativa): void
    {
        $rutaRelativa = str_replace('\\', '/', trim((string) $rutaRelativa));

        if ($rutaRelativa === '' || str_contains($rutaRelativa, '..')) {
            return;
        }

        if (! str_starts_with($rutaRelativa, self::UPLOAD_DIR.'/')) {
            return;
        }

        $absoluta = public_path($rutaRelativa);

        if (is_file($absoluta)) {
            @unlink($absoluta);
        }
    }
}
