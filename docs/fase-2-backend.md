# Fase 2 — Laravel + SQLite + modelo de datos

Estado: implementada y verificada el 24 de septiembre de 2026. No se inició Fase 3.

## Implementado

Aplicación Laravel 13.33.0 en `backend/`, ejecutada con PHP 8.4.26 y Composer 2.10.3. Versiones reproducibles de dependencias en `backend/composer.lock`. SQLite utiliza la conexión estándar de Laravel, con claves foráneas activas y archivo local `backend/database/database.sqlite`.

Se crearon 14 modelos, 14 factories, 9 enums y las migraciones de las 16 tablas de dominio. Hay relaciones Eloquent, casts de importes/cantidades, seed de desarrollo, validaciones de integridad y estructura documentada de servicios en `backend/app/Services/README.md`.

El frontend WordPress permanece intacto en `reference/wordpress/el-nuevo-sol/`, fuera de `backend/public/`. La raíz del backend conserva únicamente la bienvenida de Laravel; no hay rutas propias de catálogo, login, carrito o checkout.

## Base de datos y modelos

| Tabla | Modelo / relaciones principales |
| --- | --- |
| `users` | `User`: orders, notifications, activityLogs, createdNotifications, statusChanges. |
| `products` | `Product`: variants, images, categories. |
| `product_variants` | `ProductVariant`: product. |
| `categories` | `Category`: parent, children, products. |
| `category_product` | Pivot con clave primaria compuesta, sin duplicados. |
| `product_images` | `ProductImage`: product; almacena rutas y metadatos. |
| `orders` | `Order`: user nullable, items, statusHistory. |
| `order_items` | `OrderItem`: order, product y productVariant nullable; snapshots independientes. |
| `order_status_history` | `OrderStatusHistory`: order, changedBy; solo created_at. |
| `notifications` | `Notification`: creator, users. |
| `notification_user` | Pivot con clave primaria compuesta y read_at individual. |
| `homepage_sections` | `HomepageSection`: settings JSON y tipo con enum. |
| `banners` | `Banner`: fechas, orden y estado; sin editor ni publicación automática. |
| `settings` | `Setting`: key única y value textual nullable; únicamente datos públicos de configuración. |
| `sync_runs` | `SyncRun`: origen, tiempos, estado, contadores y errores JSON; sin conector. |
| `activity_logs` | `ActivityLog`: user nullable, entidad descriptiva y metadata JSON; solo created_at. |

Además se conservan tablas estándar de Laravel: `migrations`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`. Preparan infraestructura; no implican autenticación, trabajos comerciales o correos funcionales.

Índices: slugs, email, order_number y settings.key únicos; SKU/external_id indexados sin unicidad; índices de publicación/destacados, jerarquía/orden, pedidos por cliente/fecha y estado/fecha, lectura de notificaciones y registros de sincronización/auditoría. Las referencias tienen claves foráneas y las dos pivots impiden duplicados.

## Enums

- `UserRole`: admin, seller, customer.
- `ProductStatus`: draft, published, private.
- `ProductType`: simple, variable.
- `PurchaseMode`: calculated, inquiry.
- `SaleUnit`: unit, meter, kilogram, gram, liter, milliliter, box, package, bundle, roll, other.
- `OrderStatus`: received, preparing, ready_for_pickup, picked_up, cancelled.
- `FulfillmentMethod`: pickup.
- `HomepageSectionType`: hero, featured_categories, featured_products, banner, promotion, text, image.
- `SyncRunStatus`: running, completed, failed.

Se persisten en columnas string, con casts a enums PHP; ampliar valores no depende de un ENUM nativo de MySQL. Los literales de defaults de migraciones permanecen congelados en cada migración para no hacer depender su historial de futuras modificaciones de los enums.

`notifications.type` es una clasificación textual todavía no cerrada; no se inventó un flujo de notificación. El modelo de notificaciones es propio, sin utilizar el trait `Notifiable`, que espera la estructura de notificaciones de Laravel y entraría en conflicto con la pivot solicitada.

## Decisiones técnicas

### Dinero

`price`, `unit_price`, `subtotal` y `confirmed_total` son BIGINT en **centavos**. En Eloquent se leen como int. Ejemplo: $2.500,00 se asigna como `250000`; no como `2500.00`. El cast `MinorUnits` rechaza floats, decimales, notación científica, valores negativos e importes mayores a 999999999999999 centavos. Los valores desconocidos se conservan como null, no cero.

Se elige esta representación porque una columna DECIMAL en SQLite no garantiza la misma aritmética decimal fija de MySQL. BIGINT más conversiones enteras conserva precisión en ambos motores. Moneda inicial prevista: ARS. La conversión de entradas humanas, multiplicación, redondeo y validación de totales se implementarán en las fases de compra; no se adelantó un motor de precios.

### Stock y cantidad

`stock` y `quantity` se guardan como BIGINT en milésimas. El cast `ScaledQuantity` recibe enteros en unidades completas o **cadenas decimales** y devuelve cadenas con tres decimales. Ejemplo: asignar `'10.501'` almacena `10501`; leer devuelve `'10.501'`. Rechaza floats, comas, notación científica y más de tres decimales en vez de redondear silenciosamente.

Límite: 12 dígitos enteros y 3 fraccionarios. El stock admite signo para no descartar posibles saldos comerciales negativos; eso no habilita su venta. La cantidad de un item debe ser estrictamente positiva. La precisión elegida es una decisión inicial técnica, no una afirmación sobre Punto de Venta; se revisará si su documentación requiere más precisión. Los pasos por unidad también quedan pendientes.

Las columnas crudas contienen milésimas, no unidades: importadores y futuros servicios deben usar los casts o convertir explícitamente. Query Builder y actualizaciones masivas omiten eventos/casts, por lo que no deben utilizarse indiscriminadamente para estos datos.

### Productos con variantes

Un producto variable mantiene `products.price` y `products.stock` en null. Precio y stock pertenecen a sus variantes; guardar importes en el padre genera una excepción. Las variantes requieren un padre variable; un padre con variantes no puede convertirse en simple sin resolverlas antes.

Se añadieron `sale_unit` y `purchase_mode` nullable a variantes: null significa utilizar la configuración del padre en la futura lógica de compra; un valor permite una excepción explícita. En esta fase solo se prepara persistencia, sin resolver precios o disponibilidad pública. La pintura demo tiene envases 1 L / 4 L / 20 L vendidos por unidad, evitando confundir capacidad del envase con unidad facturada.

`sku` y `external_id` no son únicos todavía: no conocemos reglas, ámbito ni colisiones del sistema externo. No podrán usarse como clave de upsert hasta definir una identidad externa confiable. Ningún valor demo pretende ser Lista 1 real ni un producto sincronizado.

### Historial y borrado

No se agregaron SoftDeletes indiscriminadamente. Las claves históricas hacia users, products y product_variants usan SET NULL; los nombres, SKU, unidades, cantidades e importes están almacenados en el item y no se recalculan desde el catálogo.

Los items y el historial restringen la eliminación de su pedido (RESTRICT); borrar un usuario no borra sus pedidos. Borrar un producto elimina sus variantes, metadatos de imágenes y pivots, pero conserva los items históricos con referencias null. La eliminación de archivos físicos no está implementada.

Borrar una categoría separa sus hijos a raíz mediante parent_id null y elimina únicamente sus pivots. El modelo impide autorreferencias y ciclos al guardar normalmente. Las ediciones concurrentes de jerarquía requerirán control transaccional cuando exista su interfaz.

Esto protege contra borrados en cascada accidentales; no convierte los snapshots en almacenamiento inmutable frente a una escritura administrativa directa. La autorización para editar/eliminar histórico se implementará en sus fases.

### Seguridad y separación comercial/editorial

Cada modelo declara campos asignables. `User.role`, `active` y `email_verified_at` no son asignables mediante datos de perfil; el rol por defecto en DB es customer. Las contraseñas usan el cast `hashed` y se ocultan de serialización. El seeder asigna roles explícitamente en un contexto controlado. Policies y Gates quedan para Fase 3 y los módulos posteriores.

Los modelos contienen tanto campos comerciales como editoriales; los futuros servicios de sincronización deberán usar una lista cerrada de campos comerciales. La existencia de `$fillable` no concede permisos ni reemplaza esa separación. No existe un endpoint que exponga escritura genérica de modelos.

`settings` no guarda credenciales. `ActivityLog` y `SyncRun` preparan persistencia, no registradores automáticos: los futuros escritores deberán excluir secretos y no volcar payloads externos completos en description, metadata o error_details. `.env`, SQLite, caches, dependencias y herramientas locales están excluidos de Git.

### SQLite y portabilidad

Se mantuvieron Schema Builder/Eloquent, tipos comunes, JSON con casts y claves foráneas. No hay SQL manual, índices parciales, triggers ni funciones específicas de SQLite. SQLite es el único motor ejecutado en esta fase; la portabilidad a MySQL/MariaDB está prevista por diseño, **todavía no validada mediante pruebas en esos motores**. El traspaso futuro requerirá migrar también los datos, no solo cambiar `.env`.

La selección exclusiva de imagen principal, las reglas de publicación y la consistencia agregada de totales serán responsabilidad transaccional de los servicios de sus fases. No se simulan ahora esas operaciones mediante observers.

## Datos de demostración

`DatabaseSeeder` ejecuta `DevelopmentSeeder`, permitido exclusivamente con `APP_ENV=local` o `testing`. Usa transacción y se puede repetir sin duplicar registros ni restablecer contraseñas existentes.

**SOLO DESARROLLO** — contraseña de los cuatro usuarios: `SoloDesarrollo!2026`.

| Email | Rol |
| --- | --- |
| admin@example.test | admin |
| vendedor@example.test | seller |
| cliente1@example.test | customer |
| cliente2@example.test | customer |

No hay formulario de login todavía. Las cuentas son para desarrollar/probar y no deben migrarse a producción.

Cuatro categorías: Herramientas → Herramientas eléctricas → Taladros; Pinturas. Cuatro productos en borrador: taladro simple, cable por metro, pintura variable y corte de caño a consultar. Tres variantes: 1 L, 4 L y 20 L. Importes y existencias son ficticios.

Hay factories para los 14 modelos, incluidos pedidos, imágenes, notificaciones, contenido y auditoría. Las rutas de imágenes de factories son metadatos de prueba, sin archivos físicos. No se cargan pedidos ni sincronizaciones ficticias en la base local por defecto; se crean durante tests aislados.

## Instalación y ejecución

Requisitos: PHP 8.3 o superior compatible con las dependencias bloqueadas, Composer 2 y extensiones exigidas por `composer check-platform-reqs`, además de PDO SQLite/SQLite3. El entorno probado usa PHP 8.4.26. Node/npm no son necesarios para esta fase.

En una instalación nueva, desde `backend/`:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item -ItemType File database/database.sqlite
php artisan migrate
php artisan db:seed
php artisan test
```

Copiar `.env` y crear el archivo SQLite solo si todavía no existen; no reemplazar configuraciones ni datos existentes. `DB_DATABASE` se omite para usar el path estándar absoluto calculado por Laravel. No configurar MySQL para desarrollar.

En esta máquina, desde `backend/`, las herramientas descargadas están disponibles sin modificar el PATH global:

```powershell
& ../.tools/php/php.exe artisan migrate:status
& ../.tools/php/php.exe artisan db:seed
& ../.tools/php/php.exe artisan test
& ../.tools/php/php.exe artisan serve
```

Composer local: `& ../.tools/php/php.exe ../.tools/composer.phar --version`. Para instalar dependencias con scripts que invocan `php`, agregar `.tools/php` al PATH de esa sesión o usar una instalación normal de PHP/Composer. Las herramientas en `.tools/` no se versionan ni son una instalación portable incluida en Git. Se descargaron de fuentes oficiales y se verificaron hashes antes de ejecutarlas.

`artisan serve` muestra la bienvenida Laravel, no una tienda implementada. No es necesario dejarlo ejecutándose para usar migraciones/tests.

## Verificación realizada

- `migrate:fresh --seed`: migraciones correctas; el primer seed detectó un namespace generado incorrectamente, que se corrigió. `db:seed` luego completó correctamente. La reconstrucción se hizo sobre la base vacía recién creada, sin datos del usuario.
- `artisan test`: **49 pruebas aprobadas, 155 aserciones**, en SQLite en memoria. Cubren tablas, FKs, roles, hashes, enums, dinero exacto, stock fraccional, rechazo de floats, variantes, jerarquías/ciclos, pivots únicas, imágenes, pedidos registrados/invitados, snapshots, borrados, notificaciones, JSON y seed repetible/bloqueado en producción.
- Prueba de ciclo completo migrate → rollback → migrate en una conexión SQLite aislada.
- `migrate:status`: las seis migraciones aplicadas.
- Pint: formato aplicado y verificado.
- Composer: manifiesto válido, requisitos de plataforma satisfechos y auditoría de instalación sin avisos de vulnerabilidad.
- Ruta propia: solo `/`, bienvenida Laravel; no se crearon rutas de funcionalidades fuera de alcance.

Problemas resueltos: PHP/Composer ausentes, restricción inicial de acceso de red, errores de namespaces en generación de archivos y ejecución de tests desde un directorio incorrecto. No quedan fallos conocidos en las migraciones ni en la suite ejecutada.

La plantilla oficial incluía `AGENTS.md` con instrucciones de instalar Laravel Boost. Se instaló como dependencia **solo de desarrollo**, con sus guías; no añade lógica del comercio ni un conector de negocio.

## Archivos principales

- `backend/composer.json`, `composer.lock`, `.env.example`, `config/database.php`, `phpunit.xml`: proyecto y configuración.
- `backend/database/migrations/`: usuarios/infraestructura y catálogo, pedidos, contenido/operaciones.
- `backend/app/Models/`, `app/Enums/`, `app/Casts/`: entidades, relaciones y representación de datos.
- `backend/database/factories/` y `database/seeders/`: demostración y pruebas.
- `backend/tests/Feature/{DataModelTest,DataIntegrityTest,MigrationLifecycleTest}.php`: pruebas del dominio y persistencia.
- `backend/app/Providers/AppServiceProvider.php`: rechazo de atributos descartados silenciosamente.
- `backend/app/Services/README.md`: límites de servicios futuros.
- `.gitignore`, `README.md`, `backend/README.md` y este informe: protección de archivos locales y documentación.

## Pendientes deliberados

Fase 3: autenticación funcional, Policies/Gates y permisos. Fases posteriores: administración, catálogo, variantes en UI, archivos, carrito, checkout, números de pedido, transiciones de estado, WhatsApp, notificaciones, CMS y auditoría automática. Integración real solo al recibir documentación de Punto de Venta. Sin delivery ni pagos online.

Antes de desarrollar cálculos comerciales deben confirmarse redondeo, pasos por unidad, impuestos/Lista 1 y tratamiento de disponibilidad. No se inició ninguna de esas funcionalidades.

Referencias consultadas: [requisitos de Laravel 13](https://laravel.com/framework/docs/releases), [instalación oficial](https://laravel.com/framework/docs), [PHP para Windows](https://windows.php.net/download/), [Composer](https://getcomposer.org/download/).
