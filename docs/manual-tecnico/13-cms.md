# 13. CMS y personalización

## 13.1 Secciones y banners

`HomepageSection` almacena tipo, título, subtítulo, contenido, imagen, botón, settings, orden y actividad. Los tipos admitidos son hero, featured_categories, featured_products, banner, promotion, text_image, text e image. `Banner` guarda título/subtítulo, imagen obligatoria, botón, orden y actividad.

`ContentController` comparte implementación para `/admin/content/sections` y `/admin/content/banners` mediante el parámetro de ruta `kind`. Las vistas `admin/content/index.blade.php` y `edit.blade.php` presentan listado, alta y edición. `ContentManager` guarda transaccionalmente contenido y auditoría y compensa operaciones de archivos cuando hay errores.

Admin y Seller acceden mediante ContentPolicy. Activar/desactivar y mover son acciones específicas. La desactivación de contenido activo requiere confirmación aceptada. Las rutas no ofrecen un borrado genérico de secciones/banners; las secciones sí permiten retirar su imagen. No describir un CMS de páginas arbitrarias, editor visual o constructor de plantillas: la presentación usa bloques Blade definidos.

## 13.2 Validaciones y contenido público

ContentRequest limita títulos a 160 caracteres, subtítulos a 255 y texto a 5000. Texto y URL del botón se requieren mutuamente. `InternalLink` restringe a rutas internas. `max_items` admite 1–24 y se guarda dentro de settings; el request prohíbe enviar settings o image_path arbitrarios. La imagen usa la misma política de normalización que el catálogo.

Homepage consulta secciones activas ordenadas. Si no hay ningún registro de secciones utiliza defaults en memoria; si existen pero todas están desactivadas, no las repone automáticamente. Featured products usa el máximo solicitado por bloques, acotado a 24, y categorías destacadas provienen del árbol visible.

Las columnas `starts_at` y `ends_at` existen en banners y el render público respeta su vigencia. Sin embargo ContentRequest prohíbe modificarlas: **la programación temporal desde el panel no está implementada**. Esta distinción evita presentar una capacidad del esquema como una función disponible al operador.

## 13.3 Configuración y marca

`SiteSettings` permite solo `business_name`, `phone`, `whatsapp_number`, `pickup_address` y `pickup_hours`. `SettingsController` exige `manage-settings`. Los valores persistidos prevalecen sobre defaults; WhatsApp, dirección y horario pueden tomar sus defaults de `config/shop.php`. No se editan claves de conexión ni secretos desde esta pantalla.

La marca reside en archivos estáticos versionados y en `branding.css`. El panel permite contenido y datos del negocio, pero no una carga libre de logos que reconfigure todo el tema. Fuentes: ContentRequest, ContentPolicy, ContentManager, Homepage, SiteSettings, SiteSettingsRequest y vistas `storefront/content-block.blade.php` y `content-button.blade.php`.
