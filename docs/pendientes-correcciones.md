# Fase 19 — optimización focalizada del panel

Fecha: 2026-10-15. Alcance: Dashboard y listado de Pedidos. No constituye una auditoría integral de seguridad ni el cierre de las demás tareas del documento amplio de fase 19.

## Evidencia y correcciones locales

- Dashboard: cuatro viajes SQL independientes para estadísticas de productos. Ahora se ejecutan en una sola sentencia con cuatro subconsultas; se conservan los predicados sobre variantes activas, stock desconocido e imágenes. El total del controlador baja de 6 a 3 consultas. Esto reduce viajes a la base, no elimina las cuatro operaciones de conteo.
- Pedidos: el listado sin filtros repetía un conteo total ya disponible en los totales por estado. Ahora reutiliza la suma actual para la paginación: 3 a 2 consultas. Con filtros conserva el conteo específico y las 3 consultas. No se agrega caché.
- No se encontró N+1 en estas pantallas. Pedidos limita a 20 registros por página y Dashboard a 8 recientes. Productos ya pagina de a 15 y CMS de a 20. Categorías obtiene el árbol completo en una consulta.
- Navegación mediante enlaces y recarga completa de Blade. El layout administrativo no carga estadísticas adicionales ni el compositor del storefront. Las sesiones en base y la autenticación siguen realizando su trabajo habitual por petición. No se modificaron.

## Mediciones locales comparables

Mismo PHP 8.4, SQLite de revisión `.tools/render-demo-review.sqlite`, mismo contenido, 5 rondas alternadas después de una ronda de calentamiento; invocación directa de los controladores y renderizado explícito de la vista. Medianas en milisegundos. SQL medido con DB::listen; controlador y Blade con hrtime. No incluye bootstrap, middleware, conexión remota, HTTP ni pintado del navegador. La base de revisión es pequeña: no es una prueba de carga.

| Sección | Consultas antes → después | SQL antes → después | Controlador incluyendo SQL antes → después | Blade antes → después |
|---|---:|---:|---:|---:|
| Dashboard | 6 → 3 | 1,22 → 0,88 | 3,36 → 2,91 | 3,31 → 3,31 |
| Pedidos sin filtros | 3 → 2 | 0,54 → 0,45 | 1,44 → 1,28 | 3,49 → 3,62 |

El tiempo propio del controlador, restando SQL en cada muestra y tomando la mediana, pasó de 2,14 a 2,04 ms en Dashboard y de 0,90 a 0,87 ms en Pedidos. Las variaciones pequeñas de PHP y Blade no demuestran una mejora o regresión relevante. La reducción verificable es el número de viajes SQL; no se promete un ahorro remoto determinado.

## Demo: línea base y límite de la comparación

Diagnóstico previo: HttpClient autenticado, servicio caliente, tres rondas alternando rutas, cronómetro hasta cabeceras y cuerpo, respuestas 200. Medianas de respuesta HTML: Dashboard 2,413 s; Productos 1,678 s; Categorías 1,455 s; Pedidos 1,959 s; CMS 1,719 s; /up 0,264 s; CSS administrativo 0,231 s. El cuerpo agregó 0–10 ms: la espera principal ocurrió antes de las cabeceras. No son tiempos de carga completa del navegador.

**Después en Render: pendiente, no medido.** No se desplegó esta corrección: existen cambios locales de fase 18 aún no publicados. Para una comparación remota válida, publicar de forma aislada y realizar un único despliegue controlado; repetir las mismas tres rondas autenticadas, calentamiento, rutas y cliente. No comparar los milisegundos SQLite con los segundos HTTP de la demo.

Render está en Oregon y Neon en Ohio. La distancia hace plausible un costo por viaje SQL, pero no se midió el tiempo SQL remoto ni se aisló el establecimiento de conexión; no se atribuye toda la demora a esa causa. En el diagnóstico previo, ventana 14:26:55–14:36:55 UTC, el máximo de los promedios de CPU fue 0,008208 cores sobre 0,15 y la memoria rondó 81 MiB sobre 512 MiB. No prueban saturación sostenida de Render Free, ni descartan picos breves. No hay evidencia suficiente para declarar que Render Free es el límite principal. No se modificó infraestructura.

## Verificación y pendientes

- Pruebas específicas: 42 aprobadas, 240 aserciones. Incluyen reglas de stock, últimos pedidos, filtros, página 2, catálogo de pedidos vacío y actualización de totales entre solicitudes; presupuestos de 3 consultas para Dashboard y 2 para Pedidos sin filtros.
- Pint: aprobado.
- Suite completa: 421 pruebas aprobadas, 2391 aserciones; una única ejecución, 37,37 segundos.
- Pendiente: validación de estas consultas en PostgreSQL y medición HTTP posterior al despliegue aislado. No agregar más optimizaciones hasta contar con esa evidencia.
- Fuera de alcance: auditoría integral de seguridad y otras tareas del documento general de fase 19; integración real con Soluciones Es.

Archivos de aplicación modificados: `backend/app/Http/Controllers/Admin/DashboardController.php`, `backend/app/Http/Controllers/Admin/OrderController.php`. Pruebas: `backend/tests/Feature/AdminDashboardTest.php`, `backend/tests/Feature/OrderManagementTest.php`.
