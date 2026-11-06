# 2. Arquitectura de software

## 2.1 Monolito renderizado en servidor

La aplicación ejecutable está en `backend/`. Laravel organiza un monolito MVC: recibe HTTP, valida y autoriza solicitudes, coordina servicios, consulta Eloquent/Query Builder y renderiza HTML mediante Blade. No hay una SPA ni una API pública de sincronización. Los formularios y enlaces realizan solicitudes normales; cambiar de sección administrativa provoca recarga de documento.

La vista usa HTML, CSS y JavaScript estáticos. El navegador no se conecta directamente a PostgreSQL ni conoce las credenciales de Cloudinary. JavaScript mejora la galería, cantidades y selección de variantes; el servidor sigue siendo responsable de autorización, precio y stock admisible.

## 2.2 Responsabilidades

| Capa | Ubicación | Responsabilidad |
|---|---|---|
| Entrada | `public/index.php`, `bootstrap/app.php`, `routes/web.php` | Arranque, middleware, rutas y manejo de errores |
| Solicitudes | `app/Http/Requests` | Normalización, autorización de campos y validación |
| Controladores | `app/Http/Controllers` | Coordinar un caso de uso y producir vista o redirección |
| Servicios | `app/Services` | Carrito, checkout, catálogo, imágenes, estados, CMS, auditoría e importación |
| Dominio/persistencia | `app/Models`, `app/Enums`, `app/Casts` | Relaciones, estados tipados e invariantes de representación |
| Autorización | `app/Policies`, `AppServiceProvider` | Políticas por entidad y gates de acceso |
| Presentación | `resources/views`, `public/css`, `public/js` | HTML escapado, estilos y comportamiento progresivo |

No todas las escrituras pasan por un único servicio: `ProductController` contiene su transacción y el registro de auditoría. La separación existente debe documentarse como tal, sin atribuir repositorios, buses o microservicios que no existen.

## 2.3 Ciclo de una solicitud

Una petición llega a Apache en el contenedor o al servidor de desarrollo. `public/index.php` carga Composer y la aplicación configurada en `bootstrap/app.php`. El grupo web administra cookies, sesión y CSRF. `DemoAccess` agrega las reglas de demo; `EnsureActiveUser` controla cuentas desactivadas. Las rutas privadas agregan `auth`, `auth.session` y gates. El binding resuelve entidades; en relaciones anidadas se utiliza `scopeBindings()`.

El FormRequest valida antes del método del controlador. Una operación de escritura abre la transacción necesaria, aplica reglas y registra auditoría cuando corresponde. Blade escapa la salida y genera formularios con CSRF. Después de escribir, el controlador suele redirigir a una ruta nombrada: este patrón evita volver a presentar directamente el resultado de un POST. La validación HTML muestra errores; solicitudes que esperan JSON reciben respuestas del framework. No implica que exista una API REST dedicada.

## 2.4 Comunicación y decisiones técnicas

SQLite facilita ejecución local sin un servicio de base separado; PostgreSQL permite persistencia remota y bloqueos de filas verificados. Los casts enteros mantienen el mismo significado monetario entre ambos motores. Render ejecuta PHP/Apache; Neon aloja datos y sesiones; Cloudinary mantiene imágenes que no pueden depender del disco efímero. Estas son ventajas del diseño observado, no una reconstrucción de decisiones históricas no registradas.

Diagrama: [arquitectura general](diagramas/01-arquitectura.mmd).

Fuentes: `backend/bootstrap/app.php`, `backend/app/Providers/AppServiceProvider.php`, `backend/Dockerfile`, `backend/config/database.php` y los layouts Blade.

## 2.5 Decisión de evolución: responsabilidades comerciales previstas

Se conserva Laravel como plataforma principal. Para la integración futura, Punto de Venta será la fuente de precios y stock; los pedidos se gestionarán en la web y la facturación se realizará por separado en Punto de Venta. Esta distribución de responsabilidades es una decisión de arquitectura prevista, no una conexión implementada ni una automatización de facturación.

El mecanismo de conexión con Soluciones Es sigue pendiente de definición con el proveedor. No se decide aquí transporte, frecuencia, autenticación ni tratamiento de conflictos. WooCommerce no constituye una decisión definitiva ni un requisito de la arquitectura prevista. El alcance actual del importador manual permanece descrito en el capítulo 14. Las mejoras propuestas y sus dependencias se detallan en el apartado 20.5.
