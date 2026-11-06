# Anexo D. Glosario y referencias

## D.1 Glosario

| Término | Significado en este proyecto |
|---|---|
| Blade | Motor de plantillas del servidor; produce el HTML de cada página |
| Candidata | Revisión preparada y validada, pendiente de publicación autorizada |
| Cast | Conversión Eloquent entre representación persistida y valor de PHP |
| Centavo | Unidad entera de almacenamiento de importes, sin aritmética monetaria flotante |
| Checkout | Confirmación de contacto, retiro y líneas para crear un pedido |
| CMS | Edición de contenido de inicio y banners por personal autorizado |
| CSRF | Falsificación de solicitudes entre sitios, mitigada mediante token en formularios web |
| Eager loading | Precarga de relaciones para evitar consultas adicionales por cada registro |
| Eloquent | ORM de Laravel: modelos, relaciones, consultas y eventos |
| FK | Clave foránea que vincula filas y aplica reglas de integridad |
| Gate / Policy | Autorización global o por recurso; no equivale solo a ocultar botones |
| IDOR | Acceso indebido al cambiar un identificador; se previene con alcance de consulta y autorización |
| Idempotencia | Repetir una misma confirmación no debe duplicar su efecto |
| Inquiry | Modalidad de consulta: importe pendiente de confirmación |
| Middleware | Componente que inspecciona una solicitud antes o después del controlador |
| Milésima | Unidad entera de cantidad; 1500 representa 1,500 unidades |
| N+1 | Consulta inicial seguida de una consulta adicional por cada elemento |
| Normalized JSON | Contrato manual de importación; no es una API activa de Soluciones Es |
| ORM | Capa que relaciona objetos del programa con registros de base |
| PK | Clave primaria de una tabla |
| Preview | Plan persistido de importación, ligado al actor, vencimiento y huella |
| Rollback | Reversión de una transacción; distinto de volver a un contenedor anterior |
| Seeder | Inicializador de datos; no ejecutarlo indiscriminadamente sobre una base existente |
| SKU | Código comercial de coincidencia; puede ser ambiguo y no es globalmente único en SQL |
| Slug | Segmento legible y único utilizado en URLs de catálogo |
| Snapshot | Copia de datos comerciales en el pedido para conservar su interpretación histórica |
| Transacción | Grupo de operaciones de base que confirma o revierte en conjunto |
| XSS | Inyección de contenido ejecutable, mitigada con escape de salida y validación contextual |

## D.2 Referencias y prioridad documental

La fuente principal de comportamiento es el código de la candidata identificada al inicio, seguido por sus pruebas y las ejecuciones históricas citadas. Los documentos de fases explican decisiones, pero pueden describir intenciones o estados anteriores. Las referencias externas explican tecnologías; no prueban que una configuración esté activa en la demo.

- [Repositorio Ferretería El Nuevo Sol](https://github.com/marcosanchavarro-glitch/FerreteriaElNuevoSol): fuente del proyecto; consultar la revisión indicada, no asumir que la rama predeterminada coincide con ella.
- [Validación PostgreSQL de la candidata](https://github.com/marcosanchavarro-glitch/FerreteriaElNuevoSol/actions/runs/38757741821): ejecución histórica referida en el capítulo 16.
- [Documentación oficial Laravel 13](https://laravel.com/docs/13.x): framework, Eloquent, Blade, seguridad, sesiones, validación y pruebas; las versiones exactas instalables están en composer.lock.
- [PostgreSQL 17: pg_dump](https://www.postgresql.org/docs/17/app-pgdump.html): alcance y formato del respaldo lógico. No confundir exportación con restauración verificada.
- [Cloudinary: backups and version management](https://cloudinary.com/documentation/backups_and_version_management): recuperación de activos y versiones. Debe contrastarse con la configuración y cobertura efectiva de la cuenta.

Para mantener el documento, revisar también los archivos locales del anexo B: migraciones, rutas, controladores, servicios, policies, requests, tests, Dockerfile, workflow y render.yaml. No se incluyen enlaces a paneles privados, URLs de conexión ni archivos de secretos.

## D.3 Criterio de actualización

Al modificar una función, actualizar su capítulo y la evidencia de versión. Si cambia el esquema, regenerar y revisar el diccionario; el extractor es una ayuda para las migraciones actuales y no un analizador general de PHP. Si cambian rutas, regenerar su inventario en un entorno aislado antes de reconstruir el consolidado. Si cambian contratos externos o planes SaaS, verificar nuevamente la documentación oficial y registrar la fecha. Mantener separados resultados ejecutados, comprobaciones estáticas y recomendaciones pendientes.
