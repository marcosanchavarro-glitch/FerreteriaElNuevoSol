# Demostración en Render

## Estado y arquitectura

Preparación del 15/10/2026. Repositorio: https://github.com/marcosanchavarro-glitch/FerreteriaElNuevoSol, rama `codex/render-demo`. La publicación del código en ese repositorio público fue autorizada expresamente. No se subieron `.env`, bases SQLite, archivos cargados, logs ni credenciales locales.

La aplicación sigue en `backend/`: Laravel 13.33.0, PHP 8.4, Blade y CSS/JS estáticos. No necesita compilar Vite para las pantallas actuales. Fases 1–17 implementadas; 18.A documentada; integración con Punto de Venta pendiente y sin cambios en esta fase.

| Componente | Preparación |
| --- | --- |
| Web | Render Web Service **Free**, Docker con PHP 8.4 y Apache; escucha `PORT` |
| Datos y sesiones | PostgreSQL externo, previsto **Neon**, exclusivo para datos ficticios |
| Imágenes cargadas | Cloudinary, assets `authenticated`; entrega a través de Laravel y sus permisos |
| Archivos de marca/CSS/JS | Dentro de la imagen Docker; no contienen información privada |
| Sesión | Base de datos, 10080 minutos de inactividad, cookie segura en HTTPS |
| Acceso | Clave de entrada a demo + autenticación normal de Admin/Seller/Customer |
| Despliegues | Blueprint versionado y despliegue manual; GitHub Actions verifica SQLite, PostgreSQL y Docker |

**No hay todavía una demo pública verificada.** Faltan la conexión secreta a una base Neon nueva y las credenciales de Cloudinary. Tampoco se probó aún la persistencia tras un reinicio real en Render. Un build Docker exitoso no equivale a un despliegue.

La base local existente y sus imágenes no se migran. La revisión de navegador usa `.tools/render-demo-review.sqlite` y `.tools/render-demo-review-media`, aisladas del desarrollo anterior.

## Completar la publicación

1. En Neon, crear una base o proyecto **vacío y exclusivo** para la demo, dentro de un plan gratuito que la cuenta permita. No utilizar una base de producción ni restaurar el SQLite local. Conservar el usuario y la conexión **directa** para las migraciones y sesiones. Elegir una región próxima a la del servicio Render.
2. En Cloudinary, crear o elegir un entorno exclusivo de demostración. Obtener Cloud name, API key y API secret. No configurar uploads sin firma, presets que publiquen imágenes ni webhooks a clientes. El prefijo `el-nuevo-sol-demo` debe permanecer estable entre despliegues.
3. Generar secretos una sola vez, desde `backend`, con `php deploy/create-demo-secrets.php`. En Windows se puede usar `../.tools/php/php.exe`. El comando escribe `backend/.env.demo-secrets`, no muestra los valores ni sobreescribe un archivo anterior. Abrirlo localmente y guardar sus valores en un gestor de contraseñas. No subirlo ni enviarlo por chat.
4. Abrir el [Blueprint de este repositorio](https://dashboard.render.com/blueprint/new?repo=https://github.com/marcosanchavarro-glitch/FerreteriaElNuevoSol). Seleccionar la rama `codex/render-demo` y `render.yaml`. Vincular GitHub si Render lo pide. Revisar que se cree **un único Web Service Free**, sin bases Render ni discos pagos.
5. Completar las variables secretas solicitadas con los datos de abajo. No pegar comillas externas del archivo generado en los campos de Render. Compartir `DEMO_ACCESS_PASSWORD` por un canal privado con los evaluadores; **no es una variable necesaria en Render**. Cada evaluador debe usar además su cuenta del rol correspondiente para administrar.
6. Aplicar el Blueprint. La URL real se verá en Render; el nombre sugerido no garantiza disponibilidad. `APP_URL` se obtiene de `RENDER_EXTERNAL_URL` al arrancar. Para un dominio posterior, configurar `APP_URL=https://dominio-confirmado`.
7. Revisar el deploy y realizar la lista de aceptación de este documento. Hasta completarla, el estado sigue siendo **pendiente de verificación remota**.

No se necesita habilitar SMTP, pagos ni Punto de Venta. No crear recursos pagos para resolver un fallo sin aprobación.

## Variables

| Variable | Valor / manejo |
| --- | --- |
| `APP_ENV`, `APP_DEBUG` | `production`, `false` |
| `APP_KEY` | Valor `base64:` de 32 bytes generado localmente; mantenerlo entre reinicios |
| `APP_URL` | Opcional inicialmente: arranque usa `RENDER_EXTERNAL_URL`; HTTPS obligatorio |
| `DEMO_MODE` | `true` |
| `DEMO_ACCESS_HASH` | Hash bcrypt generado; rotarlo revoca el permiso de entrada de sesiones anteriores |
| `DEMO_ADMIN_EMAIL`, `DEMO_SELLER_EMAIL`, `DEMO_CUSTOMER_EMAIL` | Tres emails distintos de prueba elegidos por el administrador |
| `DEMO_ADMIN_PASSWORD`, `DEMO_SELLER_PASSWORD`, `DEMO_CUSTOMER_PASSWORD` | Contraseñas aleatorias generadas; solo se usan en la inicialización |
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | URL secreta de Neon: `postgresql://USUARIO:CLAVE@HOST/BASE?sslmode=verify-full`; codificar caracteres especiales de usuario/clave como indique Neon |
| `DB_SSLMODE` | `verify-full`; si la URL trae `sslmode=require`, reemplazarlo por `verify-full` |
| `PGSSLROOTCERT` | `/etc/ssl/certs/ca-certificates.crt` dentro de Docker |
| `SESSION_DRIVER`, `SESSION_LIFETIME` | `database`, `10080` |
| `SESSION_SECURE_COOKIE`, `SESSION_EXPIRE_ON_CLOSE` | `true`, `false`; no fijar un dominio de cookie ajeno |
| `CACHE_STORE`, `QUEUE_CONNECTION` | `database`, `sync`; no hay workers externos comerciales |
| `MAIL_MAILER` | `array`; además la demo cancela eventos de envío |
| `CATALOG_STORAGE` | `cloudinary`; desarrollo local conserva `local` por defecto |
| `CLOUDINARY_CLOUD_NAME`, `CLOUDINARY_API_KEY`, `CLOUDINARY_API_SECRET` | Datos secretos/configuración del entorno Cloudinary de demo |
| `CLOUDINARY_PREFIX` | `el-nuevo-sol-demo`; no cambiarlo sin migrar referencias |
| `FILESYSTEM_DISK` | `local` para usos internos temporales; las imágenes usan `CATALOG_STORAGE` |
| `LOG_CHANNEL`, `LOG_LEVEL` | `stderr`, `warning`; Apache omite query strings de los accesos |

Laravel conserva soporte de `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` para otros entornos. Este arranque de demo exige **DB_URL** para evitar conexiones parciales accidentales. Nunca ejecutar `config:show` o imprimir las variables completas en logs públicos.

## Arranque y persistencia

`backend/deploy/start.sh` valida configuración antes de tocar la base, configura `robots.txt`, cachea configuración, ejecuta `migrate --force`, `RenderDemoSeeder`, cachea vistas y arranca Apache. Nunca ejecuta `migrate:fresh`, no regenera APP_KEY y no borra datos al reiniciar.

El seeder exige DEMO_MODE y una base inicialmente vacía. Crea tres cuentas, una categoría, cuatro productos ficticios, dos variantes y el CMS inicial. La marca `demo.initialized` impide repetir la carga o sobreescribir ediciones, contraseñas, pedidos e imágenes. Después de la primera carga se pueden quitar de Render las tres variables `DEMO_*_PASSWORD`; no se requieren en reinicios con una base ya inicializada. Cambiar esas variables después no modifica cuentas existentes. No quitar `APP_KEY` ni `DEMO_ACCESS_HASH`.

Los precios siguen siendo BIGINT en centavos y stock/cantidades BIGINT con escala 3. Se corrigieron la comparación booleana de variantes y el orden con precios nulos para PostgreSQL. No se introdujeron floats.

Las imágenes siguen validándose y normalizándose a WebP (5 MB y 3000 × 3000 máximos). La base guarda rutas relativas con UUID. Cloudinary recibe peticiones firmadas y autenticadas; sus URLs firmadas y credenciales no se entregan al navegador. Laravel conserva la visibilidad de productos/CMS y el acceso privado. Hay respaldo temporal en memoria y restauración compensatoria en los flujos existentes de reemplazo/eliminación. No hay una transacción distribuida entre PostgreSQL y Cloudinary: una caída simultánea puede requerir reconciliar un archivo huérfano o restaurar una imagen. Usar respaldos externos y revisar fallos; no borrar en bloque el prefijo para “arreglar” errores.

No hay copia local persistente de las imágenes en Render ni dependencia de `storage:link`. Los assets de marca están en Git/Docker; las subidas nuevas viven en Cloudinary. El almacenamiento local solo contiene cachés y archivos temporales reconstruibles.

## Seguridad y circuitos de prueba

La clave de entrada no da permisos administrativos. Las rutas web, login, registro y media dinámico pasan por la puerta de demo; después se aplican los roles/policies existentes. Una configuración ausente cierra el acceso. El formulario usa CSRF y un límite de cinco intentos por minuto por IP. El permiso se conserva en la sesión y se invalida si cambia el hash de entrada.

CSS, JS, logos de marca y `/up` permanecen accesibles para presentar el formulario y comprobar salud. No exponen catálogo ni registros privados. Apache limita su raíz a `public`, impide listados y archivos ocultos y agrega `X-Robots-Tag`. Robots/noindex son medidas de indexación, no autorización.

DEMO_MODE muestra una franja discreta, cambia la confirmación a pedido ficticio y devuelve `null` en el enlace WhatsApp incluso si el número comercial está guardado. Se bloquean correos y notificaciones externas del sistema Laravel, mientras siguen las notificaciones internas de pedidos. No se habilitan pagos ni sincronización POS. Al apagar DEMO_MODE en un futuro entorno apropiado, se conserva el circuito comercial original; el **arranque Docker actual está limitado expresamente a demo**, de modo que ese cambio requiere preparar el arranque de producción.

## Verificación y aceptación pendiente

Automatización: `.github/workflows/verify.yml` ejecuta la misma suite sobre SQLite y PostgreSQL 17; el servicio PostgreSQL de CI es temporal, con datos exclusivamente de pruebas. También construye la imagen de producción. Las credenciales visibles en ese workflow son solo las del servicio efímero de CI y nunca crean usuarios en la demo publicada.

Pruebas añadidas: entrada autorizada/denegada, rate limit, roles, rotación de entrada, conservación de sesión al navegar, checkout exacto, bloqueo de WhatsApp y correo, seed idempotente, firmas/cargas/lecturas/borrados Cloudinary simulados, errores sin filtración de respuestas del proveedor y rechazo del arranque con configuración insegura.

El primer intento de suite local fue bloqueado por Control de aplicaciones de Windows. Más tarde PHP volvió a ejecutarse; no se desactivó esa protección. La suite posterior pasó. Los resultados actuales están en [GitHub Actions](https://github.com/marcosanchavarro-glitch/FerreteriaElNuevoSol/actions).

Resultado confirmado del commit `730d43f`: **404 tests aprobados / 2249 aserciones en SQLite; 404 tests / 2248 aserciones en PostgreSQL 17; build Docker aprobado**. La diferencia de una aserción corresponde a la configuración de claves foráneas propia de SQLite. Los primeros fallos detectados se corrigieron: prioridad de la puerta de demo antes del login, aislamiento de la sesión al cambiar de usuario en pruebas, lectura multipart del simulador de Cloudinary y una aserción antigua que solo admitía SQLite. Las advertencias del CI por ausencia de `.env` se resolvieron copiando la plantilla sin secretos solo en el entorno de pruebas.

La revisión local de navegador comprobó entrada a demo, login Admin, navegación CMS, carrito decimal y pedido ficticio. Escritorio 1280 × 900 y móvil 390 × 844. No equivale a validar HTTPS, Neon o Cloudinary reales.

Antes de declarar publicada la demo:

- Confirmar deploy `live`, `/up` 200 y ausencia de errores. Probar acceso sin clave y con clave incorrecta.
- Abrir una sesión nueva, ingresar a demo y luego como Admin; probar Seller y Customer, registro, cambio de contraseña y logout.
- Crear un producto ficticio, subir una imagen y editar CMS. Comprobar visibilidad y acceso a imágenes sin sesión.
- Crear un pedido ficticio, cambiar su estado, comprobar auditoría y notificación interna. No debe aparecer un enlace comercial de WhatsApp.
- Anotar IDs de producto, imagen y pedido. Hacer un **redeploy manual** y verificar que siguen existiendo y que la imagen se descarga. Comprobar que la sesión sigue válida.
- Repetir en celular. Verificar cookies Secure/HttpOnly/SameSite y URLs HTTPS.
- Configurar y probar una exportación/recuperación de Neon y un respaldo de imágenes antes de conservar trabajo importante.

## Límites y costos

[Render Free](https://render.com/docs/free) suspende el web service tras 15 minutos sin tráfico; la primera visita puede tardar alrededor de un minuto. Su disco es efímero y no admite discos persistentes gratuitos. La cuenta tiene cupos de horas, build y ancho de banda; revisar límites y configuración de facturación. No se garantiza servicio continuo.

PostgreSQL Free de Render vence a los 30 días y luego tiene una ventana de recuperación antes de eliminarse. Por eso el Blueprint **no lo crea**. Neon y Cloudinary tienen cuotas y condiciones propias del plan/cuenta: confirmar las del usuario antes de conectarlos; no se asume retención ilimitada ni respaldos gratuitos. No se aprobó ningún recurso pago.

## Pasar a tienda permanente

1. Aprobar hosting pago y elegir base persistente con respaldos y recuperación comprobada. Planificar exportación/importación y una ventana de mantenimiento.
2. Crear un entorno de producción separado con APP_KEY, cuentas y almacenamiento propios. No usar la demo como catálogo definitivo sin depurar pedidos y datos ficticios.
3. Sustituir el arranque limitado a demo por uno de producción revisado: validación de secretos, migraciones no destructivas y sin RenderDemoSeeder. Revisar robots/noindex, puerta de entrada y permisos.
4. Configurar dominio, DNS, HTTPS y APP_URL. Revisar cookies y enlaces; no se requiere reescribir las vistas.
5. Configurar SMTP y probar entrega/recuperación en una cuenta controlada. Mantener sin correo real hasta autorizarlo.
6. Validar integración POS con documentación confirmada de Soluciones Es y un entorno de pruebas. La Fase 18.B sigue pendiente.
7. Cargar datos definitivos autorizados, comprobar precios/stock exactos, roles, imágenes, auditoría y recuperación de respaldos.
8. Solo tras la aceptación comercial, desactivar DEMO_MODE, habilitar WhatsApp correcto y comprobar el circuito completo antes de recibir pedidos reales. Los pagos requieren una decisión e integración propias.

Referencias: [Docker en Render](https://render.com/docs/docker), [Blueprint](https://render.com/docs/blueprint-spec), [control de acceso a imágenes Cloudinary](https://cloudinary.com/documentation/control_access_to_media), [firmas Cloudinary](https://cloudinary.com/documentation/authentication_signatures).
