# 14. Sincronización comercial por SKU

## 14.1 Estado y contrato

Existe un motor manual para archivos normalizados de prueba, con `source=normalized_json_test`. Está implementado y validado en la candidata; no hay conector, webhook ni tarea periódica con Soluciones Es. El menú administrativo ofrece cargar JSON, previsualizar y confirmar, no iniciar una conexión real.

`CommercialSyncController` valida archivo `.json`, MIME JSON/texto, hasta 256 KB. `decode()` exige una lista de 1 a 100 registros, tamaño máximo 256 × 1024 bytes y profundidad JSON 32. Este ejemplo representa actualización de un SKU simple ya existente:

```json
[
  {"sku": "TEST-001", "kind": "product", "price_cents": 125050, "stock": "10.500"}
]
```

| Campo | Regla |
|---|---|
| sku | String; se quitan solo espacios ASCII exteriores; 1–255 caracteres según patrón alfanumérico y `._/-` |
| kind | `product` o `variant` |
| price_cents | Entero JSON no negativo, máximo 999999999999999; no float ni string |
| stock | String no negativo, hasta 12 enteros y 3 decimales, separador punto |
| name | Opcional para actualización; necesario para altas; 1–255 caracteres sin controles |
| sale_unit | Enum válido; obligatorio para alta de producto |
| parent_sku | Solo variantes; padre variable existente y no ambiguo |

Debe existir precio o stock; campos desconocidos invalidan la fila. Omitir un campo conserva su valor, enviarlo NULL no es equivalente y no se admite. Los ceros iniciales y las mayúsculas del SKU se conservan; `001` no se convierte en `1`. No se usa external_id como criterio alternativo de coincidencia.

## 14.2 Fuente de verdad y conflictos

Para registros existentes, el archivo es fuente de precio/stock suministrados y el sitio conserva nombres, descripciones, slug, imágenes, categorías, publicación y destacados. Un padre variable no recibe precio/stock propios. Si se indica unidad debe coincidir con la efectiva; el motor no convierte metros a rollos u otras unidades.

El plan indexa productos y variantes por SKU normalizado. Marca conflicto si aparece repetido en el archivo o en el catálogo, si el tipo no coincide, si el padre es ambiguo/incorrecto o si el alta carece de información necesaria. Un padre variable nuevo no se crea automáticamente para una variante en el mismo archivo. Las altas de producto son simples, borrador, no destacadas y modalidad inquiry; las variantes nuevas quedan inactivas y también inquiry. Requieren revisión editorial posterior.

## 14.3 Previsualización

`preview()` exige `manage-commercial-sync`, sanea entradas inválidas y calcula filas con acciones `invalid`, `conflict`, `create`, `update` o `unchanged`. Escribe un SyncRun con estado completed y `error_details.mode=preview`: completed aquí significa plan calculado, **no cambios comerciales aplicados**.

El JSON de detalles conserva actor, vencimiento a 30 minutos, registros normalizados, filas y fingerprint. Cada fila incluye hash de atributos del objetivo y del padre cuando corresponde. No escribe productos durante preview, pero sí guarda el registro de revisión en la base.

## 14.4 Aplicación y concurrencia

`POST /admin/commercial-sync/{syncRun}/apply` exige `confirm` aceptado. El servicio adquiere `Cache::lock('commercial-sync', 120)` y espera hasta cinco segundos. En una transacción bloquea el SyncRun y valida origen, modo, propietario, vencimiento y ausencia de aplicación anterior. Recalcula el plan con `lockForUpdate()` y compara huellas; si cambiaron los datos se exige otra vista previa.

La implementación actual lee y bloquea todas las filas de productos y variantes al construir ese plan. Esto es un límite conocido de contención, no un bloqueo selectivo exclusivamente de los SKU importados. El bloqueo de caché tiene duración finita y no constituye por sí solo una garantía ilimitada para procesos de larga duración.

Se aplican acciones create/update, se marca external_synced_at, se registra auditoría por entidad y resumen y se enlaza `applied_run_id` en la vista previa. **Las filas invalid/conflict se omiten y se contabilizan; no hacen fallar automáticamente todo el archivo.** Las escrituras efectivamente seleccionadas son transaccionales: una excepción durante ellas revierte ese conjunto completo.

## 14.5 Idempotencia y errores

Una misma vista previa no se aplica dos veces. Una nueva importación del mismo contenido puede dar unchanged si valores y marca externa ya coinciden; no es una clave idempotente global enviada por Soluciones Es. En un primer procesamiento de valores iguales puede existir update para marcar gestión externa.

Una validación vencida/desactualizada se informa como error sin aplicar. Un timeout del bloqueo de caché informa importación en curso. Un error inesperado dentro de la operación transaccional revierte cambios e intenta registrar un SyncRun failed fuera de la transacción; si la base está indisponible tampoco puede garantizarse ese registro. No se implementó un botón de deshacer una importación confirmada: rollback por error y restauración posterior son conceptos distintos.

La prueba PostgreSQL usa dos conexiones y confirma invisibilidad de cambios sin commit, SQLSTATE 55P03 ante bloqueo NOWAIT y disponibilidad de los nuevos valores al confirmar. No simula una integración comercial real ni una prueba de carga.

Diagrama: [sincronización por SKU](diagramas/05-sincronizacion.mmd). Fuentes: CommercialSyncService, CommercialSyncController, `admin/sync.blade.php`, CommercialSyncTest, CommercialSyncPostgresTest y `docs/integracion-punto-de-venta.md`.
