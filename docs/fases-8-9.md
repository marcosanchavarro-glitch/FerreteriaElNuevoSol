# Fases 8 y 9 — Catálogo público, búsqueda y filtros

Estado: completadas y verificadas. Trabajo detenido antes de Fase 10.

## Resultado verificado

- Antes de modificar código: **202 tests / 1061 aserciones**, todos aprobados.
- Resultado final: **250 tests / 1279 aserciones**, todos aprobados. Se añadieron 48 casos y se conservaron las pruebas anteriores.
- Laravel 13.33.0, PHP 8.4 y SQLite; sin nuevas dependencias Composer/npm.
- Laravel Pint ejecutado. Plantillas Blade compiladas correctamente; caché de vistas limpiada. JavaScript validado con `node --check`.
- Base y archivos de demostración aislados; no se publicaron los productos de trabajo ni se modificaron sus precios o estados.
- Los 14 archivos originales del tema siguen coincidiendo por SHA-256 con el ZIP recibido.

## Estrategia de frontend

Se siguió la recomendación de Fase 1: Blade, CSS y JavaScript nativo. Laravel/Eloquent es el backend y SQLite la fuente real de productos, categorías, variantes e imágenes.

Se reutilizaron el CSS `ens-*` y su licencia GPL-2.0-or-later, la paleta azul `#161f55`, naranja `#f0783c` y amarillo `#f7c948`, la familia tipográfica de sistema, estructura de header/buscador/navegación, hero, ilustración SVG de respaldo, tarjetas, promociones, ficha, galería y footer. La hoja copiada conserva atribución y se adapta en `public/css/storefront.css`; el original permanece intacto.

Se reescribieron los documentos como Blade, las URLs con rutas Laravel, las consultas con Eloquent, los precios con la representación exacta existente y la navegación con categorías reales. Se corrigieron apilado móvil de promociones, tamaños de galería, foco visible, contraste de botones, etiquetas, tarjetas enlazadas y selector de categorías funcional.

No se trasladaron hooks, `WP_Query`, metadatos WordPress, personalizador ni PHP legacy al backend. Tampoco se cargó `cart.js`: se reemplazó la interacción de galería y se agregó un selector ligero de variantes sin cálculos comerciales en JavaScript. No se incorporaron WordPress, WooCommerce ni un framework SPA.

El ZIP no contiene logo, fotografías ni banners reales del negocio. Se utilizó un emblema vectorial provisional de sol, el SVG del hero original y un placeholder local adaptado del original. Las ilustraciones y productos del entorno de revisión llevan datos DEMO. La portada conserva contenido editorial temporal, sin CMS ni promociones comerciales inventadas.

## Fase 8: páginas y comportamiento

| Ruta | Función |
| --- | --- |
| `/` | Portada, categorías destacadas y hasta seis productos destacados publicados. |
| `/productos` | Catálogo, búsqueda, filtros y paginación. |
| `/productos/{slug}` | Ficha simple o variable, galería, descripción, categorías y relacionados. |
| `/categorias/{slug}` | Categoría, descripción, imagen, subcategorías, breadcrumbs y productos. |
| `/imagenes/productos/{id}` | Imagen pública si su producto continúa publicado. |
| `/imagenes/categorias/{slug}` | Imagen pública si toda la rama de categoría está activa. |

El header contiene marca, buscador por texto/categoría, navegación real, Ingresar o Mi cuenta y un indicador no interactivo de carrito próximo. Staff conserva acceso a Administración. No hay operaciones de carrito.

### Publicación y productos

Solo se consultan productos `published`. Borradores y privados dan 404 también por URL directa, incluso para Admin en las rutas públicas. Los inexistentes dan una página 404 legible.

Las tarjetas muestran imagen, nombre enlazado, categoría visible, precio, unidad y disponibilidad. Los publicados sin stock permanecen visibles. No se envían al HTML cantidades exactas de stock, identificadores externos, fechas de sincronización ni modelos Eloquent serializados.

- Simple calculable con precio: importe exacto en pesos; cero es válido.
- Modalidad `inquiry` o precio ausente: **Precio a confirmar**, aunque exista un precio interno en un producto inquiry.
- Stock positivo: **Disponible**; cero o negativo: **Sin stock**; nulo: **Consultar disponibilidad**.
- Variable sin variantes activas: **Sin presentaciones disponibles**.

En las tarjetas variables, la disponibilidad resume todas sus presentaciones activas: disponible si alguna tiene stock, consulta si ninguna tiene stock positivo pero alguna lo desconoce, y sin stock si todas tienen stock no positivo. No habilita una operación de compra.

### Variantes y precios

La ficha ofrece únicamente variantes activas. Inicialmente selecciona la primera por ID o la indicada mediante `?variante={id}`. Una variante inactiva o ajena devuelve 404.

El cambio de selección actualiza presentación, precio, disponibilidad, SKU, unidad y modalidad, además de la URL. Se usan textos calculados por PHP y asignaciones `textContent`; no se recalculan importes en el navegador. El formulario GET permite consultar la presentación sin JavaScript. La actualización se anuncia con `aria-live`.

Unidad y modalidad respetan los helpers de herencia de Fase 5. La tarjeta muestra **Desde** el menor precio no nulo de las variantes activas cuya modalidad efectiva sea calculable. Incluye presentaciones sin stock, porque el catálogo también las muestra; disponibilidad se informa por separado. Si hay variantes a confirmar, se aclara en la tarjeta. No se usa el precio del padre variable ni el de variantes inactivas/inquiry.

### Categorías

Una categoría solo es pública si ella y todos sus ancestros están activos. Las ramas con un ancestro inactivo se excluyen de navegación, búsqueda por categoría, asociaciones visibles e imágenes públicas. Esto no cambia el estado administrativo de las hijas.

La página de una categoría incluye productos asignados directamente y a sus descendientes públicos. Se usa `whereHas`, sin joins que multipliquen tarjetas. El selector de categoría dentro de esa página ofrece solo la rama actual; un filtro manipulado de otra rama no amplía sus resultados.

Un producto publicado que solo tenga categorías ocultas sigue disponible en el catálogo general y por su URL, sin mostrar esas asociaciones. La publicación del producto conserva su autoridad independiente.

La portada usa categorías activas destacadas; si no hay destacadas, muestra raíces públicas, hasta ocho accesos. Los relacionados comparten al menos una categoría visible, excluyen el producto actual y los no publicados, y se limitan a tres.

### Galería e imágenes públicas

La principal tiene prioridad; luego se respeta `sort_order` e ID. Las miniaturas son botones con nombre accesible, estado seleccionado y actualización de alt. El texto alternativo cargado se conserva, incluido vacío cuando así se guardó.

El disco `catalog` sigue privado. Las nuevas rutas comprueban la publicación o visibilidad en cada petición; no se creó un symlink público. Se conservan validación de pertenencia/ruta, `nosniff` y respuesta sin caché persistente para no servir contenido retirado desde una caché HTTP. Un archivo ausente o una ruta no gestionada devuelve el placeholder, sin leer rutas arbitrarias ni mostrar errores técnicos.

El placeholder es local; no hay dependencia de imágenes externas. La ficha y las tarjetas también tienen recuperación ante errores de carga en el navegador.

### SEO y accesibilidad

Title y meta description por página, slugs legibles, H1 coherente, breadcrumbs, labels, botones reales, foco visible, alt y salto al contenido. Búsquedas y listados filtrados tienen `noindex,follow`. Los contenidos y atributos se escapan con Blade. No se añadió un administrador SEO.

## Fase 9: búsqueda, filtros y ordenamiento

La búsqueda se integra en `/productos?q=...` y también funciona dentro de una categoría.

Busca por nombre, SKU, descripción corta/larga, nombre/valor/SKU de variantes activas y nombre de categorías públicas asignadas. Los términos separados por espacios deben coincidir todos, pudiendo hacerlo en campos distintos. Se normalizan espacios y mayúsculas/minúsculas; en SQLite se registra una función de comparación Unicode con `mb_strtolower`, por lo que también funciona `MÁQUINA` frente a `máquina`. No hay búsqueda aproximada, corrección ortográfica ni eliminación de tildes.

`%` y `_` se tratan como texto literal. Los valores se enlazan como parámetros; los fragmentos SQL y columnas son constantes controladas por código. No se admiten nombres de columnas ni expresiones de orden enviados por el visitante.

Filtros disponibles:

- `categoria`: slug público, incluye descendientes visibles.
- `disponibilidad`: `disponible`, `sin-stock` o `consultar`.
- `unidad`: valor permitido de `SaleUnit`.
- `modalidad`: `calculated` o `inquiry`.
- `tipo`: `simple` o `variable`.

Para variables, disponibilidad, unidad y modalidad deben coincidir sobre **la misma variante activa**, con herencia del padre cuando corresponde. No se combinan accidentalmente stock de una variante y unidad de otra. Una tarjeta puede tener otras presentaciones además de la que coincide; la pantalla lo explica y la ficha permite elegir. Sin variantes activas, el producto aparece en el listado general pero no coincide con filtros de ofertas.

Ordenamiento (`orden`): relevancia, nombre A–Z, nombre Z–A, precio ascendente y descendente. Relevancia prioriza SKU exacto del producto, nombre exacto y prefijo del nombre; los demás resultados se ordenan por nombre e ID. Sin texto de búsqueda, relevancia equivale a nombre A–Z.

El precio de ordenamiento coincide con la estrategia de la tarjeta: precio calculable simple o menor precio calculable activo de variantes, sin exigir stock positivo. Los precios desconocidos/inquiry van al final en ambas direcciones. No se añadió rango de precio: queda documentada su postergación para evitar confundir el rango de una presentación con el precio mínimo general de la tarjeta.

Paginación de 12 productos con total y enlaces anterior/siguiente. Se conservan únicamente filtros validados en sus enlaces; los cambios de filtro vuelven a la primera página. Valores inválidos devuelven una pantalla 422 legible; categorías inexistentes/inactivas devuelven 404. Una búsqueda vacía de resultados ofrece volver al catálogo y revisar términos.

## Rendimiento

Los productos se paginan en SQL. Imágenes, variantes activas y categorías visibles usan eager loading; no se realiza una consulta SQL por tarjeta. La prueba de regresión compara el conteo con una y once tarjetas y exige una cota de diez consultas para ese caso. Relacionados y destacados tienen límites explícitos.

El árbol de categorías se obtiene una vez por instancia de la petición y se recorre iterativamente. El mínimo de precio de variantes se obtiene con una subconsulta correlacionada sobre la relación existente. Se conservan índices ya creados sobre producto/estado de variantes y relaciones.

La búsqueda por contenido usa LIKE y está pensada para el tamaño inicial. No se implementó FTS, motor externo, caché global ni serialización del catálogo completo al navegador. No hay una certificación de carga masiva ni de un motor de base distinto de SQLite.

## Verificación visual

Revisión en navegador real con datos DEMO aislados:

- Inicio en escritorio y móvil: hero, marca, navegación, categorías y destacados.
- Productos en escritorio y móvil: filtros, tarjetas, placeholder y paginación.
- Búsqueda desde el header, tolerancia a espacios y mayúsculas, y estado sin resultados.
- Categoría Pinturas en móvil, incluyendo imagen y filtros; jerarquía y descendientes cubiertos también por pruebas HTTP.
- Ficha simple Taladro y cambio de miniatura con actualización de alt.
- Pintura con variantes: 1 L a 4 L cambia precio/código y muestra Sin stock; Color a pedido muestra confirmación sin precio.
- Producto inquiry Corte de caño en tablet: placeholder, disponibilidad desconocida y precio a confirmar.
- Formulario móvil con filtros Metro + Disponible devuelve únicamente el cable correspondiente.

Se probaron viewports de 1440×1000, 820×1180 y 390×844. En portada y catálogo móvil se comprobó que el ancho del contenido no supera el ancho del documento. La navegación horizontal del menú es intencional. Se restauró el tamaño del navegador al terminar.

La comparación tomó como referencia la estructura, CSS, colores y SVG reales del tema recibido. No se afirma una reproducción exacta de un WordPress configurado: ese sitio y sus medios no están incluidos en el ZIP.

## Archivos y base de datos

No se necesitaron migraciones ni tablas nuevas.

Principales incorporaciones, relativas a `backend/`:

- `app/Http/Controllers/Storefront/CatalogController.php` y `MediaController.php`.
- `app/Http/Requests/CatalogRequest.php`.
- `app/Services/Storefront/CatalogQuery.php`, `PublicCategories.php`, `ProductPresentation.php`, `PublicImages.php`.
- `resources/views/layouts/storefront.blade.php`.
- `resources/views/storefront/{home,catalog,product,card,invalid}.blade.php` y `resources/views/errors/404.blade.php`.
- `public/css/storefront.css`, `public/js/storefront.js`, `public/images/{brand,product-placeholder}.svg`.
- `tests/Feature/PublicCatalogTest.php` y `PublicSearchTest.php`; la prueba básica de portada ahora prepara la base con RefreshDatabase.
- `database/seeders/StorefrontPreviewSeeder.php`: optativo, restringido a local/testing y una base llamada `storefront-preview.sqlite`.
- `routes/web.php` y `config/filesystems.php`, con `CATALOG_STORAGE_PATH` opcional para aislar archivos de demostración.

## Acceso y demostración

Aplicación con la base de trabajo: `http://127.0.0.1:8000/`. Solo aparecen los productos que el responsable publique desde el administrador; no se cambiaron los borradores existentes para llenar el catálogo.

Vista de revisión iniciada en este equipo: `http://127.0.0.1:8001/`, con base `.tools/storefront-preview.sqlite` y archivos `.tools/storefront-preview-media`. Sus datos son ficticios y no representan oferta comercial. Ambos directorios están ignorados por Git.

Para reconstruir/iniciar esa demostración en PowerShell desde `backend/`:

```powershell
$env:DB_DATABASE = Join-Path (Resolve-Path ../.tools).Path 'storefront-preview.sqlite'
$env:CATALOG_STORAGE_PATH = Join-Path (Resolve-Path ../.tools).Path 'storefront-preview-media'
if (!(Test-Path -LiteralPath $env:DB_DATABASE)) {
    New-Item -ItemType File -Path $env:DB_DATABASE | Out-Null
}
& ../.tools/php/php.exe artisan migrate
& ../.tools/php/php.exe artisan db:seed --class=StorefrontPreviewSeeder
& ../.tools/php/php.exe artisan serve --host=127.0.0.1 --port=8001 --no-reload
```

Ejecutarlo en una terminal dedicada: esas variables seleccionan la base y el Storage de esa sesión. La demostración no forma parte del seed normal y no publica registros de la base de trabajo. Requiere GD, ya habilitado en las fases anteriores.

## Pendientes deliberados

Fase 10 en adelante: carrito validado por servidor, checkout, pedidos, WhatsApp, pagos, delivery, historial y notificaciones. También continúan pendientes CMS/banners, medios reales del negocio e integración Punto de Venta. El envío real de recuperación de contraseña sigue deshabilitado según la decisión previa.

**No se inició Fase 10, no se creó ningún pedido y no se abrió WhatsApp.**
