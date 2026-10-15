# Ferretería El Nuevo Sol

Fase 17 completada: **auditoría centralizada e historial de actividad** sobre Laravel 13 + SQLite. **388 pruebas / 2130 aserciones aprobadas**. Fase 18.A: preparación documentada; mecanismo externo no confirmado y 18.B pendiente.

- [Relevamiento de integración — Etapa 18.A](docs/integracion-punto-de-venta.md)
- [Informe de Fase 17, auditoría, cobertura y limitaciones](docs/fase-17-auditoria.md)
- [Informe de Fases 15 y 16, CMS, seguridad y verificación](docs/fases-15-16.md)
- [Informe de Fases 12, 13 y 14, arquitectura y pruebas](docs/fases-12-13-14.md)
- [Informe de Fases 10 y 11, decisiones y pruebas](docs/fases-10-11.md)
- [Informe de Fases 8 y 9, decisiones y revisión visual](docs/fases-8-9.md)
- [Variantes, categorías e imágenes — Fases 5 a 7](docs/fases-5-6-7.md)
- [Autenticación y productos — Fases 3 y 4](docs/fases-3-4.md)
- [Instalación y modelo de datos — Fase 2](docs/fase-2-backend.md)
- [Auditoría del frontend original](docs/fase-1-frontend.md)

La aplicación está en `backend/`. Rutas públicas: `/`, `/productos`, `/productos/{slug}` y `/categorias/{slug}`. Administración: `/admin`, `/admin/products`, `/admin/categories`, `/admin/orders`, `/admin/content/sections` y `/admin/content/banners`. Configuración exclusiva de Admin: `/admin/settings`. Historial de actividad exclusivo de Admin: `/admin/activity-logs`. Clientes: `/mi-cuenta`, `/mi-cuenta/pedidos` y `/mi-cuenta/notificaciones`. Solo se muestran productos publicados; los borradores existentes conservan su estado.

Desde `backend/`, en esta máquina:

```powershell
& ../.tools/php/php.exe artisan test
& ../.tools/php/php.exe artisan migrate:status
& ../.tools/php/php.exe artisan serve --host=127.0.0.1 --port=8000 --no-reload
```

Aplicación local: `http://127.0.0.1:8000/`. Para la revisión se preparó una demostración separada en `http://127.0.0.1:8001/`; sus datos son ficticios y su reconstrucción está documentada en el informe.

Las imágenes requieren GD con WebP. El almacenamiento sigue privado; las rutas públicas comprueban publicación y visibilidad antes de servir imágenes. No se necesita `storage:link`.

El frontend original permanece intacto en `reference/wordpress/el-nuevo-sol/`. No se cargan WordPress ni WooCommerce. El pedido se guarda antes de mostrar WhatsApp y no descuenta stock. Falta configurar el número real del negocio, dirección y horarios desde Configuración; las variables SHOP_* funcionan como valores iniciales cuando no hay configuración persistida. Sin pagos ni envíos. El correo real continúa deshabilitado. 18.B requiere documentación confirmada y entorno de pruebas autorizado.


# Demo en Render

Demo privada publicada: [El Nuevo Sol](https://el-nuevo-sol-demo.onrender.com). Render Free con PostgreSQL en Neon e imágenes en Cloudinary; persistencia comprobada tras redesplegar el 15/10/2026. Acceso, pruebas realizadas y límites: [guía de despliegue de demostración](docs/despliegue-demo-render.md). Las claves se conservan fuera del repositorio.

