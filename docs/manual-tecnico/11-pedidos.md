# 11. Gestión de pedidos

## 11.1 Estructura y snapshots

`orders` guarda número único, contacto copiado, usuario opcional, estado, modalidad de retiro y totales en centavos. `order_items` mantiene nombre del producto/variante, SKU, unidad, cantidad e importe al confirmar. Cambiar un nombre o un precio del catálogo no recalcula pedidos históricos. Las líneas pendientes de cotización permanecen marcadas; el panel implementado cambia estados, no una herramienta completa de cotización posterior o cobro.

`order_status_history` registra estado anterior/nuevo, responsable opcional y fecha. Para un pedido de invitado la creación puede no tener responsable usuario. La relación `statusHistory.changedBy` se carga al presentar el detalle administrativo para no consultar cada actor por separado.

## 11.2 Máquina de estados

El flujo permitido es `received → preparing → ready_for_pickup → picked_up`. Desde los tres primeros estados se admite `cancelled`. Retirado y cancelado son terminales. No se permite volver a recibido ni cancelar un pedido retirado mediante las operaciones actuales.

La solicitud de cambio incluye `expected_status`: el servicio compara el estado que vio el operador con el actual. Antes de leerlo realiza una escritura sin cambio de valor en `updated_at` para adquirir bloqueo de escritura; refresca también al actor y reautoriza. Si el pedido cambió mientras se mostraba, devuelve error de validación. La transacción reúne estado, historial, notificación y auditoría, con reintentos de transacción configurados.

## 11.3 Listado y permisos

`GET /admin/orders` permite buscar número y datos de contacto, filtrar estado, fechas y tipo de cliente. Las fechas se interpretan en `config('shop.timezone')`, luego se comparan en UTC con intervalo desde el inicio del día hasta el inicio del día posterior. Los resultados se ordenan por ID descendente y se paginan de a 20. Los conteos por estado representan todos los pedidos, no solo el subconjunto filtrado.

Admin y Seller pueden listar/ver/cambiar estados con `OrderPolicy`. Customer consulta `GET /mi-cuenta/pedidos`, paginado de a 10, y `GET /mi-cuenta/pedidos/{order}` mediante su propia relación. No se atribuye propiedad de un pedido por un ID enviado libremente. La cuenta no incorpora automáticamente pedidos de invitado con el mismo email.

## 11.4 Avisos internos y sistema externo

`OrderNotifications::record()` crea una notificación por pedido/estado con `firstOrCreate` y la vincula al usuario mediante `syncWithoutDetaching`. El índice único apoya la ausencia de duplicados. Invitados no reciben este aviso interno por carecer de cuenta. La lectura se registra en el pivot y no modifica el aviso de otros destinatarios.

No hay transmisión automática a caja ni reconciliación de stock o facturas. El pedido web es una solicitud registrada para gestión local. La sincronización por SKU no modifica sus snapshots. Fuentes: Order, OrderItem, OrderStatusHistory, OrderStatus, OrderStatusService, OrderNotifications y controladores Account/Admin de pedidos.
