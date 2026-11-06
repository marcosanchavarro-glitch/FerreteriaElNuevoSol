# 6. Base de datos

## 6.1 Esquema y portabilidad

Diez archivos de migración definen y amplían el esquema de aplicación. Las tablas de negocio abarcan usuarios, catálogo, pedidos, contenido, notificaciones, importación y actividad. Las tablas de sesión, caché y cola pertenecen a infraestructura de Laravel. `migrations` es mantenida por el migrador, no por una clase de modelo propia.

El [diccionario de datos](anexos/diccionario-datos.md) enumera todos los campos declarados en las migraciones, su tipo Schema Builder, nulabilidad, valores predeterminados, índices y claves. El tipo se documenta al nivel de la migración, porque la representación física varía entre SQLite y PostgreSQL. No se inspeccionó el esquema de la base remota para afirmar que está actualizado.

## 6.2 Relaciones e integridad

Un producto tiene variantes e imágenes y pertenece a varias categorías mediante `category_product`. Una categoría puede tener padre e hijos. El padre admite NULL y su eliminación deja hijos sin padre. Los ciclos de categorías se rechazan en código; no hay una restricción recursiva equivalente en SQL. El enlace users–sessions del diagrama representa una relación lógica: sessions.user_id está indexado, pero no declara una FK en la migración.

Un pedido pertenece opcionalmente a un usuario; las líneas y cambios de estado pertenecen al pedido. Las FK de `order_items.order_id` y `order_status_history.order_id` restringen borrado. En cambio, los vínculos de una línea con producto y variante admiten NULL tras eliminar esos registros: el nombre, SKU, unidad, cantidad e importes copiados permiten seguir interpretando el pedido. No existen rutas administrativas para borrar pedidos ni productos en la candidata.

Las notificaciones se vinculan a destinatarios mediante `notification_user`, con `read_at` por usuario. Pueden referenciar un pedido; la combinación `order_id,type` es única. `activity_logs.user_id` conserva una FK opcional, mientras `actor_id`, nombre y rol son una copia histórica deliberadamente independiente. `entity_id` no es FK polimórfica SQL: es un identificador descriptivo del evento.

`sync_runs` no tiene una columna FK de propietario. El motor guarda `actor_id`, modo, registros y huella en `error_details`, junto con el enlace a la ejecución aplicada. No deben inventarse tablas de vistas previas o integraciones para explicar ese diseño.

## 6.3 Importes y cantidades

`MinorUnits` acepta enteros no negativos de hasta 15 dígitos: `125050` significa 1.250,50 unidades monetarias. Rechaza floats. `ScaledQuantity` guarda milésimas en BIGINT: `10500` representa `10.500` unidades y retorna una cadena decimal en PHP. Este cast admite negativos, pero los formularios comerciales y la importación restringen su dominio; no confundir capacidad del cast con entrada admitida por cada flujo.

El carrito calcula el subtotal con redondeo de media unidad hacia arriba para valores no negativos:

```php
// backend/app/Services/Storefront/Cart.php, resolve()
$subtotal = intdiv($price * $quantity + 500, 1000);
```

`$price` está en centavos y `$quantity` en milésimas. Por ejemplo, 10000 × 1500 / 1000 produce 15000 centavos. Antes de multiplicar se controla desbordamiento; también hay límite por línea y total. Las líneas pendientes de cotización usan importes NULL, no un precio falso de cero.

## 6.4 Índices y garantías

Los slugs de producto/categoría, email, número de pedido y `checkout_key` son únicos. SKU y external_id tienen índices de búsqueda, **no unicidad global SQL**. El motor debe detectar ambigüedad entre productos y variantes. La validación de SKU de variantes dentro del padre es una regla de solicitud, no un índice único de la migración.

Hay índices compuestos para productos por estado/destacado, variantes por producto/actividad, pedidos por usuario/fecha y estado/fecha, orden de categorías/imágenes, contenido activo/orden y actividad por actor, módulo, acción y fecha. Los enums PHP se almacenan como strings; no hay CHECK de estados definido en las migraciones revisadas. Los casts y eventos no se ejecutan al escribir con Query Builder directamente.

## 6.5 Transacciones y trazabilidad

Checkout escribe pedido, líneas, primer historial y notificación en una transacción. `OrderStatusService` agrupa cambio de estado, historial, notificación y auditoría. Producto/CMS/galerías registran sus cambios autorizados y auditoría dentro de transacciones. La importación bloquea vista previa y filas antes de escribir. El éxito de auditoría requiere una transacción activa; un fallo hace revertir la escritura asociada.

La inmutabilidad de `ActivityLog` se aplica mediante eventos Eloquent de actualización/borrado. No es un registro protegido contra administradores de base ni una firma criptográfica. El respaldo compensatorio en memoria de imágenes tampoco es una transacción distribuida ni una copia persistente de recuperación.

Diagrama: [entidad-relación](diagramas/02-entidades.mmd). Fuentes: `backend/database/migrations/`, `backend/app/Models/`, `backend/app/Casts/` y servicios transaccionales referidos.
