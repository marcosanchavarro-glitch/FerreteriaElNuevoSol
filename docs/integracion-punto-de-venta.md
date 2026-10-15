# Sincronización comercial por SKU — Fase 18

Motor interno implementado; conexión real con **Soluciones Es pendiente**. Solo está confirmado el uso del SKU como correspondencia. No se modificó Render, Access ni WooCommerce.

## Uso

Admin → **Sincronización comercial** (`/admin/commercial-sync`). Subir JSON → **Previsualizar sin modificar productos** → revisar filas → marcar confirmación → **Confirmar importación**. Seller y Customer no tienen acceso; el servicio también valida autorización.

La vista previa guarda un `SyncRun`, nunca modifica productos ni variantes. Vence a los 30 minutos, pertenece al Admin que la generó y se confirma una sola vez. Se compara nuevamente el catálogo antes de aplicar: si cambió, hay que generar otra vista previa. Las importaciones se serializan mediante el bloqueo de caché existente y usan transacción y bloqueo de filas. Un fallo durante la aplicación revierte todos los cambios comerciales y su auditoría.

## SKU

Texto ASCII de 1 a 255 caracteres: letras, números, punto, guion, barra y guion bajo; debe comenzar con letra o número. Solo se recortan espacios ASCII exteriores. Se conservan ceros iniciales, mayúsculas y minúsculas; `abc` y `ABC` son distintos. No se reescriben SKU existentes ni se buscan nombres similares.

Se busca en productos y variantes conjuntamente. Si hay varios candidatos, incluso tras recortar espacios, se registra conflicto. Todos los duplicados del archivo se omiten, aunque uno tenga otros campos inválidos. Los IDs internos y `external_id` no se sustituyen por el SKU.

## Contrato JSON de prueba

Lista de 1–100 registros, máximo 256 KB. Solo archivo `.json`, sin dependencias ni segundo importador.

```json
[
  {"sku":"00123","kind":"product","price_cents":10001,"stock":"1.125"},
  {"sku":"NUEVO-01","kind":"product","name":"Artículo ficticio","sale_unit":"unit","price_cents":250050},
  {"sku":"VAR-01","kind":"variant","stock":"2.500"}
]
```

- `sku` y `kind` (`product` o `variant`) obligatorios. Debe venir al menos `price_cents` o `stock`.
- `price_cents`: entero JSON no negativo, hasta 999999999999999, **ya normalizado** como precio público Lista 1 en centavos de la moneda de la tienda. Rechaza floats, strings y null. No convierte moneda ni IVA.
- `stock`: string decimal no negativo, punto decimal, hasta 12 enteros y 3 decimales. Rechaza números JSON, coma, negativos, null y exceso de precisión. Se conserva exactamente mediante el cast existente.
- Campo comercial ausente: se conserva. Cero es válido. No se interpretan vacíos como cero.
- `name`: obligatorio solo para altas; en existentes no reemplaza el nombre web.
- `sale_unit`: obligatoria para nuevos productos; valores del enum existente (`unit`, `meter`, `kilogram`, `gram`, `liter`, `milliliter`, `box`, `package`, `bundle`, `roll`, `other`). En existentes, si se informa debe coincidir. Nunca se convierte la unidad.
- Nuevas variantes: `name` y `parent_sku` de un único producto variable **ya existente**. Sin unidad explícita heredan la del padre. No se cambia de padre una variante existente. No se crean padres variables a partir de suposiciones.
- Campos desconocidos invalidan la fila y no se almacenan en el historial.

Archivo ficticio: [sync-demo.json](sync-demo.json). Para probar todas sus ramas, usar un catálogo de prueba con producto `00123` y variante `VAR-001`; las pruebas automatizadas crean esos registros con factories. No modifica automáticamente las bases locales ni la demo remota. Sin esos registros, la vista previa muestra los conflictos correspondientes.

## Cambios y protección

Solo se actualizan precio y stock suministrados y se marca `external_synced_at` para activar las restricciones comerciales existentes. Primera adopción con valores iguales también se registra como actualización; repeticiones posteriores no cambian valores ni timestamps.

Altas de productos: simples, borrador, no destacados y compra a confirmar. Altas de variantes: inactivas y compra a confirmar. No se publica automáticamente ni se infiere modalidad comercial.

Se conservan descripciones, nombres existentes, imágenes, categorías, publicación, destacados, unidades existentes, SEO, banners, CMS y configuración. No se eliminan registros ni se modifican snapshots de pedidos anteriores.

## Conflictos e historial

Cada fila muestra alta, actualización, sin cambios, inválido o conflicto. Conflictos e inválidos se omiten; la confirmación aplica únicamente las filas válidas mostradas. Un error operativo revierte la transacción completa.

Se reutiliza `SyncRun`: origen fijo `normalized_json_test`, fecha, resultado y contadores. `error_details` conserva modo, filas, conflictos, cantidad omitida y referencia a la vista previa; no incluye archivos originales, credenciales ni mensajes crudos del proveedor. Se reutiliza `ActivityLogService` para cambios por producto/variante y resumen de sincronización con el Admin responsable. No se creó otro sistema de auditoría ni migraciones.

## Adaptador real pendiente

Solicitar al proveedor: mecanismo documentado de entrega (sin asumir API REST), muestra anonimizada, unicidad y alcance del SKU, cambios de SKU, relación padre/variante, campo de Lista 1, moneda, IVA, escala y formato decimal, unidad de venta, significado del stock y depósito, negativos, ausencias y bajas, frecuencia, autenticación y entorno autorizado de pruebas. Hasta confirmarlo, no hay conexión real, sincronización automática ni envío de pedidos.
