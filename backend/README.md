# Backend — Ferretería El Nuevo Sol

Laravel 13 + SQLite. Fases 1 a 17 completadas; **388 tests / 2130 aserciones aprobados**.

- [Relevamiento de integración — Etapa 18.A](../docs/integracion-punto-de-venta.md)
- [Auditoría e historial de actividad](../docs/fase-17-auditoria.md)
- [Panel unificado y CMS de inicio y banners](../docs/fases-15-16.md)
- [Pedidos administrativos, Mi cuenta y notificaciones](../docs/fases-12-13-14.md)
- [Carrito, checkout, pedidos y WhatsApp](../docs/fases-10-11.md)
- [Catálogo público, búsqueda, filtros y revisión visual](../docs/fases-8-9.md)
- [Administración de variantes, categorías e imágenes](../docs/fases-5-6-7.md)
- [Autenticación, perfil y productos](../docs/fases-3-4.md)
- [Instalación, credenciales SOLO DESARROLLO y modelo de datos](../docs/fase-2-backend.md)

Ejecutar comandos desde esta carpeta:

```powershell
& ../.tools/php/php.exe artisan test
& ../.tools/php/php.exe artisan migrate
& ../.tools/php/php.exe artisan serve --host=127.0.0.1 --port=8000 --no-reload
```

En otro entorno con PHP/Composer en PATH, usar `php artisan ...`. No ejecutar `migrate:fresh` sobre bases con datos a conservar. Los seeders de demostración están restringidos a local/testing.

El frontend público usa Blade y assets estáticos en `public/`; no requiere npm ni Vite. El tema original está aislado como referencia. Las páginas públicas solo consultan productos publicados.

Imágenes: GD con JPEG, PNG y WebP; `upload_max_filesize=5M`, `post_max_size=8M` y memoria suficiente (512M en desarrollo). Disco privado `catalog` en `storage/app/catalog`; `CATALOG_STORAGE_PATH` permite aislar archivos en la demostración. No exponer la carpeta mediante symlink. Las rutas de medios públicos revalidan visibilidad.

`StorefrontPreviewSeeder` es optativo y exige una base llamada `storefront-preview.sqlite`; no se ejecuta con el seed normal. El informe detalla cómo iniciarlo en una terminal separada.

Seguir `AGENTS.md`. 18.A documentada; no ejecutar 18.B sin mecanismo confirmado y entorno autorizado. Admin puede configurar WhatsApp, dirección y horarios desde `/admin/settings`. Las variables SHOP_WHATSAPP_NUMBER, SHOP_PICKUP_ADDRESS y SHOP_PICKUP_HOURS son valores iniciales cuando no hay una configuración persistida. El correo real sigue deshabilitado (`MAIL_MAILER=array`).


