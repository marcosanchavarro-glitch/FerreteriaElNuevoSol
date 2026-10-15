# Integración Punto de Venta → El Nuevo Sol

Fecha de relevamiento: 8 de octubre de 2026.

**18.A: inspección y preparación local realizadas; relevamiento externo pendiente de documentación. Mecanismo: NO CONFIRMADO. 18.B: no ejecutada, bloqueada por falta de contrato técnico confirmado y entorno de prueba autorizado. La Fase 18 completa no está terminada.**

## Evidencia y alcance

El usuario identifica al proveedor como **«Soluciones Es»**. El documento de esta fase describe el programa como basado en Microsoft Access, con conectividad en la nube y algún vínculo con WooCommerce. Estos últimos datos son antecedentes aportados, no comprobaciones técnicas: no se dispone de versión, manual, contrato de interfaz ni muestra real.

La búsqueda pública por el nombre y por Access/WooCommerce no permitió identificar de forma inequívoca al proveedor y al producto instalado. Se encontró un sitio de nombre similar, [ES Soluciones](https://essoluciones.com.ar/), cuyo resultado indexado describe soluciones para hospitalidad; **no se confirmó que sea el proveedor de esta ferretería**. La página no pudo recuperarse durante la consulta. No se toma como evidencia del protocolo ni se eligió un conector por coincidencia de nombre.

Se revisaron modelos, casts, políticas, Form Requests, servicios, rutas, migraciones, pruebas y referencia WordPress local. No se encontraron adaptadores de catálogo externo, trabajos de sincronización, comandos de importación ni programación de sincronizaciones. `SyncRun` y `SyncRunStatus` son estructuras de datos, no un motor. Las llamadas Eloquent `sync()` de categorías/notificaciones son asociaciones internas. El frontend WordPress recibido no aporta un conector técnico con Punto de Venta; coincide con lo documentado en `fase-1-frontend.md`.

No se accedió a Access, al sistema comercial, a una tienda WooCommerce real ni a paneles privados. No se solicitaron ni utilizaron credenciales. No se contactó al proveedor en nombre del usuario.

## Verificación del estado real

- Fase 17 comprobada: `ActivityLogService`, modelo, migración complementaria, rutas exclusivas de Admin, vistas y pruebas de auditoría presentes.
- Suite completa ejecutada antes de modificar documentación: **388 pruebas y 2130 aserciones, todas aprobadas**.
- El repositorio conserva archivos sin seguimiento; no se creó commit ni se descartaron cambios.
- No se modificó código ejecutable, datos comerciales, configuración o esquema en esta etapa. No se crearon pruebas de un proveedor hipotético.
- PHPUnit se ejecutó fuera del sandbox por el problema de lectura de archivos ya identificado. Persisten advertencias del entorno Windows sobre las extensiones curl/intl; no impidieron esta suite. Antes de elegir un transporte habrá que verificar sus requisitos de ejecución.

## Datos internos existentes versus datos externos disponibles

**Ningún campo del proveedor está técnicamente confirmado aún.** La existencia de un campo en Laravel no demuestra que Punto de Venta lo entregue.

| Dato | Soporte actual en Laravel | Confirmación externa pendiente |
|---|---|---|
| Identificador | Product y ProductVariant.external_id, texto nullable e indexado | Estabilidad, unicidad, alcance por empresa/sucursal/padre y posible reciclaje |
| Código comercial | sku nullable e indexado | Duplicados, ceros iniciales, cambios, ausencias y equivalencia con ID |
| Nombre | name obligatorio; variantes con name/value | Nombre comercial versus nombre web y presentación |
| Precio Lista 1 | price como entero de centavos, nullable | Campo exacto de Lista 1, moneda, IVA, descuentos, escala y redondeo |
| Stock | Entero de milésimas en SQLite; string decimal en PHP | Stock físico/disponible/reservado, depósito, negativos, nulidad y precisión |
| Unidades | Enum: unidad, metro, kilogramo, gramo, litro, mililitro, caja, paquete, bulto, rollo y otra | Códigos reales, fraccionamiento y factores de conversión |
| Variantes | product_id, external_id, sku, price, stock, active; unidad/modalidad opcional heredada | Relación padre-hijo, IDs y significado comercial de active |
| Estado comercial | No hay campo separado en Product; status es publicación web | Cómo separar baja/discontinuación de visibilidad editorial |
| Marca/categorías/fotos/descripciones | Categorías, fotos y textos web existentes; marca sin campo dedicado en Product | Disponibilidad real; no autorizan sobrescritura editorial |
| Sincronización | external_synced_at nullable | Fecha de origen, zona horaria, versión/cursor y semántica de última sincronización |

### Identidad y duplicados

Los índices de external_id y sku **no son únicos** en ambas tablas. Slug sí es único. La validación de SKU de variantes locales limita duplicados dentro del mismo producto; esto no define la identidad externa.

Política propuesta para 18.B, pendiente del contrato: conservar IDs como texto, no normalizar ceros ni fusionar por nombre; usar external_id solo cuando sea fiable y con su alcance confirmado. SKU duplicado/ausente o varios candidatos deben generar conflicto y detener ese registro. No vincular automáticamente un producto local por coincidencia de nombre o SKU ambiguo. Evaluar una restricción única con ámbito correcto tras revisar duplicados existentes y el contrato; no agregarla ahora.

### Dinero y cantidades

`MinorUnits` admite centavos enteros no negativos (hasta 15 dígitos), rechaza floats y conserva null como desconocido. `ProductDisplay::toMinorUnits()` supone una cadena previamente validada y hasta dos decimales; **no es un parser de cualquier respuesta externa**. No usarlo sobre datos del proveedor sin validar formato, moneda, impuestos y escala. Ejemplo interno: 2500,51 pesos → 250051 centavos; no afirma la unidad enviada por el proveedor.

`ScaledQuantity` admite strings con hasta 12 dígitos enteros y tres decimales, incluso negativos; SQLite guarda milésimas. Los formularios comerciales locales son más restrictivos y no admiten stock negativo. Null, cero y dato ausente deben distinguirse. No convertir kilos a envases, litros a unidades ni descartar decimales sin una regla confirmada.

Un producto variable no admite precio/stock en el padre: viven en variantes. Un padre con variantes no puede convertirse sin más en producto simple. Las importaciones tendrán que respetar estas invariantes y los snapshots de pedidos existentes.

## Propiedad de datos y protección actual

| Propietario | Datos y regla |
|---|---|
| Punto de Venta, al confirmar integración | Código comercial, precio público Lista 1, stock y variantes comerciales sincronizadas |
| Web | Imágenes, descripciones, categorías asignadas, slug, visibilidad/publicación, destacados, contenido SEO que exista, banners y CMS |
| Por acordar expresamente | Nombre comercial/web, unidad, cambio de tipo simple/variable y separación del estado comercial |

`Product::isExternallyManaged()` protege si existe external_id **o** external_synced_at. ProductPolicy impide editar identidad comercial externa incluso a Admin; solo Admin modifica importes/SKU de productos locales. ProductRequest rechaza inyectar los marcadores externos. El contenido editorial del padre permanece editable.

Una variante se considera externa también cuando lo es su padre. ProductVariantPolicy bloquea su modificación y la creación manual bajo un padre externo, además de la eliminación. Las Policies gobiernan las rutas de usuario: no protegen por sí solas un proceso de consola o asignación masiva Eloquent. El futuro motor deberá usar una allowlist comercial explícita y nunca `fill()` con el payload completo.

Product.status representa borrador/publicado/privado y no debe usarse como sustituto automático de una baja comercial. La posible necesidad de un estado comercial separado se decidirá con muestras del proveedor. No se crea un campo ni se cambia el comportamiento ahora. Nuevos productos: propuesta de borrador hasta revisión; productos/variantes ausentes: conflicto o revisión, nunca eliminación automática.

## Mecanismo y autenticación: pendientes

| Hipótesis a investigar | Evidencia necesaria | Estado |
|---|---|---|
| WooCommerce REST API | Manual del proveedor, versión, configuración y mapeo real | No confirmado |
| Plugin/conector específico | Nombre, versión, autor, manual y configuración sin secretos | No confirmado |
| Servicio intermediario/nube | Operador, contrato de lectura, alcance y autorización | No confirmado |
| Exportación de archivos | Archivo anonimizado, esquema, codificación, delimitador, periodicidad e integridad | No confirmado |
| Acceso a base | Procedimiento soportado, copia de prueba y permiso explícito de lectura | No confirmado; no abrir Access ni escribir directamente |
| Aplicación local/protocolo propietario | Nombre/versiones, documentación, formato y requisitos de ejecución | No confirmado |

Flujo deseado: **Punto de Venta → web**, posiblemente a través de un intermediario aún desconocido. Flujo real actual, autenticación, permisos, frecuencia, formatos, IDs, límites, paginación, gestión de errores y conectividad siguen sin confirmar. No se asume API REST, acceso directo a Access ni que WooCommerce pueda quitarse.

## Arquitectura propuesta, sin implementar

Una vez confirmado el mecanismo: adaptador de lectura específico → normalización validada → previsualización/conflictos → confirmación Admin → servicio comercial transaccional → SyncRun y ActivityLogService. Es un diseño propuesto, no clases ni endpoints creados.

1. Obtener una muestra autorizada y anonimizada; conservar la semántica de números e identificadores.
2. Resolver y validar IDs, unidades, Lista 1, stock y relaciones antes de cualquier escritura.
3. Presentar nuevos, existentes, diferencias, conflictos y rechazados. No convertir respuestas incompletas en bajas ni ceros.
4. Confirmación explícita, respaldo y verificación de que la previsualización sigue vigente antes de aplicar.
5. Transacción por agregado producto/variantes con auditoría; acordar atomicidad global o lotes, exclusión de ejecuciones concurrentes y recuperación. No mantener transacciones SQLite durante llamadas de red.
6. Comparar valores normalizados: repetir el mismo conjunto no debe duplicar entidades, cambios ni logs. Actualizar external_synced_at sin confundir recepción repetida con cambio comercial.
7. Avanzar cursor solo tras persistir correctamente el tramo correspondiente, si el proveedor soporta incrementales; de lo contrario definir un recorrido completo seguro.
8. No programar Scheduler/Queue ni intervalos hasta confirmar capacidades y límites.

### Puntos de extensión y brechas conocidas

- `SyncRun`: ya tiene source, started_at, finished_at, status (running/completed/failed), processed_count, created_count, updated_count, error_count y error_details. No tiene contadores específicos de variantes/omitidos/conflictos, cursor, identificador de lote ni estado parcial. Evaluar ampliación complementaria solo después de definir las semánticas reales, sin una tabla paralela por defecto.
- `ActivityLogService::record()`: permite origin=external con actor=null sin heredar al usuario autenticado; exige transacción para éxitos y omite diferencias vacías. Admite los campos comerciales actuales mediante allowlist. No incluye external_id/external_synced_at ni vínculo con SyncRun: definir correlación segura y ampliar la lista deliberadamente si el contrato lo requiere. No usar indiscriminadamente `changed()` en el motor, porque su origen predeterminado es el contexto administrativo/sistema.
- Reutilizar Product/ProductVariant, casts y snapshots existentes; el transporte no se acopla a modelos. Las actualizaciones masivas Query Builder omiten casts y eventos: no son un atajo seguro para datos externos.
- Añadir permiso exclusivo Admin y panel «Integración comercial» cuando exista integración real. No exponer endpoints ni menús vacíos ahora.
- Errores externos deben almacenarse con códigos/resúmenes seguros, sin headers, tokens, URLs con credenciales o payloads completos. Las credenciales quedan fuera del frontend, repositorio y logs.

## Riesgos y decisiones pendientes

- IDs no únicos o reutilizados: fusiones/duplicados incorrectos; definir ámbito y resolver conflictos antes de importar.
- Precio de otra lista, impuestos o escala: precios públicos erróneos; contrastar contra Lista 1 del sistema.
- Stock por depósito/reserva/unidad distinto: disponibilidad engañosa; acordar significado y conversiones.
- Respuesta parcial, caída o límite de página: pérdida aparente de catálogo; no eliminar ni desactivar automáticamente.
- Campos mezclados comerciales/editoriales: sobrescritura; allowlist cerrada y pruebas de preservación.
- Concurrencia y cortes: SQLite requiere escrituras breves, ejecución controlada, transacciones y reanudación verificable.
- Dependencia real de WooCommerce o licencia de conector: no retirar ningún componente sin confirmar arquitectura y soporte.
- Fotos, textos o datos de clientes en exportaciones: pedir muestras mínimas anonimizadas, sin secretos.

Se mantiene el circuito pedido web → enlace WhatsApp → gestión comercial. El checkout actual no descuenta ni reserva stock y no envía pedidos al sistema externo. No se cambia en 18.A.

## Información mínima para habilitar 18.B

1. URL oficial o contacto técnico identificado de Soluciones Es y nombre/versión exactos del programa instalado.
2. Nombre y versión del conector con WooCommerce, manual o explicación técnica del proveedor; confirmar si WooCommerce es intermediario obligatorio.
3. Documentación del mecanismo soportado: flujo, autenticación (sin entregar secretos por chat), permisos, paginación/archivos, límites, incremental, errores y conectividad.
4. Muestra anonimizada: producto simple, producto con variantes, precio Lista 1, stock fraccionario, sin stock, dato ausente y baja comercial. Definir IDs, moneda, IVA, decimales, unidades y depósitos.
5. Entorno aislado autorizado, alcance de pruebas y respaldo. Acceso mínimo de lectura donde sea posible; ninguna escritura sobre Punto de Venta.

Texto sugerido para solicitar al proveedor (no enviado):

> Estamos preparando una integración de lectura de catálogo comercial hacia una web Laravel. Necesitamos identificar el mecanismo soportado por su versión de Punto de Venta y su conexión con WooCommerce: conector/protocolo, documentación, autenticación y permisos, campos de Lista 1/stock/unidades/variantes, identificadores, incrementales, límites y errores. ¿Pueden proporcionar una muestra anonimizada y un entorno de pruebas, y confirmar si WooCommerce es un intermediario obligatorio? No necesitamos escribir ventas ni modificar su base Access.

## Resultado y próximo paso

18.A entrega esta inspección, matriz de datos, propuesta y preguntas. **No existe evidencia suficiente para dar por confirmado el mecanismo externo.** 18.B no tiene adaptador, importaciones, dry-run ni sincronización real; no se probaron conexiones externas. Se mantuvo la suite de regresión (388/2130) sin agregar mocks ficticios.

Al recibir la documentación se podrá completar el relevamiento externo y comprobar las condiciones de acceso antes de desarrollar 18.B. No se inicia Fase 19 ni se publica en producción.
