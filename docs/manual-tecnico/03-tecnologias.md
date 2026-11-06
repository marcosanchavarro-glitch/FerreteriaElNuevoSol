# 3. Tecnologías y herramientas

## 3.1 Versiones y alcance de la evidencia

| Tecnología | Referencia verificable | Uso y precisión |
|---|---|---|
| PHP | Rama 8.4 en Dockerfile, CI y AGENTS; 8.4.26 local | Runtime objetivo; el parche de la imagen no está fijado |
| Requisito PHP del proyecto | `^8.3` en composer.json | No sustituye el objetivo 8.4 usado para validar; comprobar requisitos del lock |
| Laravel | `v13.33.0` en composer.lock | Framework HTTP, ORM, vistas, validación y transacciones |
| Composer | Imagen `composer:2` en Dockerfile; 2.10.3 local | Instalación desde lock; parche no fijado por el repositorio |
| Tinker | `v3.0.2` en composer.lock | Consola de desarrollo; no parte del recorrido público |
| PHPUnit | `12.5.35` en composer.lock | Pruebas unitarias y funcionales |
| Pint | `v1.32.1` en composer.lock | Formato PHP |
| Boost | `v2.10.0` en composer.lock | Herramientas de desarrollo; dependencia dev |
| PostgreSQL | `postgres:17` en CI; 17.11 en la ejecución histórica indicada en capítulo 16 | Parche de futuras ejecuciones puede variar |
| SQLite | PDO SQLite en desarrollo/CI; extensión local 3.53.4 | Versión de extensión consultada sin abrir una base; no está fijada en el manifiesto |
| Git | `2.55.0.windows.3` observado localmente | Versionado; no es requisito mínimo del proyecto |
| Blade | Incluido en Laravel 13.33.0 | Sin versión independiente |
| HTML/CSS/JavaScript | Recursos propios estáticos | No se asigna una versión de paquete a estándares del navegador |
| Render/Neon/Cloudinary/GitHub | Servicios SaaS | No poseen versión instalada fijada por este repositorio |

`composer.json` incluye también Faker, Mockery, Collision, Pail y Pao para desarrollo. El inventario anexo extrae las versiones resueltas de todas las dependencias directas del lock, sin confundir rangos admitidos con versiones instaladas.

## 3.2 Herramientas frontend presentes frente a herramientas utilizadas

`package.json` declara Vite `^8.0.0`, Tailwind `^4.0.0`, `@tailwindcss/vite`, `laravel-vite-plugin`, `concurrently` y el paquete opcional `@laravel/multiplex`. Su presencia procede de la estructura de herramientas del proyecto. Los layouts operativos usan `asset('css/...')` y `asset('js/...')`, no `@vite`. El Dockerfile de producción no ejecuta npm ni compila estos recursos. Por ello no debe exigirse Node para servir las pantallas actuales ni describirse la interfaz como construida con React, Vue o Tailwind sin revisar el uso efectivo.

## 3.3 Servicios y bibliotecas

El adaptador Cloudinary usa el cliente HTTP de Laravel y firma solicitudes con SHA-256; no aparece un SDK Cloudinary como dependencia directa de Composer. WhatsApp no requiere SDK: se construye una URL `wa.me`. Las tablas de cola no demuestran la existencia de un trabajador en producción; Render configura `QUEUE_CONNECTION=sync`.

Para instalar una copia reproducible se utiliza `composer install`, no `composer update`. La referencia oficial del framework es [Laravel 13](https://laravel.com/docs/13.x); las versiones exactas del proyecto se obtienen del lock y prevalecen sobre ejemplos de versiones posteriores.
