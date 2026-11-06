# 12. Panel administrativo

## 12.1 Acceso, navegación y módulos

El prefijo `/admin` se protege mediante `auth`, `auth.session` y `can:access-admin`. Los controladores aplican autorizaciones adicionales. El layout `layouts/app.blade.php` incluye navegación compartida, mensajes de estado y errores. Los formularios usan métodos HTTP convencionales con CSRF; no existe router cliente.

El menú incluye Dashboard, productos, categorías, pedidos, página de inicio, banners, configuración, historial de actividad y sincronización comercial. Los tres últimos accesos restringidos a Admin se apoyan en gates específicos; no todos los usuarios que ven el panel tienen las mismas facultades. Perfil staff está separado del perfil Customer.

`admin.js` abre el elemento details del menú desde 901 px y lo adapta al cambio de ancho. Las tablas usan contenedores desplazables. En la revisión histórica a 390 px, las columnas finales de Productos requieren desplazamiento horizontal interno; está registrado como mejora menor, no como un rediseño realizado.

## 12.2 Dashboard y estadísticas

`DashboardController::__invoke()` devuelve productos publicados, borradores, publicados sin imagen y sin stock. Un variable se considera agotado cuando tiene variantes activas y ninguna de ellas tiene stock desconocido o positivo. Ausencia de variantes activas y stock desconocido no equivalen al mismo caso de agotamiento. Se agrupan pedidos por estado y se muestran ocho recientes.

La candidata unifica cuatro conteos de productos en una consulta con subconsultas. El controlador pasó de seis a tres consultas: estadísticas de productos, estadísticas de pedidos y recientes. Se reducen viajes al servidor de datos; no desaparece el trabajo de contar cuatro conjuntos.

```php
// backend/app/Http/Controllers/Admin/DashboardController.php
->selectSub(Product::where('status', 'published')->selectRaw('COUNT(*)'), 'published')
```

Este fragmento añade un conteo como columna de una consulta compuesta. Los demás conteos aplican sus propios predicados; no se reemplazan por aproximaciones ni caché de datos comerciales.

## 12.3 Pedidos y paginación

El listado sin filtros ya obtiene todos los conteos por estado; suma esos valores y los entrega como total a `paginate(20, total: ...)`, evitando un COUNT adicional. Pasa de tres a dos consultas de controlador. Con filtros conserva el conteo específico y tres consultas. Los conteos se recalculan en cada solicitud y pueden variar entre sentencias bajo concurrencia; no se declara una instantánea global del panel.

| Módulo | Límite de listado |
|---|---:|
| Productos internos | 15 por página |
| Pedidos internos | 20 por página |
| CMS | 20 por página |
| Pedidos Customer | 10 por página |
| Catálogo público | 12 por página |
| Dashboard recientes | 8 |
| Ejecuciones de importación | 10 últimas |

Categorías utiliza el árbol completo, no un listado paginado. Las afirmaciones de presupuesto SQL se refieren a los controladores medidos, sin incluir sesión, autenticación o bootstrap.

## 12.4 Mediciones y límites

La medición local histórica en SQLite redujo Dashboard de 6 a 3 consultas y Pedidos de 3 a 2. Medianas del controlador incluyendo SQL: 3,36 → 2,91 ms y 1,44 → 1,28 ms respectivamente. No se deben comparar estos tiempos internos con HTTP remoto.

La línea base HTTP caliente de la demo fue Dashboard 2,413 s, Productos 1,678 s, Categorías 1,455 s, Pedidos 1,959 s y CMS 1,719 s; `/up` 0,264 s. No existe medición posterior de la candidata desplegada. La distancia Render Oregon/Neon Ohio hace plausible un costo por viaje, pero no demuestra causalidad completa; tampoco hubo evidencia suficiente de saturación sostenida de Render Free.

Fuentes: controladores Dashboard/Order, layouts y `docs/pendientes-correcciones.md`. No extrapolar estos resultados a catálogos grandes ni a otras pantallas.

## 12.4 Ronda administrativa local

El encabezado administrativo se compactó, conserva logotipo proporcional e identifica al usuario. La banda de demo sigue protegida. La navegación móvil utiliza drawer con cierre, Escape, foco contenido y contenido de fondo inert; escritorio conserva sidebar. Se añadieron acceso al contenido por teclado y reglas de movimiento reducido. El listado de productos y el editor usan las mejoras del apartado 8.5. No se añadió SPA ni dependencia frontend.

Las capturas y mediciones locales figuran en docs/correcciones-administracion.md. No sustituyen los tiempos HTTP históricos de Render ni demuestran un límite de infraestructura. No se utilizó caché comercial.
