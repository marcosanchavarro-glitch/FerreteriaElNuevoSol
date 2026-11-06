# Manual Técnico y de Programación de Ferretería El Nuevo Sol

Versión documental 1.0, revisión 3. Fecha de actualización: 6 de noviembre de 2026.

La revisión 3 añade el estado local de la ronda administrativa en `codex/admin-corrections`, sobre la candidata af85ee7. Tarjetas, editor integrado, guardado progresivo y ajustes de WhatsApp son cambios locales posteriores; no se atribuyen a la validación PostgreSQL histórica ni a la demo publicada. Los apartados añadidos con el título «Ronda administrativa local» prevalecen para esos recorridos sobre la descripción de la candidata base.

Este manual describe la versión candidata del repositorio, no una versión comercial ya conectada al punto de venta. Está dirigido a desarrolladores que deban instalar, comprender, mantener o extender el sistema y a evaluadores académicos.

## Referencia y método

- Repositorio: https://github.com/marcosanchavarro-glitch/FerreteriaElNuevoSol
- Rama de referencia: `codex/validation-phase18-19`.
- Revisión documental de referencia: `af85ee7a6e1179ac77757a72c9f05b17bc9983bf`.
- Código validado en PostgreSQL: `de5f43681f6117e39c8226a0c0ab040ce55a2952`. El commit posterior `af85ee7` solo incorpora documentación.
- Base de comparación: `codex/render-demo`, commit `939164e3b71ccf14c4ffe5dcd64cf98c362aa818`, última publicación confirmada en el historial de trabajo del 15/10/2026. No se consultó nuevamente Render para redactar este manual; no se certifica el estado remoto actual.
- No hay una rama local llamada `main` en el inventario consultado. No se atribuye ese nombre a la rama de publicación ni se infiere la rama predeterminada remota a partir del nombre.
- El informe local `docs/pendientes-correcciones.md` ya tenía cambios aprobados sin commit al comenzar esta tarea. Se usa como evidencia de la revisión focalizada, separada de la evidencia de CI.

Se leyeron rutas, migraciones, modelos, políticas, solicitudes, controladores, servicios, layouts, recursos estáticos, manifiestos, pruebas y configuración de despliegue. La primera revisión fue exclusivamente documental. En la ronda local posterior se ejecutaron pruebas focalizadas en SQLite en memoria y comprobaciones de guardado sobre productos ficticios locales, documentadas en docs/correcciones-administracion.md. No se ejecutaron migraciones sobre datos persistentes, instalaciones ni despliegues. Los comandos operativos se documentan para su uso posterior; su presencia no indica ejecución durante la redacción.

**Estados empleados:** implementado con cobertura y validación histórica; implementado pendiente de publicación; parcial (infraestructura de datos o código presente sin recorrido completo); pendiente (sin implementación comprobada). La existencia de una prueba no equivale a una ejecución reciente. Las versiones y fechas de evidencia se detallan en el capítulo 16.

## Índice

1. [Introducción](01-introduccion.md)
2. [Arquitectura de software](02-arquitectura.md)
3. [Tecnologías y herramientas](03-tecnologias.md)
4. [Organización del proyecto](04-organizacion.md)
5. [Instalación y configuración](05-instalacion.md)
6. [Base de datos](06-base-datos.md)
7. [Autenticación, roles y seguridad](07-seguridad.md)
8. [Módulo de productos](08-productos.md)
9. [Catálogo público](09-catalogo.md)
10. [Carrito y checkout](10-carrito-checkout.md)
11. [Gestión de pedidos](11-pedidos.md)
12. [Panel administrativo](12-panel.md)
13. [CMS y personalización](13-cms.md)
14. [Sincronización por SKU](14-sincronizacion.md)
15. [Integraciones externas](15-integraciones.md)
16. [Pruebas y control de calidad](16-pruebas.md)
17. [Despliegue](17-despliegue.md)
18. [Respaldos y recuperación](18-respaldos.md)
19. [Mantenimiento y resolución de problemas](19-mantenimiento.md)
20. [Estado actual y evolución](20-evolucion.md)

Los anexos incluyen [diccionario de datos](anexos/diccionario-datos.md), [inventario y trazabilidad](anexos/inventario.md), [configuración](anexos/configuracion.md) y [glosario y referencias](anexos/glosario-referencias.md). Los seis diagramas mantenibles se encuentran en `diagramas/`; también se incluyen en la versión consolidada.

## Mantenimiento documental

La fuente editable está organizada en capítulos y anexos. El generador `recursos/consolidar.py` concatena esas fuentes e inserta los diagramas en `docs/MANUAL-TECNICO-EL-NUEVO-SOL.md`, ajustando enlaces relativos. No analiza ni ejecuta Laravel. Después de modificar capítulos, ejecutarlo con Python 3 desde cualquier directorio y revisar el resultado.

El formato Word es opcional. En el entorno inspeccionado hay biblioteca de escritura DOCX, pero no se encontró un renderizador DOCX disponible entre las herramientas inspeccionadas para verificar páginas y diagramas sin instalaciones adicionales. Por ello esta revisión entrega Markdown completo y no un Word sin validación visual.
