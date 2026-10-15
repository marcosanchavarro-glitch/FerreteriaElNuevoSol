# Fase 17 — Auditoría, historial de actividad y trazabilidad

Implementada y verificada el 8 de octubre de 2026. Fase 18 no iniciada.

## Arquitectura y persistencia

Se reutiliza `activity_logs` y su modelo `ActivityLog`. La migración complementaria `2026_10_08_130351_extend_activity_logs_for_auditing.php` agrega identidad histórica del responsable (ID, nombre y rol), módulo, resultado, origen y snapshots antes/después. Agrega índices por actor/fecha, fecha/ID, acción/fecha, módulo/fecha y resultado/fecha; conserva los índices previos por entidad y usuario. Los registros anteriores quedan marcados como históricos, sin inventar resultados o valores anteriores.

`ActivityLogService` centraliza captura, sanitización, diferencias y escritura. La integración se hace en servicios de dominio y en las pocas escrituras que permanecían en controladores. No se usan observers globales: estos producirían eventos intermedios o duplicados en operaciones con varios guardados y no observarían actualizaciones masivas. Cada operación real genera un solo evento; guardar sin cambios no genera ruido. Una operación puede modificar varios campos y se muestra su diferencia dentro del mismo evento.

Los eventos exitosos se escriben dentro de la misma transacción que la operación; el servicio rechaza escribir un éxito sin una transacción activa. Si falla la escritura del log, también se revierte el cambio. Las operaciones de imágenes conservan sus mecanismos de backup, restauración y limpieza. Las pruebas simulan fallos de auditoría y verifican la restauración de archivos y filas.

Los cambios de pedidos mantienen `order_status_history` y las notificaciones existentes. La auditoría conserva únicamente el cambio de estado y el responsable, sin duplicar productos, teléfono, email ni domicilio del cliente.

## Cobertura efectiva

| Módulo | Operaciones auditadas |
|---|---|
| Productos | Creación, edición de campos autorizados, cambios de publicación/inactividad, destacado, datos comerciales locales y asignación de categorías |
| Variantes | Creación, edición, presentación, activación/desactivación y datos comerciales autorizados |
| Categorías | Creación, edición, jerarquía, visibilidad, destacado, orden e imagen |
| Galerías | Subida, texto alternativo, eliminación, cambio de principal y reordenamiento; se incluyen los cambios colaterales en la misma operación |
| Pedidos | Transiciones administrativas válidas, incluida cancelación |
| CMS | Creación/edición de secciones y banners, contenido, enlaces, imágenes, visibilidad y orden |
| Configuración | Nombre comercial, teléfono, WhatsApp, dirección y horarios, agrupados en un evento por guardado |

Las galerías utilizan el ID del producto como referencia de entidad y guardan las referencias/IDs de las imágenes afectadas, nunca binarios. El orden de secciones/banners se representa mediante la lista ordenada de IDs. Las modificaciones de publicación o destacado figuran como «Modificación», con el campo y sus valores explícitos en el detalle.

Se registran aparte los rechazos 403 a escrituras administrativas de usuarios de personal autenticados, sin copiar el contenido enviado. No se registran vistas, navegación cotidiana, intentos de acceso de clientes/invitados, errores ordinarios de validación, ni fallos CSRF como operaciones exitosas. No es un sistema general de monitoreo de seguridad.

No existe un módulo administrativo de gestión de usuarios/roles o eliminación de categorías: no se construyó uno ni se simularon eventos para operaciones inexistentes. Los cambios directos por SQL, consola, seeders o código que no utilice el servicio no se auditan automáticamente.

## Seguridad y conservación

- `view-activity` exige Admin activo. Seller y Customer reciben 403; Guest va al login. La navegación respeta el mismo permiso.
- Solo existen rutas GET de listado y detalle. No hay rutas ni botones para crear, editar o borrar registros desde el panel. El modelo también rechaza actualización y eliminación Eloquent normales.
- Allowlist explícita por módulo tanto al capturar como al escribir y presentar snapshots. No se copian requests completos, sesiones, cookies, passwords, hashes, tokens, credenciales o secretos. Los metadatos antiguos no se exponen en la nueva interfaz porque preceden a estas reglas.
- Se conserva nombre, rol e ID histórico del responsable aunque cambie su nombre/rol, se desactive o se elimine su usuario. La relación opcional puede desaparecer sin perder esa referencia.
- Salida escapada, filtros validados y consultas parametrizadas. CSRF sigue habilitado en las escrituras de los módulos originales.
- No se implementó purga automática ni plazo arbitrario de retención.

La inmutabilidad es una garantía de la operación normal de la aplicación, no una protección absoluta frente a acceso directo al archivo SQLite, SQL masivo o administración del servidor. Se requieren permisos de sistema limitados y respaldos protegidos y verificables. No se agregaron firmas criptográficas ni un repositorio externo de auditoría.

Los campos comerciales de texto libre se conservan como contenido: no deben utilizarse para pegar secretos. La allowlist evita capturar campos de credenciales; no puede reconocer infaliblemente un secreto escrito dentro de una descripción autorizada.

## Interfaz

`/admin/activity-logs` ofrece tarjetas paginadas de 20 eventos, orden descendente por fecha e ID. Filtros por ID histórico del responsable, acción, módulo, entidad, resultado y rango de fechas; búsqueda por nombre histórico o ID de entidad. Las fechas se interpretan y muestran en horario de Argentina. Se aceptan rangos abiertos.

El detalle muestra responsable/rol, fecha, acción, módulo/entidad, origen, resultado y únicamente campos modificados. Presenta antes/después con etiquetas legibles, importes monetarios y estados traducidos; las estructuras de galerías se presentan como referencias y propiedades, sin JSON crudo. Los filtros y el detalle se apilan en móvil, sin tablas que desborden. No necesita cargar relaciones de usuarios para cada fila y evita N+1.

## Verificación

- Suite previa a cambios: **368 pruebas, 2021 aserciones, aprobadas**.
- Suite final completa: **388 pruebas, 2130 aserciones, aprobadas**.
- 20 casos nuevos: creación/diferencias/actor/fecha, variantes, categorías, galerías, CMS, pedidos, settings, sanitización, no duplicación, rollback de datos/archivos, roles, filtros/paginación, zona horaria, inmutabilidad, responsables desactivados/eliminados, orígenes automáticos y CSRF.
- Pint aplicado; migración aplicada correctamente a la base principal y a la demostración, después de respaldarlas. Las migraciones también se ejecutan sobre SQLite aislado en la suite.

Recorrido manual en la base de demostración:

1. Seller: no ve Historial de actividad y acceso directo devuelve 403.
2. Admin: modificación de descripción breve del producto DEMO #16; listado y detalle muestran un evento con texto anterior y posterior.
3. Edición del subtítulo del banner DEMO #1; aparece un evento de Banners. Continúa inactivo.
4. Pedido `DEMO-PEDIDO-AJENO` #3: Recibido → En preparación; evento y detalle correctos.
5. Filtro por Pedidos muestra solamente ese evento.
6. Listado y detalle revisados en escritorio y en 390 × 844. Menú móvil plegable, contenido legible y sin desbordes.
7. Se dejó sesión Admin y listado completo abiertos. Evidencia: `.tools/phase17-activity.png`.

Los tres cambios manuales son datos de demostración y se conservaron junto con sus tres eventos; no se borró historial para limpiar pruebas.

## Entorno y respaldos

El repositorio continúa con archivos sin seguimiento, como antes; no se creó commit. Laravel instalado: 13.33.0. No se agregaron dependencias.

Antes de migrar se crearon respaldos consistentes mediante SQLite backup:

- `.tools/database-before-phase17-20261008101200.sqlite`
- `.tools/storefront-preview-before-phase17-20261008101200.sqlite`

Windows reportó bloqueos de carga para extensiones PHP curl e intl en CLI. La ejecución de PHPUnit dentro del sandbox reportó erróneamente falta de acceso a `vendor/autoload.php`; fuera de ese entorno las pruebas pasaron. No se cambiaron protecciones de Windows ni extensiones. El lanzamiento indirecto por Artisan no respondió en esta sesión; el servidor directo de PHP con el router oficial de Laravel sí permitió completar la prueba en navegador.

Para reproducir el servidor de demostración, desde `backend/public`:

```powershell
$env:DB_DATABASE='C:/Users/Marco/Documents/ChatGPT/Ferreteria El Nuevo Sol/.tools/storefront-preview.sqlite'
$env:CATALOG_STORAGE_PATH='C:/Users/Marco/Documents/ChatGPT/Ferreteria El Nuevo Sol/.tools/storefront-preview-media'
& ../../.tools/php/php.exe -S 127.0.0.1:8001 ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
```

## Archivos principales y próximas fases

Nuevos: ActivityLogService, ActivityLogController, ActivityDisplay, migración complementaria, vistas `admin/activity/index` y `show`, y ActivityAuditTest. Se integraron los servicios CategoryManager, VariantManager, ProductGallery, CategoryImages, ContentManager y OrderStatusService; escrituras de ProductController, ProductCategoryController, ProductImageController, ContentController y SettingsController; modelo ActivityLog, rutas, Gate, manejo de rechazos, navegación y estilos.

Los orígenes `admin`, `system` y `external` están disponibles. Los procesos automáticos futuros deben invocar explícitamente el servicio dentro de su transacción; las llamadas de origen system/external no heredan accidentalmente al usuario autenticado. Aún no hay un proceso externo ni protocolo de sincronización. La política de retención, respaldo operativo permanente y cualquier protección adicional frente a administradores de servidor se definirán después.

No se implementaron Punto de Venta, WooCommerce, sincronización, pagos, delivery, monitoreo externo, nuevas jerarquías ni rediseño del panel. **Fase 18 no iniciada.**
