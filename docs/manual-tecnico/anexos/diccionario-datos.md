# Anexo A. Diccionario de datos

Derivado de las operaciones `up()` de las diez migraciones, incorporando ampliaciones posteriores. Los tipos son nombres de Schema Builder; no se ejecutaron migraciones para extraer esta información. Las cadenas sin longitud explícita usan la longitud predeterminada del framework. `timestamps()` se expande a dos campos anulables. Las restricciones indicadas pertenecen a la base; reglas de enums, importes y jerarquía adicionales están en modelos/requests.

Tablas declaradas: **23**. Además Laravel mantiene `migrations` (id, migration y batch) como registro del migrador. El ER muestra las relaciones del dominio y sesiones; las tablas técnicas independientes se describen aquí.

## A.1 `users`

Identidad y autorización de cuentas.

Migraciones: `0001_01_01_000000_create_users_table.php`.

| Campo | Tipo | NULL | Restricciones / default | Significado |
|---|---|---|---|---|
| `id` | bigint autoincremental | No | PK | Identificador primario del registro. |
| `first_name` | string | No | — | Nombre. |
| `last_name` | string | No | — | Apellido. |
| `email` | string | No | UNIQUE | Dirección de correo de la cuenta o recuperación. |
| `phone` | string | Sí | — | Teléfono de contacto. |
| `password` | string | No | — | Hash de contraseña; nunca texto plano. |
| `role` | string | No | default 'customer'; INDEX | Rol admin/seller/customer. |
| `active` | boolean | No | default true | Habilitación del registro según el módulo. |
| `email_verified_at` | timestamp | Sí | — | Fecha de verificación; no implica flujo de verificación implementado. |
| `remember_token` | string(100) | Sí | — | Token para recordar autenticación. |
| `created_at` | timestamp | Sí | — | Fecha de creación. |
| `updated_at` | timestamp | Sí | — | Última actualización. |

## A.2 `password_reset_tokens`

Tokens del broker de recuperación.

Migraciones: `0001_01_01_000000_create_users_table.php`.

| Campo | Tipo | NULL | Restricciones / default | Significado |
|---|---|---|---|---|
| `email` | string | No | PK | Dirección de correo de la cuenta o recuperación. |
| `token` | string | No | — | Token de recuperación gestionado por el broker. |
| `created_at` | timestamp | Sí | — | Fecha de creación. |

## A.3 `sessions`

Persistencia de sesiones web.

Migraciones: `0001_01_01_000000_create_users_table.php`.

| Campo | Tipo | NULL | Restricciones / default | Significado |
|---|---|---|---|---|
| `id` | string | No | PK | Identificador primario del registro. |
| `user_id` | foreignId | Sí | FK users.id; DELETE SET NULL | Usuario relacionado; ver regla de borrado. |
| `ip_address` | string(45) | Sí | — | IP de sesión. |
| `user_agent` | text | Sí | — | Identificación del cliente HTTP. |
| `payload` | longText | No | — | Contenido serializado de sesión o trabajo. |
| `last_activity` | integer | No | INDEX | Marca temporal de última actividad de sesión. |

## A.4 `cache`

Valores de caché.

Migraciones: `0001_01_01_000001_create_cache_table.php`.

| Campo | Tipo | NULL | Restricciones / default | Significado |
|---|---|---|---|---|
| `key` | string | No | PK | Clave de caché o configuración. |
| `value` | mediumText | No | — | Valor de configuración/caché o descriptor de variante según tabla. |
| `expiration` | bigInteger | No | INDEX | Vencimiento del registro. |

## A.5 `cache_locks`

Propiedad y expiración de bloqueos atómicos.

Migraciones: `0001_01_01_000001_create_cache_table.php`.

| Campo | Tipo | NULL | Restricciones / default | Significado |
|---|---|---|---|---|
| `key` | string | No | PK | Clave de caché o configuración. |
| `owner` | string | No | — | Propietario del bloqueo. |
| `expiration` | bigInteger | No | INDEX | Vencimiento del registro. |

## A.6 `jobs`

Cola de trabajos de Laravel; su tabla no implica worker desplegado.

Migraciones: `0001_01_01_000002_create_jobs_table.php`.

| Campo | Tipo | NULL | Restricciones / default | Significado |
|---|---|---|---|---|
| `id` | bigint autoincremental | No | PK | Identificador primario del registro. |
| `queue` | string | No | INDEX | Nombre de cola. |
| `payload` | longText | No | — | Contenido serializado de sesión o trabajo. |
| `attempts` | unsignedSmallInteger | No | — | Intentos del trabajo. |
| `reserved_at` | unsignedInteger | Sí | — | Instante de reserva del trabajo. |
| `available_at` | unsignedInteger | No | — | Instante en que puede ejecutarse. |
| `created_at` | unsignedInteger | No | — | Fecha de creación. |

## A.7 `job_batches`

Metadatos de lotes de cola.

Migraciones: `0001_01_01_000002_create_jobs_table.php`.

| Campo | Tipo | NULL | Restricciones / default | Significado |
|---|---|---|---|---|
| `id` | string | No | PK | Identificador primario del registro. |
| `name` | string | No | — | Nombre de entidad. |
| `total_jobs` | integer | No | — | Cantidad total de trabajos. |
| `pending_jobs` | integer | No | — | Trabajos pendientes. |
| `failed_jobs` | integer | No | — | Cantidad de fallos. |
| `failed_job_ids` | longText | No | — | IDs de trabajos fallidos. |
| `options` | mediumText | Sí | — | Opciones serializadas del lote. |
| `cancelled_at` | integer | Sí | — | Cancelación del lote. |
| `created_at` | integer | No | — | Fecha de creación. |
| `finished_at` | integer | Sí | — | Finalización de lote o ejecución. |

## A.8 `failed_jobs`

Diagnóstico de trabajos fallidos.

Migraciones: `0001_01_01_000002_create_jobs_table.php`.

| Campo | Tipo | NULL | Restricciones / default | Significado |
|---|---|---|---|---|
| `id` | bigint autoincremental | No | PK | Identificador primario del registro. |
| `uuid` | string | No | UNIQUE | Identificador único del trabajo fallido. |
| `connection` | string | No | — | Conexión de cola. |
| `queue` | string | No | — | Nombre de cola. |
| `payload` | longText | No | — | Contenido serializado de sesión o trabajo. |
| `exception` | longText | No | — | Detalle del error del trabajo. |
| `failed_at` | timestamp | No | fecha actual | Fecha del fallo. |

Claves e índices adicionales (declaración exacta):

- `index(['connection', 'queue', 'failed_at'])`.

## A.9 `products`

Producto editorial y comercial simple o padre variable.

Migraciones: `2026_09_24_000100_create_catalog_tables.php`.

| Campo | Tipo | NULL | Restricciones / default | Significado |
|---|---|---|---|---|
| `id` | bigint autoincremental | No | PK | Identificador primario del registro. |
| `external_id` | string | Sí | — | Identificador externo opcional; no es la clave de coincidencia del importador SKU. |
| `sku` | string | Sí | — | Código comercial; índice no único globalmente. |
| `name` | string | No | — | Nombre de entidad. |
| `slug` | string | No | UNIQUE | Identificador legible de URL. |
| `short_description` | text | Sí | — | Descripción breve del producto. |
| `description` | text | Sí | — | Descripción editorial o resumen histórico. |
| `product_type` | string | No | default 'simple' | simple o variable. |
| `sale_unit` | string | No | default 'unit' | Unidad enum; en variante NULL hereda. |
| `purchase_mode` | string | No | default 'calculated' | calculated o inquiry; en variante NULL hereda. |
| `price` | bigInteger | Sí | — | Precio entero en centavos; NULL desconocido/no aplicable. |
| `stock` | bigInteger | Sí | — | Stock entero en milésimas; cast expone texto decimal. |
| `status` | string | No | default 'draft' | Estado del producto, pedido o ejecución. |
| `featured` | boolean | No | default false | Selección como destacado; no publica por sí sola. |
| `external_synced_at` | timestamp | Sí | — | Marca de gestión comercial externa. |
| `created_at` | timestamp | Sí | — | Fecha de creación. |
| `updated_at` | timestamp | Sí | — | Última actualización. |

Claves e índices adicionales (declaración exacta):

- `index('external_id')`.
- `index('sku')`.
- `index(['status', 'featured'])`.
- `index('name')`.

## A.10 `product_variants`

Presentación comercial perteneciente a producto variable.

Migraciones: `2026_09_24_000100_create_catalog_tables.php`.

| Campo | Tipo | NULL | Restricciones / default | Significado |
|---|---|---|---|---|
| `id` | bigint autoincremental | No | PK | Identificador primario del registro. |
| `product_id` | foreignId | No | FK products.id; DELETE CASCADE | Producto padre o referencia opcional del snapshot. |
| `external_id` | string | Sí | — | Identificador externo opcional; no es la clave de coincidencia del importador SKU. |
| `sku` | string | Sí | — | Código comercial; índice no único globalmente. |
| `name` | string | No | — | Nombre de entidad. |
| `value` | string | Sí | — | Valor de configuración/caché o descriptor de variante según tabla. |
| `sale_unit` | string | Sí | — | Unidad enum; en variante NULL hereda. |
| `purchase_mode` | string | Sí | — | calculated o inquiry; en variante NULL hereda. |
| `price` | bigInteger | Sí | — | Precio entero en centavos; NULL desconocido/no aplicable. |
| `stock` | bigInteger | Sí | — | Stock entero en milésimas; cast expone texto decimal. |
| `active` | boolean | No | default true | Habilitación del registro según el módulo. |
| `external_synced_at` | timestamp | Sí | — | Marca de gestión comercial externa. |
| `created_at` | timestamp | Sí | — | Fecha de creación. |
| `updated_at` | timestamp | Sí | — | Última actualización. |

Claves e índices adicionales (declaración exacta):

- `index('external_id')`.
- `index('sku')`.
- `index(['product_id', 'active'])`.

## A.11 `categories`

Árbol de categorías.

Migraciones: `2026_09_24_000100_create_catalog_tables.php`, `2026_09_25_005706_add_featured_to_categories_table.php`.

| Campo | Tipo | NULL | Restricciones / default | Significado |
|---|---|---|---|---|
| `id` | bigint autoincremental | No | PK | Identificador primario del registro. |
| `parent_id` | foreignId | Sí | FK categories.id; DELETE SET NULL | Categoría superior; NULL raíz. |
| `name` | string | No | — | Nombre de entidad. |
| `slug` | string | No | UNIQUE | Identificador legible de URL. |
| `description` | text | Sí | — | Descripción editorial o resumen histórico. |
| `image_path` | string | Sí | — | Ruta relativa del archivo normalizado. |
| `active` | boolean | No | default true | Habilitación del registro según el módulo. |
| `sort_order` | unsignedInteger | No | default 0 | Posición de presentación. |
| `created_at` | timestamp | Sí | — | Fecha de creación. |
| `updated_at` | timestamp | Sí | — | Última actualización. |
| `featured` | boolean | No | default false | Selección como destacado; no publica por sí sola. |

Claves e índices adicionales (declaración exacta):

- `index(['parent_id', 'sort_order'])`.

## A.12 `category_product`

Relación muchos-a-muchos entre catálogo y categorías.

Migraciones: `2026_09_24_000100_create_catalog_tables.php`.

| Campo | Tipo | NULL | Restricciones / default | Significado |
|---|---|---|---|---|
| `product_id` | foreignId | No | FK products.id; DELETE CASCADE | Producto padre o referencia opcional del snapshot. |
| `category_id` | foreignId | No | FK categories.id; DELETE CASCADE | Categoría vinculada. |

Claves e índices adicionales (declaración exacta):

- `primary(['product_id', 'category_id'])`.
- `index('category_id')`.

## A.13 `product_images`

Galería privada y orden de imágenes.

Migraciones: `2026_09_24_000100_create_catalog_tables.php`.

| Campo | Tipo | NULL | Restricciones / default | Significado |
|---|---|---|---|---|
| `id` | bigint autoincremental | No | PK | Identificador primario del registro. |
| `product_id` | foreignId | No | FK products.id; DELETE CASCADE | Producto padre o referencia opcional del snapshot. |
| `path` | string | No | — | Ruta relativa UUID de imagen. |
| `alt_text` | string | Sí | — | Texto alternativo. |
| `is_primary` | boolean | No | default false | Marca de imagen principal gestionada por el servicio. |
| `sort_order` | unsignedInteger | No | default 0 | Posición de presentación. |
| `created_at` | timestamp | Sí | — | Fecha de creación. |
| `updated_at` | timestamp | Sí | — | Última actualización. |

Claves e índices adicionales (declaración exacta):

- `index(['product_id', 'sort_order'])`.

## A.14 `orders`

Cabecera y contacto congelado del pedido.

Migraciones: `2026_09_24_000200_create_order_tables.php`, `2026_09_25_015934_add_checkout_key_to_orders_table.php`.

| Campo | Tipo | NULL | Restricciones / default | Significado |
|---|---|---|---|---|
| `id` | bigint autoincremental | No | PK | Identificador primario del registro. |
| `order_number` | string | No | UNIQUE | Número público ENS-ULID. |
| `user_id` | foreignId | Sí | FK users.id; DELETE SET NULL | Usuario relacionado; ver regla de borrado. |
| `customer_first_name` | string | No | — | Nombre del contacto copiado al confirmar. |
| `customer_last_name` | string | No | — | Apellido copiado al confirmar. |
| `customer_phone` | string | No | — | Teléfono copiado al confirmar. |
| `customer_email` | string | Sí | — | Correo opcional copiado al confirmar. |
| `status` | string | No | default 'received' | Estado del producto, pedido o ejecución. |
| `subtotal` | bigInteger | No | — | Importe en centavos; en línea puede ser NULL pendiente. |
| `confirmed_total` | bigInteger | No | — | Suma confirmada del pedido, sin líneas pendientes. |
| `has_items_to_confirm` | boolean | No | default false | Indica líneas que requieren cotización. |
| `fulfillment_method` | string | No | default 'pickup' | pickup, única modalidad actual. |
| `created_at` | timestamp | Sí | — | Fecha de creación. |
| `updated_at` | timestamp | Sí | — | Última actualización. |
| `checkout_key` | string(64) | Sí | UNIQUE | Hash SHA-256 del token para idempotencia, único y anulable en datos antiguos. |

Claves e índices adicionales (declaración exacta):

- `index(['user_id', 'created_at'])`.
- `index(['status', 'created_at'])`.

## A.15 `order_items`

Líneas con snapshots comerciales.

Migraciones: `2026_09_24_000200_create_order_tables.php`.

| Campo | Tipo | NULL | Restricciones / default | Significado |
|---|---|---|---|---|
| `id` | bigint autoincremental | No | PK | Identificador primario del registro. |
| `order_id` | foreignId | No | FK orders.id; DELETE RESTRICT | Pedido relacionado. |
| `product_id` | foreignId | Sí | FK products.id; DELETE SET NULL | Producto padre o referencia opcional del snapshot. |
| `product_variant_id` | foreignId | Sí | FK product_variants.id; DELETE SET NULL | Variante relacionada, anulable en snapshot. |
| `product_name` | string | No | — | Nombre de producto congelado. |
| `variant_name` | string | Sí | — | Nombre de variante congelado. |
| `sku` | string | Sí | — | Código comercial; índice no único globalmente. |
| `sale_unit` | string | No | — | Unidad enum; en variante NULL hereda. |
| `quantity` | bigInteger | No | — | Cantidad en milésimas, positiva en líneas de pedido. |
| `unit_price` | bigInteger | Sí | — | Precio unitario congelado en centavos. |
| `subtotal` | bigInteger | Sí | — | Importe en centavos; en línea puede ser NULL pendiente. |
| `requires_confirmation` | boolean | No | default false | Línea sin importe definitivo. |
| `created_at` | timestamp | Sí | — | Fecha de creación. |
| `updated_at` | timestamp | Sí | — | Última actualización. |

## A.16 `order_status_history`

Transiciones de estado del pedido.

Migraciones: `2026_09_24_000200_create_order_tables.php`.

| Campo | Tipo | NULL | Restricciones / default | Significado |
|---|---|---|---|---|
| `id` | bigint autoincremental | No | PK | Identificador primario del registro. |
| `order_id` | foreignId | No | FK orders.id; DELETE RESTRICT | Pedido relacionado. |
| `previous_status` | string | Sí | — | Estado anterior; NULL al crear. |
| `new_status` | string | No | — | Estado resultante. |
| `changed_by` | foreignId | Sí | FK users.id; DELETE SET NULL | Usuario que realizó transición; opcional. |
| `created_at` | timestamp | No | fecha actual | Fecha de creación. |

Claves e índices adicionales (declaración exacta):

- `index(['order_id', 'created_at'])`.

## A.17 `notifications`

Avisos internos dirigidos a usuarios.

Migraciones: `2026_09_24_000300_create_content_and_operations_tables.php`, `2026_09_25_204634_add_order_link_to_notifications_table.php`.

| Campo | Tipo | NULL | Restricciones / default | Significado |
|---|---|---|---|---|
| `id` | bigint autoincremental | No | PK | Identificador primario del registro. |
| `title` | string | No | — | Título visible. |
| `message` | text | No | — | Texto del aviso. |
| `type` | string | No | — | Tipo de sección o notificación según tabla. |
| `created_by` | foreignId | Sí | FK users.id; DELETE SET NULL | Creador opcional del aviso. |
| `published_at` | timestamp | Sí | — | Fecha desde la cual es visible el aviso. |
| `expires_at` | timestamp | Sí | — | Vencimiento del aviso. |
| `active` | boolean | No | default false | Habilitación del registro según el módulo. |
| `created_at` | timestamp | Sí | — | Fecha de creación. |
| `updated_at` | timestamp | Sí | — | Última actualización. |
| `order_id` | foreignId | Sí | FK orders.id; DELETE SET NULL | Pedido relacionado. |

Claves e índices adicionales (declaración exacta):

- `index(['active', 'published_at'])`.
- `unique(['order_id', 'type'])`.

## A.18 `notification_user`

Destinatarios y fecha de lectura individual.

Migraciones: `2026_09_24_000300_create_content_and_operations_tables.php`.

| Campo | Tipo | NULL | Restricciones / default | Significado |
|---|---|---|---|---|
| `notification_id` | foreignId | No | FK notifications.id; DELETE CASCADE | Aviso relacionado. |
| `user_id` | foreignId | No | FK users.id; DELETE CASCADE | Usuario relacionado; ver regla de borrado. |
| `read_at` | timestamp | Sí | — | Fecha de lectura por destinatario. |

Claves e índices adicionales (declaración exacta):

- `primary(['notification_id', 'user_id'])`.
- `index(['user_id', 'read_at'])`.

## A.19 `homepage_sections`

Bloques de inicio configurables.

Migraciones: `2026_09_24_000300_create_content_and_operations_tables.php`.

| Campo | Tipo | NULL | Restricciones / default | Significado |
|---|---|---|---|---|
| `id` | bigint autoincremental | No | PK | Identificador primario del registro. |
| `type` | string | No | — | Tipo de sección o notificación según tabla. |
| `title` | string | Sí | — | Título visible. |
| `subtitle` | string | Sí | — | Subtítulo. |
| `content` | text | Sí | — | Texto de sección, mostrado escapado. |
| `image_path` | string | Sí | — | Ruta relativa del archivo normalizado. |
| `button_text` | string | Sí | — | Etiqueta de enlace. |
| `button_url` | string | Sí | — | Ruta interna validada. |
| `settings` | json | Sí | — | Opciones JSON del bloque; actualmente max_items. |
| `sort_order` | unsignedInteger | No | default 0 | Posición de presentación. |
| `active` | boolean | No | default false | Habilitación del registro según el módulo. |
| `created_at` | timestamp | Sí | — | Fecha de creación. |
| `updated_at` | timestamp | Sí | — | Última actualización. |

Claves e índices adicionales (declaración exacta):

- `index(['active', 'sort_order'])`.

## A.20 `banners`

Banners con imagen y visibilidad.

Migraciones: `2026_09_24_000300_create_content_and_operations_tables.php`.

| Campo | Tipo | NULL | Restricciones / default | Significado |
|---|---|---|---|---|
| `id` | bigint autoincremental | No | PK | Identificador primario del registro. |
| `title` | string | Sí | — | Título visible. |
| `subtitle` | string | Sí | — | Subtítulo. |
| `image_path` | string | No | — | Ruta relativa del archivo normalizado. |
| `button_text` | string | Sí | — | Etiqueta de enlace. |
| `button_url` | string | Sí | — | Ruta interna validada. |
| `starts_at` | timestamp | Sí | — | Inicio de vigencia; no editable por el formulario CMS actual. |
| `ends_at` | timestamp | Sí | — | Fin exclusivo de vigencia; no editable por el formulario CMS actual. |
| `active` | boolean | No | default false | Habilitación del registro según el módulo. |
| `sort_order` | unsignedInteger | No | default 0 | Posición de presentación. |
| `created_at` | timestamp | Sí | — | Fecha de creación. |
| `updated_at` | timestamp | Sí | — | Última actualización. |

Claves e índices adicionales (declaración exacta):

- `index(['active', 'sort_order'])`.

## A.21 `settings`

Valores de configuración por clave.

Migraciones: `2026_09_24_000300_create_content_and_operations_tables.php`.

| Campo | Tipo | NULL | Restricciones / default | Significado |
|---|---|---|---|---|
| `id` | bigint autoincremental | No | PK | Identificador primario del registro. |
| `key` | string | No | UNIQUE | Clave de caché o configuración. |
| `value` | text | Sí | — | Valor de configuración/caché o descriptor de variante según tabla. |
| `created_at` | timestamp | Sí | — | Fecha de creación. |
| `updated_at` | timestamp | Sí | — | Última actualización. |

## A.22 `sync_runs`

Registro de planificación y aplicación de importaciones.

Migraciones: `2026_09_24_000300_create_content_and_operations_tables.php`.

| Campo | Tipo | NULL | Restricciones / default | Significado |
|---|---|---|---|---|
| `id` | bigint autoincremental | No | PK | Identificador primario del registro. |
| `source` | string | No | — | Origen lógico de importación. |
| `started_at` | timestamp | No | — | Inicio de ejecución. |
| `finished_at` | timestamp | Sí | — | Finalización de lote o ejecución. |
| `status` | string | No | default 'running' | Estado del producto, pedido o ejecución. |
| `processed_count` | unsignedInteger | No | default 0 | Registros analizados. |
| `created_count` | unsignedInteger | No | default 0 | Altas aplicadas. |
| `updated_count` | unsignedInteger | No | default 0 | Actualizaciones aplicadas. |
| `error_count` | unsignedInteger | No | default 0 | Filas inválidas/conflictivas o fallo de ejecución. |
| `error_details` | json | Sí | — | JSON de preview/apply, actor, filas, huella y errores; no solo errores. |
| `created_at` | timestamp | Sí | — | Fecha de creación. |
| `updated_at` | timestamp | Sí | — | Última actualización. |

Claves e índices adicionales (declaración exacta):

- `index(['source', 'started_at'])`.

## A.23 `activity_logs`

Trazabilidad de operaciones seleccionadas.

Migraciones: `2026_09_24_000300_create_content_and_operations_tables.php`, `2026_10_08_130351_extend_activity_logs_for_auditing.php`.

| Campo | Tipo | NULL | Restricciones / default | Significado |
|---|---|---|---|---|
| `id` | bigint autoincremental | No | PK | Identificador primario del registro. |
| `user_id` | foreignId | Sí | FK users.id; DELETE SET NULL | Usuario relacionado; ver regla de borrado. |
| `action` | string | No | — | Acción auditada. |
| `entity_type` | string | Sí | — | Tipo descriptivo de entidad. |
| `entity_id` | string | Sí | — | Identificador descriptivo sin FK. |
| `description` | text | Sí | — | Descripción editorial o resumen histórico. |
| `metadata` | json | Sí | — | Metadatos heredados; no expuestos libremente en vista de auditoría. |
| `created_at` | timestamp | No | fecha actual | Fecha de creación. |
| `actor_id` | unsignedBigInteger | Sí | — | ID histórico sin FK. |
| `actor_name` | string | Sí | — | Nombre histórico del actor. |
| `actor_role` | string | Sí | — | Rol histórico del actor. |
| `module` | string | No | default 'legacy' | Módulo de auditoría. |
| `result` | string | No | default 'unknown' | success/rejected o valor legacy unknown. |
| `origin` | string | No | default 'legacy' | admin/system/external o legacy. |
| `before_values` | json | Sí | — | Campos permitidos anteriores al cambio. |
| `after_values` | json | Sí | — | Campos permitidos posteriores al cambio. |

Claves e índices adicionales (declaración exacta):

- `index(['entity_type', 'entity_id'])`.
- `index(['user_id', 'created_at'])`.
- `index(['actor_id', 'created_at'])`.
- `index(['created_at', 'id'])`.
- `index(['action', 'created_at'])`.
- `index(['module', 'created_at'])`.
- `index(['result', 'created_at'])`.
