# 5. Instalación y configuración

## 5.1 Requisitos y aislamiento

Se recomienda PHP 8.4 de 64 bits, Composer 2, Git y extensiones PDO SQLite para desarrollo o PDO PostgreSQL para una base de prueba externa. GD debe admitir WebP; también se emplean mbstring, intl, zip y las extensiones estándar requeridas por Composer. `composer check-platform-reqs` determina la compatibilidad exacta del lock. Docker y PostgreSQL local no son obligatorios para la instalación SQLite.

Estos pasos crean una copia nueva de desarrollo. Nunca apuntar el archivo `.env` de esa copia a Neon de la demo para ejecutar semillas o pruebas. No reutilizar APP_KEY ni credenciales de producción. Los siguientes comandos están respaldados por archivos existentes; no fueron ejecutados durante este trabajo documental.

## 5.2 Obtención e instalación

En PowerShell, desde una carpeta de trabajo elegida:

```powershell
git clone --branch codex/validation-phase18-19 https://github.com/marcosanchavarro-glitch/FerreteriaElNuevoSol.git
Set-Location FerreteriaElNuevoSol
git switch --detach af85ee7a6e1179ac77757a72c9f05b17bc9983bf
Set-Location backend
composer install --no-interaction --prefer-dist
composer check-platform-reqs
Copy-Item .env.example .env
```

La referencia detached permite reproducir la versión; crear una rama propia antes de desarrollar. `Copy-Item` corresponde a una copia nueva: no sobrescribir un `.env` existente. No usar `composer run setup` indiscriminadamente: el script también ejecuta migraciones y npm. Para las pantallas estáticas actuales, npm no es necesario.

## 5.3 Configuración local mínima

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=sqlite
DB_FOREIGN_KEYS=true
SESSION_DRIVER=database
SESSION_LIFETIME=10080
SESSION_EXPIRE_ON_CLOSE=false
SESSION_SECURE_COOKIE=false
CACHE_STORE=database
MAIL_MAILER=array
DEMO_MODE=false
CATALOG_STORAGE=local
```

No establecer `DB_URL` para SQLite. Si la terminal hereda una conexión externa, eliminarla de ese proceso antes de continuar. El archivo de ejemplo deja `DB_DATABASE` omitido para usar `database/database.sqlite`. Mantener el mismo host en APP_URL y navegador; `localhost` y `127.0.0.1` no comparten cookie por defecto. En HTTPS se necesita cookie segura; el valor falso anterior es solo para HTTP local.

## 5.4 Clave, base y datos iniciales

```powershell
php artisan key:generate
if (-not (Test-Path database/database.sqlite)) {
    New-Item -ItemType File database/database.sqlite | Out-Null
}
php artisan migrate --no-interaction
php artisan db:seed --no-interaction
php artisan serve --host=127.0.0.1 --port=8000
```

`DatabaseSeeder` invoca `DevelopmentSeeder` y `HomepageSeeder`. La semilla de desarrollo solo admite `local/testing`; crea usuarios ficticios de cuatro perfiles nominales (Admin, Seller y dos Customer), categorías y productos mayormente en borrador. Los identificadores y la contraseña exclusiva de desarrollo están en `DevelopmentSeeder.php`; no deben utilizarse como credenciales comerciales. Un catálogo público vacío después de sembrar puede ser correcto: publicar un producto válido desde el panel local para verlo. No usar las semillas de preview sobre una base que se deba conservar.

En este equipo existe un PHP portable en `.tools/php/php.exe`, pero esa carpeta no se distribuye con Git. La instalación anterior asume `php` y `composer` disponibles en PATH. No generar otra APP_KEY en un entorno ya configurado: invalidaría datos cifrados y sesiones relacionadas.

## 5.5 Comprobación y PostgreSQL opcional

Abrir `/up`, `/`, `/productos` y `/login`. `/up` comprueba el arranque HTTP; no demuestra por sí solo que Cloudinary o todas las consultas de base funcionen. Consultar `php artisan route:list --except-vendor` para localizar rutas y `php artisan migrate:status` para diagnosticar la base seleccionada. Revisar `storage/logs` si aparecen errores.

Para una base PostgreSQL de desarrollo autorizada, configurar `DB_CONNECTION=pgsql` y `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`; usar TLS apropiado. El arranque Docker de la demo exige `DB_URL`, pero el desarrollo normal puede usar variables separadas. No ejecutar las pruebas sobre una base persistente: PHPUnit usa migraciones y limpieza de datos. El capítulo 16 documenta el aislamiento requerido.

Fuentes: `.env.example`, `composer.json`, `config/database.php`, `database/seeders/DatabaseSeeder.php`, `DevelopmentSeeder.php`, `Dockerfile` y `bootstrap/app.php` dentro de `backend/`.
