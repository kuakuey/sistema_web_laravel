# Sistema Web CDA (Laravel)

Reescritura del **Sistema Web** de CDA (PHP puro + Bootstrap) a **Laravel + Tailwind**.

Panel interno de la iglesia: interfaz y mensajes en español. Zona horaria: `America/Guayaquil`.

Laravel usa **la misma base MySQL** que el sistema legado. No se recrea el esquema; se mapean tablas existentes con Eloquent (`ModeloCda`, timestamps `creado_en`, etc.).

## Tecnologías

### Backend
| Tecnología | Uso |
|---|---|
| **PHP** `^8.2` | Runtime del servidor |
| **Laravel** `^12` | Framework principal (rutas, controladores, middleware, auth) |
| **Eloquent ORM** | Modelos sobre tablas legadas (`usuarios`, `presentaciones_ninos`, etc.) |
| **MySQL** | Base de datos compartida con el sistema PHP anterior |
| **Blade** | Plantillas del panel |
| **Composer** | Dependencias PHP |

### Frontend
| Tecnología | Uso |
|---|---|
| **Tailwind CSS** `^4` | Estilos del panel (tema claro/oscuro) |
| **Vite** `^7` | Empaquetado de CSS/JS (`npm run build` / `npm run dev`) |
| **Alpine.js** `^3` | Interactividad ligera (notificaciones y UI) |
| **Axios** | Cliente HTTP (configuración base del proyecto) |
| **JavaScript (ES modules)** | AJAX global, presentaciones, eventos, estructura |
| **npm** | Dependencias front |

### Librerías y paquetes
| Paquete | Uso |
|---|---|
| **mckenziearts/laravel-notify** | Toasts / notificaciones flash |
| **mckenziearts/blade-untitledui-icons** | Iconos SVG en Blade (dependencia de Notify) |
| **blade-ui-kit/blade-icons** | Infraestructura de iconos Blade |
| **dompdf/dompdf** | Generación de PDF (informes) |

### Entorno y herramientas de desarrollo
| Tecnología | Uso |
|---|---|
| **XAMPP** (Apache + MySQL + PHP) | Entorno local típico del proyecto |
| **Laravel Pint** | Formato de código PHP |
| **PHPUnit** | Pruebas |
| **Laravel Pail / Sail / Collision** | Utilidades de desarrollo Laravel |

### Integraciones
| Tecnología | Uso |
|---|---|
| **API REST** (`routes/api.php`) | Formularios públicos / plugin WordPress (`X-API-KEY`) |
| **Fetch AJAX** | Envío de formularios del panel sin recarga completa |

## Requisitos

- PHP 8.2+ (Notify 3.x declara PHP 8.3; en este entorno se instaló con `--ignore-platform-req=php`)
- Composer
- Node.js + npm
- MySQL (misma BD del sistema legado)
- Extensiones PHP habituales de Laravel (pdo_mysql, mbstring, openssl, etc.)

## Instalación y actualización

Guía completa (cPanel + local, primera vez y cada deploy): **[INSTALACION.md](INSTALACION.md)**

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
npm run build
```

Configura en `.env` la conexión MySQL compartida. **No ejecutes** `php artisan migrate` para recrear el esquema: las tablas ya existen.

## Scripts útiles

```bash
composer run dev   # servidor + cola + logs + Vite
npm run build      # assets de producción
npm run dev        # Vite en desarrollo
```

## Licencia

Código de aplicación interno de CDA. El framework Laravel se distribuye bajo [MIT](https://opensource.org/licenses/MIT).
