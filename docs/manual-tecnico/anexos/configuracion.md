# Anexo C. Configuración sin secretos

Esta matriz describe nombres y responsabilidades observados en `.env.example`, `config/` y `render.yaml`. No reproduce valores de `.env`, credenciales del panel ni configuraciones obtenidas de consolas externas. Los valores del manifiesto representan la configuración declarada, no una certificación del estado remoto.

| Variables | Desarrollo / demo declarada | Responsabilidad |
|---|---|---|
| APP_NAME, APP_URL | Nombre y URL del entorno | Enlaces y presentación; usar la URL real del entorno autorizado |
| APP_ENV, APP_DEBUG | local/true; production/false | Entorno y exposición de errores |
| APP_KEY | Secreto único del entorno | Cifrado de Laravel; conservar al actualizar, no regenerar en cada despliegue |
| APP_LOCALE, APP_FALLBACK_LOCALE, APP_FAKER_LOCALE | es, en, es_AR en ejemplo | Idioma y generación de datos de desarrollo |
| DB_CONNECTION | sqlite local; pgsql demo | Controlador de persistencia |
| DB_DATABASE | Archivo SQLite o nombre PostgreSQL | La ausencia local usa database/database.sqlite |
| DB_URL | Secreto suministrado en Render | Conexión PostgreSQL; puede prevalecer sobre parámetros individuales |
| DB_SSLMODE, PGSSLROOTCERT | verify-full y CA del sistema en demo | Verificación TLS de PostgreSQL |
| DB_FOREIGN_KEYS | true en ejemplo | Integridad referencial SQLite |
| SESSION_DRIVER | database | Persistencia compartida entre solicitudes |
| SESSION_LIFETIME | 10080 minutos | Ventana configurada de sesión; no garantiza acceso si se revoca la cuenta o cambia la clave |
| SESSION_EXPIRE_ON_CLOSE | false | Cerrar el navegador no fuerza expiración |
| SESSION_SECURE_COOKIE | true en Render | Cookie solo por HTTPS; no copiar este valor a HTTP local sin considerar el efecto |
| SESSION_DOMAIN, SESSION_PATH | null, / en ejemplo | Alcance de cookie; no mezclar localhost y 127.0.0.1 en una misma sesión |
| SESSION_ENCRYPT | false en ejemplo | Cifrado del contenido persistido; distinto de protección de cookies |
| CACHE_STORE | database | Caché y bloqueos; requiere tablas técnicas |
| QUEUE_CONNECTION | database local; sync en demo | Ejecución diferida frente a inmediata; no hay worker Render declarado |
| MAIL_MAILER | array | No entrega correo real; no escribir tokens de recuperación en logs |
| MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD | Parámetros de transporte sin activar | No configuran por sí solos un proveedor operativo mientras el mailer sea array |
| MAIL_FROM_ADDRESS, MAIL_FROM_NAME | Remitente de configuración | Su presencia no demuestra envío real |
| LOG_CHANNEL, LOG_LEVEL | stack/debug local; stderr/warning demo | Destino y nivel; no registrar secretos ni payloads comerciales completos |
| DEMO_MODE | false local; true demo | Activa protecciones demostrativas, incluida supresión de WhatsApp y correo |
| DEMO_ACCESS_REQUIRED | true ejemplo; false manifiesto | Control de acceso previo de demo; distinto del login administrativo |
| DEMO_ACCESS_HASH | Secreto bcrypt | El validador de arranque lo exige incluso con acceso previo deshabilitado |
| DEMO_ADMIN_EMAIL, DEMO_ADMIN_PASSWORD | Secretos de inicialización | Cuenta de ejemplo del seeder, no mecanismo de rotación de usuarios existentes |
| DEMO_SELLER_EMAIL, DEMO_SELLER_PASSWORD | Secretos de inicialización | Cuenta de vendedor del seeder |
| DEMO_CUSTOMER_EMAIL, DEMO_CUSTOMER_PASSWORD | Secretos de inicialización | Cuenta de cliente del seeder |
| CATALOG_STORAGE | local o cloudinary | Adaptador específico del catálogo |
| FILESYSTEM_DISK | local | Disco general de Laravel; no sustituye CATALOG_STORAGE |
| CLOUDINARY_CLOUD_NAME, CLOUDINARY_API_KEY, CLOUDINARY_API_SECRET | Identificación y secretos del adaptador | Firmas de carga/lectura; no exponer al navegador |
| CLOUDINARY_PREFIX | el-nuevo-sol-demo declarado | Espacio de identificadores; cambiarlo puede afectar recuperación de imágenes |
| SHOP_WHATSAPP_NUMBER | Valor inicial configurable | Número internacional; settings puede prevalecer |
| SHOP_PICKUP_ADDRESS, SHOP_PICKUP_HOURS | Valores iniciales configurables | Información de retiro, con sobreescritura administrativa en settings |

`config/shop.php` fija la zona comercial `America/Argentina/Buenos_Aires`. No existe una variable de conexión operativa con Soluciones Es en el contrato implementado. No inventar un token o interruptor para una integración que todavía requiere documentación del proveedor.

El ejemplo contiene variables estándar para Redis, Memcached, AWS y Vite. Esto no acredita servicios habilitados ni almacenamiento de catálogo en S3. El inventario de dependencias tampoco acredita una integración comercial.

Tras cambiar configuración en un entorno autorizado, Laravel requiere reconstruir su caché de configuración según el procedimiento de despliegue. No editar secretos dentro de archivos versionados ni copiar una configuración de producción al entorno de pruebas. Las pruebas PostgreSQL aprobadas utilizan una base efímera y credenciales ficticias de CI.

La marca `demo.initialized` pertenece a la tabla settings y es distinta de las variables de entorno: el arranque debe reconocerla para no intentar inicializar una base existente. Su comprobación previa es parte del procedimiento de publicación, no una acción realizada al redactar este manual.
