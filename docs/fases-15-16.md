# Fases 15 y 16 — Panel administrativo y CMS

Completadas y verificadas el 1 de octubre de 2026. Laravel 13, PHP 8.4 y SQLite. Fase 17 no iniciada.

## Panel administrativo

`/admin` integra productos, categorías, pedidos, página de inicio y banners sin duplicar sus módulos. La navegación reutilizable marca la sección activa y se pliega en móvil. Conserva los colores y el logotipo de El Nuevo Sol, con accesos rápidos y enlace «Ver sitio».

El dashboard consulta datos reales: productos publicados, borradores, publicados sin imagen y sin stock; pedidos recibidos, en preparación y listos para retirar. Presenta como máximo ocho pedidos recientes, con cliente, fecha, estado, total confirmado y acceso al detalle. La disponibilidad desconocida no se contabiliza como agotada; en productos variables se consideran las variantes activas.

Admin y Seller acceden al panel y al contenido comercial. Se mantienen las Policies de los módulos existentes. Solo Admin accede a Configuración, tanto desde la navegación como mediante autorización de backend. Customer recibe 403; Guest es redirigido al login.

## Mini-CMS

Se reutilizan HomepageSection y Banner. Tipos controlados: hero, banner, featured_products, featured_categories, text_image y promotion; se conservan text e image por compatibilidad con el enum anterior.

Las secciones permiten título, subtítulo, texto, imagen, botón con enlace interno, visibilidad y orden. Los controles ↑/↓ persisten el orden; los empates se resuelven por ID. Las desactivaciones y la eliminación de imágenes requieren confirmación. Se oculta contenido sin borrarlo definitivamente.

Los banners permiten creación, edición, imagen, título, subtítulo, botón, orden y activación. Una sección de tipo banner muestra el conjunto de banners activos, ordenados. Su imagen es obligatoria al crearlos; después puede reemplazarse. Las fechas existentes de vigencia se respetan, pero no se añadió un editor de programación de fechas.

Los destacados reutilizan Product.featured y Category.featured. El CMS configura título, visibilidad, orden y cantidad máxima (1–24); no crea listas paralelas. Se aplican publicación y visibilidad jerárquica de categorías. Los productos usan las tarjetas y consultas del catálogo existente, con relaciones precargadas.

La home renderiza componentes controlados según las secciones activas y su orden. Mantiene la identidad visual; no admite HTML/CSS arbitrario. HomepageSeeder crea seis secciones representativas solo cuando aún no existen secciones. No sobrescribe ediciones. Sin configuración se utiliza contenido inicial; si se ocultaron todas las secciones, se presenta un estado vacío con acceso al catálogo.

## Configuración y seguridad

La lista de campos editables es cerrada: nombre comercial, teléfono, WhatsApp, dirección y horarios. Se rechazan claves adicionales y estructuras arbitrarias. No se muestran ni editan secretos, APP_KEY, credenciales ni configuración de base de datos.

SiteSettings centraliza los datos del footer, retiro y WhatsApp del pedido. Las variables SHOP_* existentes son valores iniciales si no hay configuración guardada. Un WhatsApp guardado vacío deshabilita el enlace. No se envían mensajes automáticamente. No se añadió caché.

Los Form Requests validan campos permitidos, tipos, límites y confirmaciones. Los textos se escapan al mostrarse. Los botones aceptan rutas internas seguras y rechazan protocolos peligrosos, URLs externas, enlaces relativos a protocolo y codificaciones evasivas; también se verifica la seguridad al renderizar valores antiguos.

Se reutiliza ImageStorage: JPEG, PNG y WebP de hasta 5 MB y 3000 × 3000 píxeles, validación de MIME y extensión, decodificación y conversión a WebP, nombres seguros y almacenamiento privado. No hay blobs en SQLite ni symlink público. Las rutas públicas verifican visibilidad. El reemplazo/eliminación coordina transacciones y restauración de archivos ante fallos.

## Pruebas y verificación

- Antes de modificar código: suite completa aprobada, **332 pruebas / 1732 aserciones**.
- Resultado final de regresión: **368 pruebas / 2021 aserciones, todas aprobadas**.
- Nuevas coberturas: roles, estadísticas, límite de pedidos, navegación, tipos CMS, creación/edición/visibilidad/orden, destacados, imágenes válidas e inválidas, rollback, enlaces inseguros, XSS, configuración permitida y rechazo de claves arbitrarias.
- Laravel Pint ejecutado sobre los cambios PHP.
- `migrate:fresh --seed --force --no-interaction` aprobado en una base nueva aislada: `.tools/cms-validation-20261001.sqlite`. No se reconstruyeron bases con datos del usuario.

Prueba manual en `http://127.0.0.1:8001` con datos de demostración:

- Admin: dashboard, productos, pedidos, categorías y CMS; edición de hero y carga de imagen, guardado y comprobación pública.
- Banner: creación con imagen y botón, cambio de orden, desactivación confirmada y desaparición del inicio.
- Destacados: cambio de límites de productos y categorías, comprobación pública y restauración de límites.
- Sección texto + imagen: carga y guardado de imagen.
- Configuración: guardado de WhatsApp/dirección/horarios ficticios y comprobación del footer; luego se vaciaron los datos de contacto de prueba.
- Responsive: dashboard, menú plegable, formulario CMS e inicio a 390 × 844.
- Seller: edición comercial permitida, configuración ausente del menú y acceso directo rechazado con 403.
- Customer: acceso directo al CMS rechazado con 403. Guest: `/admin` redirige al login.
- Se dejó abierta una sesión de Admin en el dashboard.

La demostración conserva el hero «Todo para tu obra», imágenes de marca y un banner «Novedades · DEMO CMS» inactivo. Estos cambios manuales se hicieron en la base de preview; la base principal recibió únicamente el contenido inicial del seeder.

## Base de datos y archivos principales

No se agregaron migraciones: las tablas existentes resultaron suficientes. DatabaseSeeder incorpora HomepageSeeder. No se añadieron dependencias.

Principales archivos bajo `backend/`:

- `app/Http/Controllers/Admin/{DashboardController,ContentController,SettingsController}.php`.
- `app/Http/Requests/{ContentRequest,SiteSettingsRequest}.php`.
- `app/Services/Content/{Homepage,ContentManager,SiteSettings}.php`.
- `app/Policies/ContentPolicy.php`, `app/Rules/InternalLink.php` y `app/Enums/HomepageSectionType.php`.
- `app/Services/Catalog/ImageStorage.php`, `app/Services/Storefront/WhatsAppOrder.php`, `app/Http/Controllers/Storefront/CatalogController.php` y `app/Providers/AppServiceProvider.php`.
- `routes/web.php`, `database/seeders/{HomepageSeeder,DatabaseSeeder}.php`.
- Vistas `admin/home`, `admin/navigation`, `admin/content/*`, `admin/settings`, layouts y componentes del inicio público.
- `public/css/{admin,content}.css` y `public/js/admin.js`.
- Pruebas `AdminDashboardTest`, `HomepageCmsTest` y `SiteSettingsTest`.

## Decisiones y pendientes

Para fases posteriores, los permisos comerciales del Seller quedan separados de los ajustes globales del Admin. Las promociones son contenido visual, sin alterar precios. La configuración central de WhatsApp es reutilizable, pero no define ninguna integración externa. El CMS utiliza componentes fijos y enlaces internos; no es un editor visual general.

Falta que el negocio defina contenidos comerciales definitivos, número real de WhatsApp, teléfono, dirección y horarios. El correo real continúa deshabilitado según lo solicitado. No se implementaron Punto de Venta, WooCommerce, sincronización, pagos, delivery, cupones, auditoría completa ni analytics complejos. Fases 17 y 18 quedan pendientes de instrucciones.
