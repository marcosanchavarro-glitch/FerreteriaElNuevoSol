# 8. Módulo de productos

## 8.1 Producto, variante y origen

`Product` distingue `simple` y `variable`. Un simple guarda precio y stock propios; un variable los guarda únicamente en sus variantes y debe tener ambos campos NULL en el padre. Los estados son `draft`, `published` y `private`. `featured` selecciona destacados, sin publicar por sí mismo.

Las unidades son `unit`, `meter`, `kilogram`, `gram`, `liter`, `milliliter`, `box`, `package`, `bundle`, `roll` y `other`. `calculated` significa importe calculable; `inquiry` requiere confirmación. Una variante puede heredar unidad y modalidad del producto cuando sus campos son NULL. Precio y stock no se heredan del padre variable.

Un producto es externo si `external_id` o `external_synced_at` no es NULL. Una variante también lo es cuando su padre es externo. Por eso una importación comercial puede cambiar las posibilidades de edición posterior aunque no asigne external_id. La importación marca `external_synced_at` y no altera los campos editoriales de entidades existentes.

## 8.2 Flujo de alta y edición

1. `GET /admin/products/create` autoriza creación y presenta `admin.products.form` con valores iniciales borrador/consulta.
2. `POST /admin/products` ejecuta `ProductRequest`, convierte el precio ingresado en centavos y normaliza stock decimal.
3. `ProductController::store()` crea el modelo y el evento de auditoría en la misma transacción; redirige a su detalle.
4. `GET /admin/products/{product}/edit` presenta campos autorizados. `PUT/PATCH /admin/products/{product}` vuelve a validar después de adquirir la escritura del registro y refrescarlo.
5. Categorías, imágenes y variantes tienen formularios y controladores propios; no se persisten como un único JSON masivo junto al producto.

El slug es obligatorio, único y de segmentos minúsculos separados por guiones. La descripción corta admite 500 caracteres y la extensa 20000. El simple publicado y calculable requiere precio. No puede convertirse en simple un producto que aún tiene variantes. Tampoco se habilita herencia calculable para variantes activas sin precio. Valores nulos significan desconocido o pendiente, no cero.

## 8.3 Variantes y categorías

`VariantController`, `VariantRequest` y `VariantManager` atienden las rutas `products/{product}/variants`. La solicitud prohíbe cambiar padre, IDs externos y metadatos de control. Una variante activa calculable requiere precio; el servidor verifica que el padre sea variable. No hay ruta de borrado de variantes.

`CategoryManager` gestiona jerarquía, orden y visibilidad. `Category` comprueba ciclos; `ProductCategoryController` administra la relación muchos-a-muchos mediante `ProductCategoriesRequest`. PublicCategories excluye ramas con ancestros inactivos. Una categoría oculta no convierte automáticamente el producto en privado; la publicación del producto es otra decisión.

## 8.4 Galería

`ProductImageController` delega cargas, selección principal, movimiento y eliminación a `ProductGallery`. Los archivos se procesan con `ImageStorage`; `ImageUploadRequest` rechaza IDs/rutas/flags manipulados. La principal y el orden se gestionan por operaciones específicas, no aceptando libremente `is_primary` o `sort_order` desde una carga. `product_images` conserva ruta relativa y texto alternativo. La ruta anidada y las reglas de almacenamiento impiden operar una imagen de otro producto.

Vistas: `admin/products/{index,form,show,categories,images,sections}.blade.php`, `admin/variants/` y `admin/categories/`. El listado interno pagina 15 productos y carga el conteo de variantes. Pruebas relacionadas: ProductManagementTest, VariantManagementTest, CategoryManagementTest e ImageManagementTest.

## 8.5 Ronda administrativa local

En codex/admin-corrections el listado principal utiliza tarjetas con foto principal real cuando existe (o indicador sin fotografía), nombre, SKU, origen, precio, categorías, unidad, modalidad, disponibilidad, publicación y destacado. Se conserva tabla alternativa y preferencia local del navegador. La paginación permanece en 15; búsqueda y filtros de categoría, modalidad, publicación, stock conocido/cero/desconocido, destacado y orden se resuelven en SQL. Para variables, el filtro de stock coincide con alguna variante activa; no representa una reserva o disponibilidad consolidada.

El listado precarga categorías y solo una imagen por producto, además de contar variantes. Las acciones rápidas POST de publicación y destacado autorizan, bloquean escritura y auditan; publicar un simple calculable sin precio se rechaza. No modifican precio, stock ni campos externos.

El editor reúne información esencial, categorías, imágenes y variantes en una vista. Las secciones conservan formularios independientes y controladores existentes. El selector de categorías permite búsqueda y jerarquía, asociación/desasociación y alta de categoría con permisos; la nueva categoría se selecciona y asocia explícitamente después. La galería conserva validación, vista previa local, principal, orden y eliminación confirmada. Variantes se muestran en detalles expandibles paginados de 20; no se consultan variantes para un simple. Las rutas independientes siguen disponibles.

JavaScript mejora el guardado existente: FormData con CSRF, respuestas JSON de confirmación después del commit, errores 422 junto a campos, bloqueo de doble envío y conservación de entradas. Solo la sección afectada se refresca; el producto principal mantiene sus entradas sin recargar. No existe autoguardado ni transacción conjunta entre las secciones. Se advierte al salir con cambios pendientes y se evita sustituir una sección que tiene otros formularios modificados sin guardar. Los formularios HTML conservan redirecciones. Los productos nuevos se crean antes de habilitar sus relaciones y el editor genera un slug inicial, sujeto a validación de unicidad; el slug existente no cambia automáticamente.

La preferencia de vista se conserva en localStorage y el contexto de retorno (URL, filtros, página y desplazamiento) en sessionStorage, con degradación si el navegador impide almacenamiento. Las modalidades reutilizan inquiry/calculated: consulta no promete disponibilidad confirmada; calculable conserva los controles de stock conocido, sin reservas ni descuentos. No se creó un tercer modelo comercial ni se habilitó Punto de Venta.
