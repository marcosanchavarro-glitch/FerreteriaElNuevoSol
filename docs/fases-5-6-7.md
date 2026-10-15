# Fases 5, 6 y 7 — Variantes, categorías e imágenes

Estado: completadas y verificadas. No se inició la Fase 8.

## Resultado y verificación

- Suite previa ejecutada antes de modificar: **124 tests / 489 aserciones**, todos aprobados.
- Suite final: **202 tests / 1061 aserciones**, todos aprobados; 78 casos nuevos.
- PHPUnit cubre la regresión de las fases anteriores, permisos y reglas nuevas. Las pruebas de imágenes usan `Storage::fake('catalog')` y archivos reales generados con GD.
- `migrate:fresh --seed` completado en una SQLite nueva y aislada dentro de `.tools/`; no se vació la base de trabajo.
- `artisan migrate` aplicado a la base local, conservando sus datos.
- Plantillas Blade compiladas correctamente y caché de vistas limpiada.
- Laravel Pint ejecutado con `--dirty --format agent`.
- Revisión visual en navegador local: edición por secciones, variantes de pintura, selección múltiple de categorías, árbol administrativo y formulario de imágenes. Los flujos de carga, cambio de principal, orden y eliminación se verificaron mediante pruebas HTTP automatizadas.
- Los 14 archivos del frontend WordPress coinciden por SHA-256 con el ZIP original.

## Fase 5: variantes y unidades

Se reutilizan `ProductVariant`, `product_variants`, `SaleUnit` y `PurchaseMode`. Desde cada producto se accede a una sección de variantes con listado paginado, creación, consulta y edición. El formulario permite activar/desactivar; no existe ruta de eliminación. Se conservan referencias y snapshots de pedidos.

El padre debe ser de tipo variable y mantener precio/stock nulos. Cada presentación tiene sus propios valores. Las unidades y modalidades se muestran con etiquetas en español, incluidas **Compra calculable** y **Requiere confirmación**.

Precio se recibe como texto decimal en pesos y se convierte exactamente a centavos enteros. Stock conserva la escala de tres decimales de Fase 2. Se admiten coma o punto decimal, sin separadores de miles, notación científica ni valores negativos en la carga manual. Precio y stock pueden estar vacíos cuando corresponde. No se usa float.

`effectiveSaleUnit()` y `effectivePurchaseMode()` resuelven el valor particular de la variante o el heredado del producto. Una variante activa calculable necesita precio, incluso si la modalidad es heredada; cero es un precio válido. También se impide cambiar el padre a calculable cuando dejaría variantes activas heredadas sin precio. Omitir un override al actualizar lo conserva; enviar null restaura la herencia.

### Permisos

| Acción | Admin | Seller | Customer |
| --- | --- | --- | --- |
| Consultar variantes | Sí | Sí | No |
| Crear/editar datos web de variantes locales | Sí | Sí | No |
| SKU, precio y stock locales | Sí | No | No |
| Modificar variantes externas | No | No | No |
| Categorías y asociaciones | Sí | Sí | No |
| Imágenes de productos y categorías | Sí | Sí | No |

Una variante es externa si tiene `external_id`, `external_synced_at` o su padre es externo. Se decidió conservar todas las variantes externas en solo lectura, incluido su estado, y no permitir crearlas manualmente bajo un padre externo. La información web del producto padre, categorías e imágenes sigue siendo editable según las políticas previas. No existe sincronización real.

El servidor rechaza campos externos, identificadores forjados y cambios comerciales de Seller aunque se envíen fuera del formulario. Las rutas anidadas comprueban pertenencia al producto; un identificador de otro producto devuelve 404.

### SKU

Los formularios locales impiden repetir un SKU no vacío **dentro del mismo producto**, incluyendo presentaciones inactivas; editar conservando el propio SKU es válido. Se permiten SKU vacíos y códigos iguales en productos distintos. `VariantManager` vuelve a comprobar la unicidad después de serializar las escrituras del padre.

No se agrega una restricción global de SKU a la base, ni se cambian las reglas de los productos de Fase 4: se desconocen las reglas del Punto de Venta. La futura integración deberá definir su identidad externa antes de imponer una unicidad adicional.

## Fase 6: categorías

`/admin/categories` ofrece un árbol con niveles recursivos, orden entre hermanas, estado, destacado y cantidad de productos asociados directamente. Se puede crear, editar, elegir padre, activar/desactivar y cambiar el orden. Los empates se ordenan por nombre e ID. La representación se construye con una consulta del árbol y un recorrido iterativo, sin un límite artificial de dos niveles.

`CategoryManager` centraliza el guardado transaccional. La validación de jerarquía del modelo recorre los ancestros y rechaza tanto padre propio como descendientes; el formulario devuelve un error legible. Las pruebas cubren seis niveles, cambios de padre y ciclos.

No hay borrado administrativo. Desactivar una categoría conserva sus hijos, productos y asociaciones; cada hija mantiene su propio estado. El catálogo futuro deberá definir expresamente cómo trata un ancestro inactivo.

Desde la sección Categorías de cada producto se guardan múltiples asociaciones mediante `category_product`, sin duplicar productos. Se pueden quitar todas. Seleccionar una hija no agrega automáticamente sus ancestros. Las categorías inactivas son visibles y seleccionables en el administrador para conservar la organización editorial.

Se añadió `featured` y se reutilizó `image_path`. La imagen representativa puede cargarse, reemplazarse y eliminarse después de crear la categoría, con la misma infraestructura segura que la galería. Reemplazarla elimina el archivo anterior.

## Fase 7: imágenes

Se reutilizan `ProductImage` y `product_images`. La galería permite subir una imagen por envío, previsualizarla, editar su alt, designar principal, mover antes/después y eliminarla. El alt es opcional (máximo 255 caracteres); no se inventa texto cuando está vacío.

### Almacenamiento y validación

- Disco Laravel `catalog`, privado, en `storage/app/catalog`.
- Rutas guardadas en base: `products/{id}/{uuid}.webp` o `categories/{id}/{uuid}.webp`.
- Sin binarios en SQLite, rutas absolutas ni uso del nombre original.
- Entradas JPEG/JPG, PNG y WebP; máximo **5 MiB (5120 KiB)** y **3000 × 3000 píxeles**. La interfaz lo expresa como 5 MB.
- Se valida extensión, MIME inferido, tamaño, dimensiones y decodificación real con GD.
- Conversión a WebP de calidad 85, conservando transparencia y descartando metadatos y contenido añadido al archivo. Se conserva la orientación de píxeles decodificada; no hay rotación automática por EXIF ni recorte.
- SVG, GIF, archivos ejecutables y contenido corrupto son rechazados.
- Las rutas se validan contra la carpeta del registro para lectura, borrado y recuperación. No se admiten rutas aportadas por formularios.
- Previsualización por rutas administrativas autenticadas, con `image/webp`, `nosniff` y caché privada sin almacenamiento.
- No se expone el disco mediante symlink ni se crean URLs públicas.

### Principal, orden y eliminación

`ProductGallery` serializa cambios mediante una escritura en el producto padre dentro de la transacción. La primera imagen es principal; elegir otra desmarca la anterior antes de marcar la nueva. El orden usa `sort_order`, con ID como desempate; mover renumera de forma consecutiva y no cambia la principal.

Eliminar la principal promueve la primera restante según ese orden. Eliminar la última deja la galería vacía. Se elimina el registro y el archivo; si el borrado de Storage falla, la operación revierte la base. Se conserva temporalmente el contenido anterior para restaurarlo ante excepciones, también en reemplazos de imagen de categoría. Las pruebas simulan fallos del disco y comprueban referencias, archivos y principal.

Los futuros importadores y acciones de auditoría deben usar estos servicios de escritura; insertar filas directamente o hacer actualizaciones masivas puede omitir estas reglas de aplicación. No se implementó auditoría completa ni una transacción distribuida entre filesystem y SQLite.

### Requisitos de PHP

GD con JPEG, PNG y WebP es necesario para normalizar imágenes. Se habilitó la extensión ya incluida en el PHP portátil de este equipo. Se configuró `upload_max_filesize=5M`, con `post_max_size=8M` y `memory_limit=512M`, y se reinició el servidor local.

En otra instalación, habilitar GD con estos formatos y configurar límites compatibles antes de usar uploads. `.tools/php/php.ini` es configuración local ignorada por Git; no se añadieron paquetes de Composer ni dependencias JavaScript. El servicio devuelve un error comprensible si GD/WebP no está disponible.

## Base de datos

Nueva migración: `backend/database/migrations/2026_09_25_005706_add_featured_to_categories_table.php`.

Agrega únicamente `categories.featured`, booleano con valor inicial false; la reversión quita ese campo. Se mantienen las tablas y relaciones anteriores. No se reestructuraron precios, stock, imágenes ni variantes.

## Principales archivos

Todos los siguientes son relativos a `backend/`:

- Modelos: `app/Models/ProductVariant.php`, `app/Models/Category.php`.
- Políticas: `app/Policies/ProductVariantPolicy.php`, `app/Policies/CategoryPolicy.php`; se conserva `ProductPolicy`.
- Validación: `app/Http/Requests/VariantRequest.php`, `CategoryRequest.php`, `ProductCategoriesRequest.php`, `ImageUploadRequest.php`, `ImageUpdateRequest.php`; ampliación de `ProductRequest.php`.
- Acciones: `app/Services/Catalog/VariantManager.php`, `CategoryManager.php`, `ProductGallery.php`, `CategoryImages.php`, `ImageStorage.php`.
- Controladores: `app/Http/Controllers/Admin/VariantController.php`, `CategoryController.php`, `ProductCategoryController.php`, `ProductImageController.php`, `CategoryImageController.php`; actualización de `ProductController.php`.
- Rutas y Storage: `routes/web.php`, `config/filesystems.php`.
- Interfaz: `resources/views/admin/variants/`, `admin/categories/`, `admin/products/sections.blade.php`, `categories.blade.php`, `images.blade.php`, formularios y consulta existentes; navegación, inicio administrativo y `public/css/internal.css`.
- Etiquetas: `app/Support/ProductDisplay.php`.
- Pruebas: `tests/Feature/VariantManagementTest.php`, `CategoryManagementTest.php`, `ImageManagementTest.php`.
- Documentación: este informe y los README de raíz y backend.

## Decisiones para fases posteriores

El catálogo deberá consumir los precios/stock de la variante elegida y los helpers de herencia, respetar sus estados y la modalidad de compra. Los padres variables no ofrecen importes propios. Se deberá definir filtrado público de categorías activas/ancestros, uso de destacados y exposición pública de imágenes mediante un mecanismo deliberado. Las rutas actuales de imagen requieren autenticación; no deben copiarse directamente al catálogo público.

Quedan para sus fases: catálogo y selección pública de variantes, frontend legacy, búsqueda/filtros públicos, carrito, checkout, pedidos completos, WhatsApp, CMS, banners, notificaciones, auditoría completa, integración Punto de Venta/WooCommerce, pagos y delivery. La recuperación de contraseña sigue preparada con `MAIL_MAILER=array`, sin envío real, según lo indicado.

**Trabajo detenido al finalizar la Fase 7.**
