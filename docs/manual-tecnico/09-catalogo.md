# 9. Catálogo público

## 9.1 Consultas y presentación

`CatalogController` expone inicio, listado y detalle; `CatalogQuery` compone consultas; `ProductPresentation` calcula los datos de oferta mostrados; `PublicImages` resuelve imágenes y placeholder; `PublicCategories` construye el árbol visible. Las vistas están en `resources/views/storefront/` y se integran en `layouts/storefront.blade.php`.

La consulta base limita a productos publicados y carga imágenes ordenadas, variantes activas y categorías visibles. El detalle busca por slug y devuelve 404 para un producto no visible. La selección `variante` debe pertenecer al conjunto activo del producto; por defecto se selecciona la primera. Se muestran hasta tres relacionados por categorías.

## 9.2 Filtros y búsqueda

| Parámetro GET | Valores/regla |
|---|---|
| `q` | Texto hasta 120 caracteres, espacios normalizados |
| `categoria` | Slug de categoría visible |
| `tipo` | `simple`, `variable` |
| `disponibilidad` | `disponible`, `sin-stock`, `consultar` |
| `modalidad` | `calculated`, `inquiry` |
| `unidad` | Valor de SaleUnit |
| `orden` | `relevancia`, `nombre-asc`, `nombre-desc`, `precio-asc`, `precio-desc` |
| `page` | Entero 1–100000 |
| `variante` | ID positivo, usado en detalle |

Cada palabra de búsqueda debe coincidir en alguno de los campos consultados: nombre, SKU, descripciones, variantes activas o categorías visibles. Las palabras se combinan con AND y sus posibles campos con OR. Se escapan comodines `%`, `_` y `!`; no se implementa un buscador externo. PostgreSQL usa LOWER; SQLite registra `catalog_fold` con mb_strtolower para aproximar el tratamiento de mayúsculas.

Para productos variables, los filtros de disponibilidad/modalidad/unidad deben cumplirse en una misma variante activa. La modalidad y unidad pueden heredar del padre mediante COALESCE. El precio de ordenamiento es el del simple calculable o el mínimo de variantes calculables; los precios desconocidos quedan al final. Relevancia prioriza SKU exacto, nombre exacto y prefijo de nombre. No hay índice de búsqueda de texto completo dedicado.

## 9.3 Categorías, páginas y disponibilidad

`/categorias/{slug}` restringe a la rama de esa categoría; otro filtro de categoría se intersecta con ella. La navegación conserva parámetros al paginar de a 12 productos. PublicCategories obtiene las categorías en una consulta y recorre el árbol en memoria, evitando seguir ciclos repetidos. Su caché es de la instancia durante el uso, no un caché persistente de stock.

Stock positivo, agotado y desconocido son estados distintos. Un modo de consulta no debe presentarse como compra a precio cero. El navegador puede deshabilitar un botón por la oferta mostrada, pero `Cart::resolve()` vuelve a consultar y validar al agregar o confirmar. La administración actualiza los mismos modelos que consulta el catálogo, sin un proceso separado de republicación.

Fuentes: `CatalogController`, `CatalogRequest`, `CatalogQuery`, `PublicCategories`, `ProductPresentation`, `public/js/storefront.js`; cobertura: PublicCatalogTest y PublicSearchTest.
