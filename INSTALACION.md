# Cómo actualizar el Sistema Web CDA (Git + cPanel)

Flujo: **cambias código en tu Mac → subes a Git → en cPanel traes los cambios → recargas librerías y cachés.**

Haz **un paso a la vez**. Copia y pega los comandos.

Carpeta del proyecto en el servidor:

```text
~/www/sistema-web.iglesiacasadeavivamiento.com
```

---

# PARTE 1 — En tu Mac (local)

## Paso 1 — Compila los assets (el servidor NO tiene Node)

Solo si cambiaste CSS, JS o vistas con estilos:

```bash
cd RUTA/A/TU/PROYECTO
npm install
npm run build
```

Comprueba que existe `public/build` con `manifest.json` y `assets`.

## Paso 2 — Sube a Git

```bash
git status
git add .
git commit -m "Describe el cambio aquí"
git push
```

### ¿`public/build` se sube con Git?

Por defecto Laravel lo ignora (`.gitignore`). Tienes dos opciones:

**Opción A (recomendada): incluirlo en Git.** Una sola vez:

```bash
git add -f public/build
git commit -m "Incluir public/build"
git push
```

Desde ahí, cada `npm run build` cambiará esos archivos y viajarán con `git push`.
Si borras o renombras archivos de `build`, usa `git add -A public/build`.

**Opción B: subirlo a mano.** Comprime `public/build` en `build.zip`, súbelo por File Manager a `public/` y extráelo (sobrescribiendo). Verifica que no quede `build/build`.

---

# PARTE 2 — En cPanel

## Paso 3 — Traer los cambios con Git Version Control

1. cPanel → **Git™ Version Control**.
2. Junto al repositorio, clic en **Manage**.
3. Pestaña **Pull or Deploy**.
4. Clic en **Update from Remote**.
5. Revisa que el último commit mostrado sea el que acabas de subir.

> **Si da error "uncommitted changes" o no deja hacer pull:**
> hay archivos modificados directamente en el servidor. Revisa en la terminal:
>
> ```bash
> cd ~/www/sistema-web.iglesiacasadeavivamiento.com
> git status
> ```
>
> No borres `.env` ni `storage`. Si el problema es algún archivo que tú no tocaste, dime qué muestra antes de usar `git checkout` o `git reset`.

> **Nota:** el botón **Deploy HEAD Commit** solo se usa si tu repo tiene un archivo `.cpanel.yml`. Si el repo vive directamente dentro de `www/sistema-web...`, con **Update from Remote** basta.

---

# PARTE 3 — En la terminal de cPanel

## Paso 4 — Entra a la carpeta

```bash
cd ~/www/sistema-web.iglesiacasadeavivamiento.com
```

## Paso 5 — Modo mantenimiento (recomendado)

```bash
php artisan down
```

## Paso 6 — Instalar librerías (solo si cambió `composer.json` o `composer.lock`)

Si no tocaste librerías, puedes saltar este paso. En caso de duda, córrelo, no hace daño:

```bash
php -d allow_url_fopen=On -d memory_limit=-1 composer.phar install --no-dev --optimize-autoloader --ignore-platform-reqs
```

> `--ignore-platform-reqs` es necesario porque el PHP de la terminal no tiene la extensión `fileinfo`. La web sí la tiene, es seguro.

**Si dice `Could not open input file: composer.phar`** (por ejemplo, porque se borró), reinstala Composer:

```bash
curl -sS https://getcomposer.org/installer -o composer-setup.php
php -d allow_url_fopen=On composer-setup.php
rm composer-setup.php
```

y repite el comando de instalación.

## Paso 7 — Limpiar y recargar cachés

```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Paso 8 — Permisos

```bash
chmod -R 775 storage bootstrap/cache
```

## Paso 9 — Quitar mantenimiento

```bash
php artisan up
```

## Paso 10 — Probar

1. Abre `https://sistema-web.iglesiacasadeavivamiento.com`
2. Recarga con **Cmd + Shift + R** (Mac) para saltar el caché del navegador.
3. Prueba el login y la pantalla que cambiaste.

Si hay error 500:

```bash
tail -50 storage/logs/laravel.log
```

---

# CHEAT SHEET

## En tu Mac

```bash
npm run build
git add .            # y si hace falta: git add -f public/build
git commit -m "mensaje"
git push
```

## En cPanel

1. Git™ Version Control → Manage → Pull or Deploy → **Update from Remote**

## En la terminal de cPanel

```bash
cd ~/www/sistema-web.iglesiacasadeavivamiento.com
php artisan down
php -d allow_url_fopen=On -d memory_limit=-1 composer.phar install --no-dev --optimize-autoloader --ignore-platform-reqs
php artisan optimize:clear
php artisan config:cache && php artisan route:cache && php artisan view:cache
chmod -R 775 storage bootstrap/cache
php artisan up
```

---

# Qué NO está en Git (y no debes tocar)

| Elemento | Por qué |
|---|---|
| `.env` | Es solo del servidor. Nunca lo subas desde tu Mac. |
| `vendor/` | Se genera en el servidor con Composer. |
| `storage/` | Contiene archivos subidos y logs. No lo borres. |
| `composer.phar` | Es de Composer, no del proyecto. Conviene agregarlo al `.gitignore`. |

---

# Errores comunes

| Error | Solución |
|---|---|
| Página sin CSS / se ve fea | Falta `public/build` actualizado. Haz `npm run build` y súbelo (Git o zip). |
| `Class ... not found` | Falta o está incompleto `vendor`. Corre el Paso 6. |
| `composer: command not found` | Usa `php composer.phar ...` como en el Paso 6. |
| `allow_url_fopen` / `fileinfo` en Composer | Usa los flags del Paso 6 (`-d allow_url_fopen=On` y `--ignore-platform-reqs`). |
| Cambios que no se ven | Corre `php artisan optimize:clear` y luego las tres cachés. |
| Error 500 | Revisa `storage/logs/laravel.log`. |
| 404 en todo | El dominio debe apuntar a la carpeta `public` (cPanel → Domains → Document Root). |
| Update from Remote falla | Corre `git status` en el servidor y revisa archivos modificados. |

---

# Nunca hagas esto

- `php artisan migrate`: puede crear o romper tablas distintas a las del sistema viejo. Si una actualización necesita cambios en la base de datos, hazlos aparte y con respaldo.
- Subir tu `.env` de la laptop al servidor.
- Dejar `APP_DEBUG=true` en producción.
- Borrar `storage` al actualizar.
- Usar `composer require` o `composer update` en el servidor. Solo `composer install`.