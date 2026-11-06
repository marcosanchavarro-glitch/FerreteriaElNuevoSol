# 16. Pruebas y control de calidad

## 16.1 Organización y alcance

PHPUnit se configura en `backend/phpunit.xml`, con suites Unit y Feature. Feature contiene la mayoría de los casos de negocio y permisos; Unit incluye la prueba de ejemplo del esqueleto. Las fábricas están en `database/factories`. La cobertura funcional no implica un porcentaje medido de cobertura de líneas: CI configura `coverage: none`.

| Área | Grupos de prueba principales |
|---|---|
| Modelo y migraciones | DataModelTest, DataIntegrityTest, MigrationLifecycleTest |
| Acceso y perfil | AuthenticationTest, ProfileSecurityTest |
| Catálogo interno | ProductManagementTest, VariantManagementTest, CategoryManagementTest, ImageManagementTest |
| Catálogo público | PublicCatalogTest, PublicSearchTest |
| Compra y seguimiento | CartCheckoutTest, OrderManagementTest, CustomerOrdersTest, OrderNotificationsTest |
| CMS y auditoría | HomepageCmsTest, SiteSettingsTest, ActivityAuditTest |
| Demo y despliegue | DemoModeTest, CloudinaryImagesTest, RenderConfigurationTest |
| Candidata | CommercialSyncTest, CommercialSyncPostgresTest, AdminDashboardTest |

## 16.2 Ejecución segura y específica

Desde `backend`, en una copia local de pruebas sin configuración cacheada de producción:

```powershell
php artisan test --compact tests/Feature/CartCheckoutTest.php
php artisan test --compact --filter='CommercialSync(Test|PostgresTest)|AdminDashboardTest|OrderManagementTest'
```

`phpunit.xml` propone SQLite `:memory:`, correo array, sesión/caché array y cola sync. Variables ya definidas en el proceso pueden prevalecer: verificar DB_CONNECTION, DB_DATABASE y DB_URL antes de ejecutar. En SQLite, la prueba específica PostgreSQL no acredita bloqueos PostgreSQL. Nunca apuntar RefreshDatabase/limpieza de pruebas a Neon de demo. `php artisan test --compact` ejecuta la suite completa y debe reservarse para cambios que lo justifiquen, no cada edición documental.

## 16.3 GitHub Actions

`.github/workflows/verify.yml` responde a push, pull_request y workflow_dispatch, con permisos contents:read. En la rama candidata (o PR cuyo head sea esa rama) selecciona solo pgsql y filtra los cuatro grupos de importación/Dashboard/Pedidos. Levanta `postgres:17` con base y credenciales efímeras de prueba, puerto 5432 y healthcheck. DB_URL vacío impide heredar una conexión externa.

En otras ramas ejecuta la matriz SQLite/PostgreSQL y el job Docker. Ese job construye la imagen production, comprueba que no contiene archivos secretos ni SQLite, arranca Apache con un entorno de prueba mínimo, consulta `/up` y CSS y valida render.yaml con el esquema oficial. No es un despliegue comercial ni un recorrido completo de pedidos. El Dockerfile tiene además una etapa tests; no se ejecuta automáticamente al construir solo production.

## 16.4 Evidencia histórica atribuida

| Evidencia | Versión/alcance | Resultado registrado |
|---|---|---|
| Suite general previa | Estado previo a agregar la prueba PG de dos conexiones; informe de Fase 19 | 421 pruebas, 2391 aserciones; no se presenta como suite completa de un commit posterior |
| Grupos Dashboard/Pedidos locales | Optimización de Fase 19 | 42 pruebas, 240 aserciones |
| Actions 38757741821 | Código `de5f43681f6117e39c8226a0c0ab040ce55a2952`, PostgreSQL 17.11, PHP 8.4 | 57 pruebas, 347 aserciones; job Docker omitido deliberadamente |
| Revisión interactiva focalizada | Candidata sin cambios funcionales posteriores en este trabajo | Compra invitado/cliente y vistas 1366×900 / 390×844 con datos locales ficticios |

[Ejecución PostgreSQL registrada](https://github.com/marcosanchavarro-glitch/FerreteriaElNuevoSol/actions/runs/38757741821). El manual toma los resultados del informe y del historial ya verificado; no relanzó Actions ni PHPUnit. `af85ee7` añade documentación al código validado. La revisión local posterior comprobó dos pedidos ficticios, sin abrir enlaces WhatsApp ni modificar servicios externos.

La prueba de dos conexiones valida lectura de valores anteriores antes del commit, rechazo de FOR UPDATE NOWAIT sobre producto, variante y preview, y liberación de bloqueos después. No cubre todos los patrones de concurrencia de procesos, límites del proveedor o contención de un catálogo comercial grande.

## 16.5 Ronda administrativa local

Se reutilizaron los grupos de productos, variantes, categorías, imágenes, configuración, compra, pedidos, Dashboard, demo y sincronización. Se agregaron regresiones de filtros, permisos y auditoría de acciones rápidas, consultas constantes del listado, ausencia de consultas de variantes en el editor simple, guardado JSON y errores, disponibilidad por consulta y contenido WhatsApp. Los resultados exactos y límites se registran en docs/correcciones-administracion.md. No se volvió a ejecutar toda la suite ni PostgreSQL remoto; la evidencia anterior de PostgreSQL no se atribuye a estos cambios nuevos.
