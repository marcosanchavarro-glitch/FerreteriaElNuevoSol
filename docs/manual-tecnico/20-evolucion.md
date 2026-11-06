# 20. Estado actual y evolución

## 20.1 Matriz de estado

| Componente | Estado y evidencia | Límite |
|---|---|---|
| Autenticación y roles | Implementado con pruebas históricas | SMTP real no habilitado; sin CRUD general de usuarios |
| Productos, variantes, categorías e imágenes | Implementado con pruebas históricas | Restricciones por gestión externa y sin borrado genérico de productos |
| Catálogo y compra | Implementado con pruebas y revisión local | Sin pagos, envíos, reserva/descuento de stock |
| Pedidos y notificaciones internas | Implementado con pruebas históricas | Sin transmisión a caja ni notificaciones externas automáticas |
| CMS | Implementado con pruebas históricas | Vigencia temporal de banners existe en datos/lectura, no edición en panel |
| Auditoría | Implementada y cubierta | Protección por aplicación, no sistema inalterable a nivel DB |
| Motor SKU | Implementado, validado en PG 17.11, pendiente de publicación | Importación JSON manual de prueba, 100 filas |
| Optimización Dashboard/Pedidos | Implementada, validada y pendiente de publicación | Mejora de HTTP remoto todavía no medida |
| Soluciones Es | Pendiente | Sin documentación de transporte ni conector real |
| Respaldos | Procedimiento propuesto, recuperación no acreditada | Bloquea la publicación condicionada |

## 20.2 Pendientes concretos

La próxima operación autorizable es acreditar recuperación de base e imágenes; después, integrar y publicar una sola candidata con comprobación de lecturas y mediciones equivalentes. No debe repetirse la suite general por el solo hecho de haber escrito documentación. Al modificar código posteriormente, atribuir cada resultado al SHA probado.

Para el punto de venta faltan contrato del proveedor, credenciales de prueba por canal seguro y decisiones de sincronización. El motor interno no resuelve autenticación remota, descarga incremental, programación de tareas ni conciliación de fallos de red. No introducir datos comerciales en CI ni conectar bases reales a pruebas destructivas.

## 20.3 Deuda técnica y recomendaciones

Se conserva como pendiente medio el alcance amplio del bloqueo de importación; medir contención antes de escalar su uso. Se conserva como pendiente bajo la facilidad de descubrir desplazamiento horizontal en tablas móviles. La matriz de navegadores físicos y la prueba de carga con catálogo representativo no están completadas. La lectura por línea del carrito y la entrega de imágenes por backend deben evaluarse con datos de rendimiento antes de cualquier refactorización.

Actualizar documentos históricos que quedaron desfasados puede planificarse aparte; esta entrega no los modifica. Mantener el manual por capítulos, con diagramas y referencias al código, evita depender de una narración de fases para comprender el funcionamiento.

## 20.4 Información no verificada en esta revisión documental

No se verificaron el estado actual de las cuentas remotas, cuotas, respaldos, parámetros secretos, migraciones aplicadas en Neon ni SHA actualmente servido después de la última evidencia histórica. No se midieron versiones de parche actuales de PHP/Composer/SQLite en producción ni se volvieron a ejecutar pruebas. El inventario anexo registra versiones fijadas en archivos y referencias locales, sin convertirlas en afirmaciones sobre un servicio vivo.

La aplicación no fue publicada ni modificada. La revisión documental describe una versión identificable y sus límites, y permite a otro desarrollador continuar desde un estado trazable.

## 20.5 Mejoras planificadas y avance local

Avance local de esta ronda: tarjetas y tabla alternativa, filtros, editor integrado con guardados independientes, respuestas JSON, drawer móvil, vista previa del destinatario y mensaje WhatsApp ampliado están implementados localmente. No están publicados. Se reutilizan los modos comerciales existentes; no se ha definido ni habilitado conexión con Punto de Venta. Los detalles y límites actuales están en 8.5, 12.4, 15.4 y docs/correcciones-administracion.md. La tabla siguiente conserva el origen de las propuestas y debe leerse junto con este estado de avance.

Las siguientes decisiones, registradas el 6 de noviembre de 2026, son la referencia de alcance prevista. Su estado de implementación local se actualiza en la nota anterior; su existencia no amplía los resultados históricos de PostgreSQL ni acredita publicación.

| Área | Implementación actual documentada | Cambio propuesto | Dependencias pendientes |
|---|---|---|---|
| Rendimiento | Optimizaciones focalizadas de Dashboard/Pedidos validadas en la candidata; mejora HTTP remota aún no medida | Optimizar llamadas a PostgreSQL, navegación y operaciones de edición y guardado | Medir los recorridos afectados y localizar consultas u operaciones costosas antes de elegir correcciones |
| Administración de productos | Listado tabular paginado y acciones administrativas existentes | Usar tarjetas como vista principal, con fotografías, precios, SKU, disponibilidad y acciones rápidas; conservar tabla opcional | Definir acciones y criterios de disponibilidad; desarrollar y verificar ambas vistas |
| Editor unificado | Edición de producto y recorridos existentes para categorías, imágenes y variantes | Integrar esas tareas en una pantalla única, con opciones avanzadas desplegables | Definir interacción y guardado conservando validaciones y permisos; implementar el editor |
| Modalidad comercial | Modos calculated/inquiry y validaciones de stock descritos en capítulos 8–10; los pedidos no reservan ni descuentan stock | Distinguir explícitamente productos de catálogo por consulta y productos con stock controlado | Definir reglas de disponibilidad y control de stock, incluidas sus responsabilidades frente a Punto de Venta; los modos actuales no acreditan el circuito futuro completo |
| Pedidos y WhatsApp | Se construye un enlace desde los snapshots; en demo se suprime. El número ya admite configuración administrativa en settings | Completar el circuito de solicitud mediante WhatsApp y asegurar la configuración del destinatario desde el backend | Definir el cierre funcional esperado y validar el recorrido; la configuración existente del número debe aprovecharse, sin presentarla como una incorporación pendiente. Un enlace no acredita entrega o recepción del mensaje |
| Punto de Venta | Motor manual JSON por SKU, sin conexión real con Soluciones Es | Mantener Laravel; recibir precios y stock de Punto de Venta, gestionar pedidos en la web y facturar por separado en Punto de Venta | Acordar con el proveedor el mecanismo de conexión y su contrato; no hay transporte ni sincronización automática definidos |

## 20.6 Límites y decisiones pendientes

La separación entre catálogo por consulta y stock controlado requiere una definición funcional antes de traducirse en campos, estados o reglas nuevas. Esta revisión no prescribe reserva, descuento, conciliación ni actualización automática de existencias. Tampoco presupone envío automático de pedidos al sistema de caja o emisión de comprobantes desde Laravel.

El proveedor debe confirmar sus capacidades de intercambio antes de seleccionar un mecanismo de integración. No se adopta WooCommerce como solución definitiva ni se inventan endpoints, conectores o credenciales. Las propuestas de tarjetas y editor unificado no implican un cambio de framework. La ejecución de estas mejoras requiere trabajo posterior autorizado; esta revisión es exclusivamente documental.
