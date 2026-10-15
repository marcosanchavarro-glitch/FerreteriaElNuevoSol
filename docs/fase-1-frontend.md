# Fase 1 — Auditoría del frontend recibido

Fecha: 24 de septiembre de 2026. Fuente: `C:/Users/Marco/Downloads/el-nuevo-sol-wordpress-modificado.zip`.

SHA256 del ZIP: `A2763230159CE882FC63FF3331A86F14FC0EABA25F39FE7667B556C6AFFD87DA`.

Se leyó el contenido completo de los 14 archivos del tema. Se conservaron sin modificar en `reference/wordpress/el-nuevo-sol/`. Las instrucciones de instalación del README del tema se trataron como documentación del sistema anterior; no se instaló WordPress.

## A. Estructura actual

Rutas de esta tabla relativas a `reference/wordpress/el-nuevo-sol/`:

| Archivo | Contenido |
| --- | --- |
| `functions.php` | Registro de productos y categorías, administración, metadatos, carga rápida, personalizador, recursos, cabecera, pie y tarjetas. |
| `style.css` | Estilos públicos, variables de marca y reglas responsive; metadatos del tema. |
| `assets/js/cart.js` | Carrito local, cantidades, totales, galería y enlace a WhatsApp. |
| `header.php`, `footer.php` | Documento HTML y puntos de integración de WordPress. |
| `front-page.php` | Hero, imagen por dispositivo, accesos rápidos y dos promociones fijas. |
| `archive-ens_product.php` | Catálogo, categorías y orden por precio. |
| `taxonomy-ens_product_cat.php` | Listado de una categoría. |
| `single-ens_product.php` | Ficha, galería, disponibilidad, marca, unidad, SKU, especificaciones y relacionados. |
| `page-carrito.php` | Carrito y datos para el mensaje de WhatsApp. |
| `page-nosotros.php`, `page-contacto.php` | Páginas institucionales; contacto depende del contenido del editor. |
| `index.php` | Plantilla genérica y fallback. |
| `README.txt` | Instrucciones del tema anterior. |

El ZIP contiene además directorios vacíos, incluido `assets/css/`. No incluye fotos, logo, banners, exportación de base de datos, productos reales, plugins, WordPress core, pruebas ni manifiestos Composer/npm. Los SVG de respaldo están escritos dentro de las plantillas.

## B. Tecnologías encontradas

PHP y APIs de WordPress, HTML, CSS nativo con Grid/Flexbox y JavaScript sin framework para el sitio público. El administrador usa jQuery y `wp.media`. No hay Laravel ni SPA.

El catálogo utiliza el tipo de contenido propio `ens_product`, metadatos `_ens_*` y la taxonomía jerárquica `ens_product_cat`. Los accesos de portada usan `ens_feature`. No se encontraron llamadas a APIs WooCommerce ni un conector con Punto de Venta. El README menciona WooCommerce como posibilidad futura; eso no demuestra una integración existente.

## C. Componentes reutilizables

- `style.css`: variables azul `#161f55`, naranja `#f0783c`, amarillo `#f7c948`, tipografía de sistema, botones, tarjetas, formularios, estructura de catálogo y ficha. Conservar las clases `ens-*` permite migrar progresivamente sin rediseñar todo.
- Estructura HTML de cabecera, pie, breadcrumbs, hero, promociones, tarjetas, ficha, galería y estados vacíos: convertir en layouts/componentes Blade, reemplazando únicamente la obtención de datos y las URLs.
- Interacción de miniaturas de `cart.js`: extraer a un módulo pequeño de galería y mejorar etiquetas accesibles. Su funcionamiento no requiere WordPress.
- Presentación del carrito y formato argentino de importes: aprovechar como referencia. Los cálculos y la creación del pedido requieren implementación backend nueva.
- Configuración visual y accesos rápidos: reutilizar sus conceptos en el mini CMS de Fase 16. No migrar el personalizador WordPress.

No hay recursos fotográficos que copiar. Para reproducir el sitio efectivamente configurado se necesitarán sus medios y valores actuales del personalizador. Conservar atribución/licencia del CSS reutilizado: su cabecera declara GPL-2.0-or-later.

## D. Componentes a reemplazar

| Origen | Sustitución prevista, en su fase |
| --- | --- |
| Hooks, `WP_Query`, posts, metadatos y taxonomías de `functions.php` | Modelos Eloquent, relaciones, consultas paginadas, controladores y servicios específicos. |
| `get_theme_mod`, menús, generación automática de páginas | Configuración central, rutas Laravel y mini CMS. |
| Metaboxes, carga rápida y `wp.media` | Panel propio con Requests, Policies y almacenamiento validado. |
| `wp_localize_script` con todo el catálogo | Datos mínimos por página y operaciones de carrito validadas por backend. |
| Cálculo y WhatsApp de `cart.js` | Carrito validado, pedido persistido con snapshots y mensaje generado a partir del pedido. |
| IDs de adjuntos y galería separada por comas | Registros de imágenes con orden, principal, alt y referencia a archivo. |
| Stock textual, unidad libre y precio editable | Datos comerciales tipados, unidades ampliables, variantes reales y autoridad de Lista 1. |

No se borró ningún archivo: la copia del tema está aislada como referencia. Ningún PHP del tema se trasladará directamente al backend Laravel.

## E. Frontend y viabilidad

La conservación de la base visual es altamente viable por inspección del código: no depende de un framework de UI ni de un constructor de páginas. Se recomienda Blade, CSS y JavaScript nativo.

El CSS incluye puntos de quiebre a 1050 px (cabecera y dos columnas de productos) y 720 px (una columna, filtros horizontales, hero y ficha apilados). El hero admite una imagen móvil mediante `picture`.

Problemas a revisar al adaptar: `.ens-split` mantiene dos columnas en móvil; el carrito conserva columnas fijas que suman 265 px más espacios y la primera columna, por lo que puede desbordar en pantallas estrechas; la imagen de ficha conserva 490 px de altura. El buscador necesita nombre accesible y revisión de tamaño mínimo; miniaturas y botones de cantidad necesitan etiquetas descriptivas. Hay reglas duplicadas de `.ens-actions` y `.ens-product-visual` que conviene consolidar sin cambiar el aspecto.

No se ejecutó el tema ni se verificó su apariencia en navegador: falta una instalación WordPress con sus datos y medios, y PHP no está disponible en PATH. Las conclusiones responsive son estáticas, no una certificación visual. No se creó un mockup que pudiera confundirse con el sitio real.

## F. Riesgos y problemas concretos

### Compra y datos comerciales

1. **No hay pedidos persistidos.** `assets/js/cart.js` construye el mensaje y abre `wa.me` directamente. No hay número de pedido, snapshots ni historial.
2. **Un precio desconocido se convierte en cero.** `functions.php:20` convierte el metadato a float; un precio vacío puede verse como “Consultar” en la tarjeta (`ens_price`) y como $0 en el carrito. Debe modelarse explícitamente el importe pendiente de confirmar.
3. **No se valida disponibilidad ni precio en servidor al comprar.** Se puede agregar un producto agotado. El stock es texto y, si falta, la ficha muestra “En stock”. No existen variantes ni cantidades por medida; la unidad es solo descriptiva y los botones incrementan de a uno.
4. **Precios editables sin separación comercial/editorial.** `functions.php:57-70` permite editar precio y stock a quien pueda editar el post. La carga rápida (`:91`) publica con `edit_posts`, sin exigir una capacidad de publicación diferenciada. No satisface los roles Admin/Vendedor/Cliente del nuevo sistema.
5. **Entrega fuera de alcance.** `page-carrito.php` incluye envío a domicilio y consulta de entrega; el nuevo checkout debe ofrecer únicamente retiro en local.

### Seguridad y robustez

6. **Inserción de HTML sin escape en el carrito.** `cart.js` concatena nombres de productos e IDs de localStorage en `innerHTML`. Es un punto de inyección DOM si contienen marcado malicioso; usar creación de nodos/textContent y datos validados. No se ejecutó una explotación ni se afirma que cualquier visitante pueda modificar títulos en WordPress.
7. **localStorage sin validación de estructura.** El try/catch cubre JSON inválido, pero valores JSON válidos como `null` u objetos pueden romper `reduce`, `find` o `map`. No se validan cantidades finitas/positivas; productos inexistentes se omiten de filas pero siguen afectando el contador. Las escrituras tampoco manejan fallos de almacenamiento.
8. **Validación comercial débil.** Los guardados sanitizan texto, pero no validan precio decimal no negativo, unicidad de SKU ni conjunto permitido de disponibilidad en servidor. Sí existen nonces y controles de capacidad en los guardados: no corresponde describirlos como totalmente desprotegidos.

### Navegación y rendimiento

9. **Todo el catálogo en cada página.** `functions.php:17-28` consulta todos los productos publicados y los serializa en JavaScript. `archive-ens_product.php` vuelve a consultar todos sin paginación. La plantilla de categoría no muestra enlaces de paginación.
10. **Tarjetas sin enlace a la ficha.** `ens_product_card` solo ofrece Agregar; título e imagen no enlazan al producto, aunque existe plantilla de detalle.
11. **Buscador incompleto.** El selector de categoría de `ens_header` no tiene `name`; no filtra. Solo envía `s` a WordPress y no hay búsqueda propia por SKU/categoría ni plantilla de resultados dedicada.
12. **Enlace institucional inconsistente.** `ens_create_pages` genera el slug `la-ferreteria`, pero varias plantillas buscan `nosotros`; pueden terminar en `#`.
13. **HTML de portada inconsistente.** `front-page.php` cierra `picture` fuera del condicional que lo abre; cuando no hay hero hay una etiqueta de cierre sobrante.
14. **Inicialización frágil de portada.** `functions.php:160` solo configura la portada si `show_on_front` es falso; no cubre un valor existente como `posts`. Los accesos iniciales reciben todos el mismo `menu_order`.

### Contenido y mantenimiento

15. Hay promociones, textos institucionales, menú alternativo, opciones del buscador y mensajes comerciales fijos. WhatsApp tiene configuración central en el tema, pero el número de ejemplo `5491100000000` se repite como fallback en varias plantillas y en JS. Email y horarios son valores de ejemplo configurables, no datos confirmados del negocio.
16. El tema mezcla datos, administración y HTML en `functions.php`, y varios archivos están comprimidos en una sola línea. No hay pruebas. La versión de cabecera CSS (1.0.0) difiere de README/assets (1.1).
17. No se encontró implementación propia de autenticación de clientes, historial, notificaciones, auditoría, SEO específico, sincronización ni API externa. WordPress aporta funciones generales, pero no cumple por sí solo esos requisitos del nuevo sistema.

## G. Arquitectura propuesta y migración

Se mantiene la [propuesta inicial, sección G](fase-1-auditoria.md): monolito Laravel, Blade, MySQL/MariaDB, autorización backend y un límite explícito para integración externa. No se requiere SPA.

Mapa concreto para la futura adaptación:

```text
reference/wordpress/el-nuevo-sol/           # original intacto; no se publica
docs/                                     # auditoría y decisiones

# A crear únicamente al llegar a la fase correspondiente:
resources/css/storefront.css               # estilos ens-* adaptados
resources/js/gallery.js                    # interacción de imágenes
resources/js/cart.js                       # interfaz del carrito validado
resources/views/layouts/storefront.blade.php
resources/views/components/{header,footer,breadcrumbs,product-card}.blade.php
resources/views/storefront/{home,catalog,product,cart,contact,about}.blade.php
app/Http/Controllers/{Storefront,Account,Admin}/
app/Http/Requests/
app/Policies/
app/Models/
app/Services/{Cart,Orders,Integrations}/
app/Contracts/Integrations/ExternalCatalogProvider.php
database/migrations/
tests/{Feature,Unit}/
```

La futura migración de datos, si se solicita y se proporciona una exportación, podrá mapear `ens_product` a productos, `ens_product_cat` a categorías/relaciones, adjuntos a imágenes y `ens_feature`/personalizador a contenido web. Los precios `_ens_price` no deben asumirse como Lista 1 ni fuente autorizada: necesitan confirmación comercial. El ZIP no aporta datos para ejecutar esa migración.

La sincronización futura solo podrá escribir campos comerciales permitidos; nunca descripciones, imágenes, categorías web, SEO ni publicación. El contrato del proveedor se implementará cuando exista documentación real, sin deducir el protocolo a partir de este tema.

## H. Cambios de Fase 1 y cierre

Realizado:

- Extracción de los 14 archivos originales a `reference/wordpress/el-nuevo-sol/`, validando que las rutas permanezcan dentro del destino.
- Creación de este informe y actualización del README del proyecto.
- Marcado del informe inicial como anterior a la recepción del ZIP, conservando su propuesta de arquitectura.
- Revisión estática completa de código, estilos, dependencias, carrito, WhatsApp y contenido fijo.

No se modificó ni eliminó código del tema, ni se instalaron dependencias o implementaron funciones de fases posteriores.

Pendiente para validar la reproducción visual: medios originales, configuración actual y una referencia del sitio configurado. Esto no impide proponer la arquitectura ni identifica una necesidad de conservar WordPress en el nuevo sistema.

Decisiones que requieren información del responsable antes de las fases afectadas: origen comercial transitorio de Lista 1/stock, precisión y pasos de cantidades por unidad, redondeo/impuestos, tratamiento de disponibilidad y posible reserva al crear pedidos, y datos reales de contacto. La integración requiere documentación del mecanismo de Punto de Venta en Fase 18.

**Fase 1: auditoría estática y preparación completadas con los límites indicados. Esperar instrucciones explícitas antes de comenzar Fase 2.**
