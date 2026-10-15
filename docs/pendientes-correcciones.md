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

**Después en Render: pendiente, no medido.** Las fases 18 y 19 están publicadas únicamente en una rama de validación, sin fusionar ni desplegar. Para una comparación remota válida, autorizar la integración de la versión candidata y realizar un único despliegue controlado; repetir las mismas tres rondas autenticadas, calentamiento, rutas y cliente. No comparar los milisegundos SQLite con los segundos HTTP de la demo.

Render está en Oregon y Neon en Ohio. La distancia hace plausible un costo por viaje SQL, pero no se midió el tiempo SQL remoto ni se aisló el establecimiento de conexión; no se atribuye toda la demora a esa causa. En el diagnóstico previo, ventana 14:26:55–14:36:55 UTC, el máximo de los promedios de CPU fue 0,008208 cores sobre 0,15 y la memoria rondó 81 MiB sobre 512 MiB. No prueban saturación sostenida de Render Free, ni descartan picos breves. No hay evidencia suficiente para declarar que Render Free es el límite principal. No se modificó infraestructura.

## Verificación y pendientes

- Pruebas específicas: 42 aprobadas, 240 aserciones. Incluyen reglas de stock, últimos pedidos, filtros, página 2, catálogo de pedidos vacío y actualización de totales entre solicitudes; presupuestos de 3 consultas para Dashboard y 2 para Pedidos sin filtros.
- Pint: aprobado.
- Suite completa: 421 pruebas aprobadas, 2391 aserciones; una única ejecución, 37,37 segundos.
- PostgreSQL: validación focalizada aprobada, detallada debajo. Pendiente: medición HTTP posterior al despliegue autorizado. No agregar más optimizaciones hasta contar con esa evidencia.
- Fuera de alcance: auditoría integral de seguridad y otras tareas del documento general de fase 19; integración real con Soluciones Es.

Archivos de aplicación modificados: `backend/app/Http/Controllers/Admin/DashboardController.php`, `backend/app/Http/Controllers/Admin/OrderController.php`. Pruebas: `backend/tests/Feature/AdminDashboardTest.php`, `backend/tests/Feature/OrderManagementTest.php`.

## Versión candidata: validación PostgreSQL

- Rama: `codex/validation-phase18-19`, sin fusionar. Base: `939164e`.
- Fase 18: `130c887`; Fase 19: `2962d21`; pruebas y workflow: `de5f43681f6117e39c8226a0c0ab040ce55a2952` (código validado).
- [GitHub Actions, ejecución 38757741821](https://github.com/marcosanchavarro-glitch/FerreteriaElNuevoSol/actions/runs/38757741821): **57 pruebas aprobadas, 347 aserciones**, PostgreSQL **17.11**, PHP 8.4. Una sola ejecución focalizada, sin fallos ni omisiones de pruebas. Job Docker omitido deliberadamente.
- Grupos: CommercialSyncTest, CommercialSyncPostgresTest, AdminDashboardTest y OrderManagementTest. Se reutilizaron 56 pruebas y se agregó una para el comportamiento real de dos conexiones PostgreSQL.
- Verificado: coincidencia SKU y ceros iniciales, conflictos, variantes, idempotencia, previsualización sin cambios comerciales, confirmación única, autorización y propietario de la vista previa, rechazo de vistas vencidas/desactualizadas, conservación editorial y snapshots, reversión ante fallo de auditoría, filtros, paginación y presupuestos SQL administrativos.
- La prueba nueva usa datos confirmados fuera de una transacción de prueba envolvente: durante la aplicación, una segunda conexión observa los precios anteriores y recibe SQLSTATE 55P03 al intentar FOR UPDATE NOWAIT sobre producto, variante y vista previa. Tras el commit observa ambos precios nuevos y puede bloquear nuevamente la vista previa. Esto verifica bloqueos de filas y aislamiento; no constituye una prueba de carga ni de múltiples procesos compitiendo por el bloqueo de caché.
- Base exclusiva del contenedor efímero `ens_test` en localhost; credenciales ficticias del workflow, DB_URL vacío. No se usó Neon, Render, datos comerciales ni credenciales de producción. No se instalaron servidores locales.
- El workflow conserva su ejecución normal en otras ramas; en esta rama selecciona solo PostgreSQL y estos cuatro grupos. Los commits documentales posteriores pueden usar `[skip ci]` porque no alteran el código validado.
- No se detectaron fallos que exigieran cambios adicionales en la aplicación. No se agregaron migraciones, dependencias ni conectores. La suite general anterior de 421 pruebas no se repitió.

## Pendientes para publicación y cierre amplio

| ID | Módulo | Severidad / tipo | Estado y reproducción breve | Recomendación |
|---|---|---|---|---|
| P19-01 | Rendimiento remoto | Verificación pendiente | Solo existe línea base HTTP previa; la versión candidata no está desplegada | Tras autorización, un despliegue y tres rondas equivalentes |
| P19-02 | Sincronización | Medio, alcance de bloqueo | La aplicación lee y bloquea todas las filas de productos y variantes en `plan(..., true)`, aun para una importación pequeña | Documentado; medir contención con catálogo representativo antes de uso comercial. No optimizar sin evidencia adicional |
| P19-03 | Seguridad y compra | Verificación pendiente | La suite previa incluye autenticación/CSRF, permisos, imágenes, catálogo y checkout; esta ejecución PostgreSQL se limitó a los módulos solicitados | Completar la revisión focalizada del checklist original sin duplicar pruebas existentes |
| P19-04 | Vistas y móvil | Verificación pendiente | No se realizó una nueva comprobación interactiva escritorio/móvil de esta candidata | Revisar formularios de importación, tablas administrativas y recorrido de compra con datos ficticios |
| P19-05 | Publicación | Autorización pendiente | Rama validada sin merge ni despliegue | Autorizar integración; verificar configuración existente y migraciones aplicadas, desplegar una sola vez y hacer comprobación funcional |

El motor manual de prueba y las optimizaciones administrativas superan la validación técnica solicitada. El cierre completo del documento original de fase 19 sigue condicionado a las verificaciones pendientes; no se certifica aún preparación comercial integral. La conexión real con Soluciones Es permanece fuera de esta candidata.
