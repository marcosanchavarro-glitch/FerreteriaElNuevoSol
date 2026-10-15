# Fases 3 y 4 — Autenticación y gestión interna de productos

Estado: implementadas y verificadas. No se inició Fase 5.

## Fase 3

Autenticación web mediante el guard de sesión, hashing, password broker y CSRF estándar de Laravel 13. No se instalaron paquetes adicionales, OAuth ni autenticación por tokens.

### Rutas

| Método | Ruta | Uso |
| --- | --- | --- |
| GET | `/` | Acceso inicial, sin catálogo público. |
| GET / POST | `/registro` | Registro público. |
| GET / POST | `/login` | Inicio de sesión y recordar sesión opcional. |
| POST | `/logout` | Cierre e invalidación de sesión. |
| GET / POST | `/recuperar-contrasena` | Solicitud de recuperación. |
| GET | `/restablecer-contrasena/{token}` | Formulario de nueva contraseña. |
| POST | `/restablecer-contrasena` | Validación de token y restablecimiento. |
| GET / PATCH | `/mi-cuenta` | Ver y modificar el perfil propio. |
| PUT | `/mi-cuenta/contrasena` | Cambio autenticado de contraseña. |
| GET | `/admin` | Inicio administrativo básico. |

Después de ingresar, customer va a `/mi-cuenta`; admin y seller van a `/admin`. Se utiliza el rol actual en servidor, sin aceptar un destino privilegiado enviado por el navegador.

### Roles, permisos y cuentas inactivas

- El registro impone `UserRole::Customer` y cuenta activa. Ignora cualquier rol, ID, active o verificación manipulados. No hay selector público de rol.
- Gate `access-admin`: únicamente admin/seller activos. Gate `manage-roles`: únicamente admin activo; queda definido como límite para el futuro, **sin ruta ni interfaz de cambio de roles**.
- Middleware `EnsureActiveUser` revisa el usuario en cada petición web: una cuenta desactivada pierde la sesión. El login y el password broker también filtran por active.
- Rutas privadas protegidas por `auth` y `auth.session`. Los cambios/restablecimientos de contraseña rotan remember_token; otras sesiones deben reautenticarse al volver a una ruta privada.
- Las sesiones se regeneran al registrarse/ingresar/cambiar contraseña y se invalidan al salir. No se deshabilitó CSRF.

### Perfil y contraseñas

El perfil utiliza exclusivamente al usuario autenticado; no acepta IDs de usuarios ni parámetros de roles. Se puede editar nombre, apellido, teléfono y email. Cambiar email exige contraseña actual, comprueba unicidad, normaliza a minúsculas, borra el token de recuperación anterior y deja email_verified_at en null. No se implementó todavía un flujo de verificación de email.

Cambiar contraseña exige la actual y confirmación de la nueva. Mínimo 12 caracteres con letras y números. Se limita también el tamaño en bytes a 72 y se rechazan caracteres nulos para evitar truncamiento o errores del algoritmo bcrypt. Las entradas públicas siempre pasan por Hash::make, incluso cuando parecen un hash existente.

Hay límites por IP en registro, login, recuperación y cambios sensibles. Además, el login se bloquea durante 60 segundos tras cinco intentos fallidos por combinación email/IP. Contraseñas y tokens no se conservan como old input tras fallos de validación.

### Recuperación de contraseña

Usa el password broker y la notificación de Laravel: token almacenado mediante el mecanismo estándar, expiración de 60 minutos, invalidación después de utilizarlo, rechazo de tokens inválidos/vencidos y de usuarios inactivos. La respuesta de solicitud es genérica para no confirmar si existe una cuenta.

Se añadió únicamente `RoutesNotifications` a User, conservando la relación propia `notifications()` de Fase 2. No se sustituyó por la tabla de notificaciones estándar ni se desarrollaron notificaciones comerciales.

Los enlaces se construyen con el `APP_URL` configurado, no con un Host arbitrario de la petición.

**Por indicación expresa del responsable, el envío real de correos queda deshabilitado.** `.env.example` y el entorno local usan `MAIL_MAILER=array`: no envía, no persiste el correo ni vuelca el enlace a logs. La pantalla local informa esta limitación. Las pruebas verifican la notificación, el token y el cambio de contraseña; una prueba adicional compone el mensaje con el transporte array.

Para habilitar entrega más adelante: configurar `MAIL_MAILER=smtp`, host, puerto, esquema y remitente; credenciales exclusivamente en `.env`; ajustar `APP_URL` al dominio real. Luego ejecutar `php artisan config:clear` y probar la entrega con una cuenta autorizada. No se configuró SMTP ficticio ni se enviaron correos a terceros.

## Fase 4

### Pantallas y operaciones

- `GET /admin/products`: listado de 15 productos por página, búsqueda por nombre/SKU, nombre, código, precio, stock, unidad, estado, destacado y última sincronización.
- `GET /admin/products/create` y `POST /admin/products`: creación local.
- `GET /admin/products/{product}`: ficha interna, categorías existentes y cantidad de variantes.
- `GET /admin/products/{product}/edit` y `PUT/PATCH /admin/products/{product}`: edición autorizada.
- No existe DELETE: para ocultar se utiliza `private`. No se borran variantes, imágenes, categorías ni historial desde estas pantallas.

La interfaz Blade y CSS es sencilla y responsive, sin dependencias nuevas ni build de JavaScript. El listado admite desplazamiento horizontal en pantallas pequeñas. Los textos de productos se escapan y se presentan como texto simple; no se renderiza HTML arbitrario.

### Matriz de permisos

| Operación | Admin | Seller | Customer |
| --- | --- | --- | --- |
| Entrar al admin, listar/ver productos | Sí | Sí | No |
| Crear producto local | Sí | Sí | No |
| Slug, descripciones, modalidad, estado, destacado | Sí | Sí | No |
| Nombre, tipo y unidad de producto local | Sí | Sí | No |
| SKU, precio y stock de producto local | Sí | No | No |
| Datos comerciales de producto externo | No | No | No |
| external_id y external_synced_at por formularios | No | No | No |
| Borrar producto | Sin ruta | Sin ruta | Sin ruta |
| Cambiar roles | Sin ruta | Sin ruta | Sin ruta |

La matriz se aplica en `ProductPolicy`, Gates, middleware y `ProductRequest`. Enviar un campo protegido devuelve 403, incluso si su valor es null. Ocultar controles no es la protección principal. Los controladores persisten únicamente los datos validados; nunca el request completo.

### Locales y externos

Un producto se considera externo si tiene external_id **o** external_synced_at; esta segunda condición evita desbloquearlo accidentalmente por un ID faltante. Ningún formulario puede asignar o borrar estos indicadores.

En productos externos se protegen SKU, precio, stock, nombre, tipo y unidad, para ambos roles. Se mantienen editables slug, descripciones, modalidad de compra, publicación y destacado. Esta elección conservadora evita cambiar significado comercial antes de conocer el origen real. Si luego se necesita un nombre web independiente del comercial, se decidirá explícitamente en esa fase.

Los vendedores crean productos locales en borrador o para consulta, sin cargar importes. Un producto simple calculable no se puede publicar con precio desconocido; puede permanecer en borrador o usar inquiry. El administrador puede cargar precio y stock locales, identificados visualmente como datos manuales, no como Lista 1 sincronizada.

La modalidad de compra se considera una decisión web: no cambia el precio/stock externo. El flujo de compra deberá respetar inquiry sin interpretar un precio desconocido como cero.

### Dinero, stock y variantes

Se mantienen los casts y BIGINT de Fase 2. Los formularios reciben pesos como texto decimal, con punto o coma y sin miles; convierten a centavos mediante enteros. Ejemplo: `2500,51` → `250051`. Stock admite hasta tres decimales, convertido a cadena normalizada para el cast existente. Las entradas administrativas de stock no aceptan negativos; el modelo conserva capacidad de representar saldos externos negativos sin autorizar su venta.

Productos variables mantienen precio y stock del padre en null. No pueden convertirse a simples si tienen variantes. Se muestran tipo/cantidad de variantes, pero no existe administración de variantes. No se duplicó ninguna tabla ni modelo.

## Verificación

- Antes de modificar: **49 pruebas / 155 aserciones aprobadas**.
- Resultado final: **124 pruebas / 489 aserciones aprobadas**. Incluye las 49 previas y 75 casos nuevos.
- Nuevos tests: `AuthenticationTest`, `ProfileSecurityTest`, `ProductManagementTest`.
- Casos: registro con roles manipulados, contraseñas y hashes, login/logout, recordar sesión, rate limiting, cuenta inactiva, tokens inválidos/vencidos/de un solo uso, notificación de recuperación, revocación de sesión, CSRF real, perfil ajeno, protección de roles y campos comerciales, estados, destacados, búsqueda, paginación, variantes, escape de HTML y snapshots históricos.
- Las seis migraciones se aplicaron desde cero con `migrate --seed` sobre un archivo SQLite independiente de verificación en `.tools/`. El seeder se repitió sin duplicados. La base local existente no fue reconstruida.
- Las pruebas conservan también el ciclo migrate → rollback → migrate de Fase 2.
- `migrate:status`: todas aplicadas en la base de desarrollo. `composer validate --strict --no-check-publish`: correcto. Pint aplicado.
- Navegador: login de administrador, listado, formulario de creación, edición/guardado de un producto demo con sesión y CSRF reales, Mi cuenta a 390 px y logout. En la edición se guardaron los valores existentes del cable demo, sin alterar sus importes.
- El frontend original de referencia se conserva; no se migró al nuevo núcleo.

Problemas encontrados: algunas aserciones iniciales comparaban atributos de una factory aún no recargada con una lectura de DB; se corrigieron para comparar snapshots persistidos antes/después. Se agregó validación de longitud en bytes al detectar la diferencia entre longitud de caracteres y límite bcrypt. No quedan fallos en la suite ejecutada.

## Archivos principales

- `backend/routes/web.php`: rutas públicas de acceso, cuenta y administración.
- `backend/bootstrap/app.php`: middleware, redirecciones y exclusión de secretos de old input.
- `backend/app/Providers/AppServiceProvider.php`: Gates, límites y URL confiable de recuperación.
- `backend/app/Http/Controllers/Auth/`: registro, sesión, recuperación/restablecimiento.
- `backend/app/Http/Controllers/ProfileController.php` y `Admin/ProductController.php`.
- `backend/app/Http/Requests/`: reglas y autorización de campos.
- `backend/app/Http/Middleware/EnsureActiveUser.php`, `app/Policies/ProductPolicy.php`.
- `backend/app/Rules/PasswordInput.php`, `app/Support/{AccountSession,ProductDisplay}.php`.
- `backend/app/Models/User.php` y `Product.php`: routing de correo y detección de origen externo.
- `backend/resources/views/{auth,account,admin,layouts}/`, `home.blade.php`, `public/css/internal.css`, `lang/es/`.
- `backend/.env.example`: transporte de correo sin envíos reales.
- `backend/tests/Feature/{AuthenticationTest,ProfileSecurityTest,ProductManagementTest}.php`.

No hubo nuevas migraciones ni cambios de esquema o dependencias.

## Uso local

Desde `backend/`:

```powershell
& ../.tools/php/php.exe artisan test
& ../.tools/php/php.exe artisan serve --host=127.0.0.1 --port=8000 --no-reload
```

Abrir `/login`. Siguen disponibles las cuentas **SOLO DESARROLLO** de Fase 2: `admin@example.test`, `vendedor@example.test`, `cliente1@example.test`, `cliente2@example.test`; contraseña `SoloDesarrollo!2026`. No utilizarlas en producción.

## Pendientes por fase

- **Fase 5:** administración completa de variantes, selección y reglas por variante/unidad.
- **Fase 6:** CRUD de categorías y edición de asignaciones.
- **Fase 7:** carga, validación, orden y selección de imágenes.
- **Fases posteriores:** catálogo público, carrito, checkout, pedidos operativos, historial del cliente, notificaciones comerciales, CMS, banners, auditoría automática e integración documentada con Punto de Venta.
- **Configuración futura:** SMTP real autorizado. La seguridad de producción (HTTPS, cookies seguras, APP_DEBUG=false, credenciales y despliegue) corresponde al entorno de publicación.

No se implementaron pagos, delivery, WooCommerce ni conectores externos. Esperar instrucciones antes de Fase 5.

Referencia técnica: [autenticación estándar de Laravel 13](https://laravel.com/framework/docs/13.x/authentication), contrastada con el código de Laravel instalado.
