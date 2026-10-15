# Fases 10 y 11 — Carrito, checkout y WhatsApp

Implementadas y verificadas. No se inició Fase 12.

## Fase 10

El carrito vive en la sesión de Laravel, para invitados y usuarios autenticados. No usa localStorage ni tablas nuevas de carritos. Cada línea guarda únicamente product_id, product_variant_id nullable y quantity normalizada a tres decimales. La clave combina producto y variante: repetirla acumula cantidad; variantes diferentes generan líneas independientes. El contador del header cuenta líneas distintas.

Rutas: GET/POST /carrito; PATCH/DELETE /carrito/{clave}. La ficha permite agregar la presentación seleccionada; el carrito muestra imagen, nombre, variante, unidad, precio, cantidad, subtotal, actualización y eliminación. La navegación de categorías conserva el criterio público de fases anteriores.

Se aceptan punto o coma decimal, hasta tres decimales, cantidades mayores que cero y hasta 9.999,999; máximo 50 líneas. Unidad, caja, paquete, bulto, rollo y otra requieren enteros. Metro, kilogramo, gramo, litro y mililitro admiten fracciones; los controles avanzan 1 o 0,001 respectivamente. Con JavaScript deshabilitado, la selección de variante se confirma con “Ver presentación” y los formularios siguen funcionando.

Todos los importes se reconstruyen desde Product o ProductVariant. Los valores de precio, nombre, SKU o subtotal enviados por el navegador no son autoridad. Se utilizan centavos y cantidades escaladas enteras; se redondea cada subtotal al centavo, mitad hacia arriba. Se impiden desbordamientos y se respeta el límite de MinorUnits: 999999999999999 centavos por subtotal y total.

Inquiry permite solicitar confirmación incluso con stock cero/desconocido; su precio y subtotal son null aunque el catálogo contenga un precio interno. No se suman importes inventados. Un calculable sin precio también queda a confirmar, conservando su validación de stock. Un calculable con stock null admite el pedido sujeto a coordinación de disponibilidad. Con stock conocido se exige cantidad suficiente al agregar, actualizar y confirmar. Productos ocultos, eliminados o variantes inválidas/inactivas no se pueden pedir.

Las líneas que dejan de ser válidas permanecen corregibles/eliminables en el carrito. El resumen distingue total confirmado y número de productos a confirmar; bloquea continuar si hay líneas inválidas.

## Fase 11

GET /checkout presenta resumen actual y datos del cliente. Nombre, apellido y teléfono son obligatorios; email opcional. Para autenticados se precargan los datos. Las correcciones son un snapshot del pedido y no actualizan el perfil. La única modalidad es retiro en local.

POST /checkout valida contacto, sesión, revisión, catálogo y disponibilidad. Dentro de una transacción SQLite crea Order, todos los OrderItems y el historial inicial. La escritura inicial del pedido obtiene el bloqueo de escritura antes de releer el catálogo. Cualquier fallo revierte el conjunto.

Order conserva contacto, user_id real o null, status received, fulfillment pickup, subtotal, confirmed_total y has_items_to_confirm. Ambos totales contienen la suma de subtotales confirmados. Los OrderItems conservan IDs y snapshots de nombre, variante, SKU, unidad, cantidad, precio, subtotal y requires_confirmation. OrderStatusHistory comienza con previous_status null, new_status received y changed_by del usuario autenticado o null.

Número: prefijo ENS- seguido de ULID de 26 caracteres, con índice único existente. No expone el ID correlativo interno. La nueva columna nullable orders.checkout_key tiene un índice único para idempotencia; es compatible con pedidos anteriores. La migración también puede revertirse en SQLite.

Cada revisión genera un token aleatorio de 64 caracteres ligado a la sesión y una huella del resumen validado en servidor. Si cambian precios u otros datos del resumen, se exige revisar nuevamente. Una revisión nueva invalida formularios anteriores. El token se almacena como SHA-256 en el pedido. Las rutas de carrito/checkout utilizan bloqueo de sesión, se aplica POST/Redirect/GET y los reenvíos completados vuelven al mismo pedido. También se recupera un pedido ya confirmado en DB si falló guardar el resultado de sesión. El carrito solo se vacía después del commit.

La sesión conserva las últimas 20 confirmaciones. /pedido-confirmado/{numero} exige esa sesión; además verifica el propietario de pedidos registrados. No es un historial público ni un enlace de acceso por token. Al cerrar sesión/expirar la sesión se pierde este acceso temporal. Un invitado no se convierte retroactivamente en propietario al iniciar sesión.

**No se descuenta ni reserva stock.** Se comprueba disponibilidad, pero la confirmación final queda para el local. La futura integración Punto de Venta será la fuente autoritativa; Fase 12 debe mantener esta decisión salvo cambio explícito.

## WhatsApp y configuración

El mensaje usa exclusivamente snapshots del pedido guardado: número, cantidades/unidades, productos/variantes, subtotales, total confirmado, número de líneas a confirmar y retiro. Se genera una URL https://wa.me con destino validado y texto codificado con rawurlencode. No se realizan peticiones externas desde el backend ni apertura automática.

La pantalla de confirmación presenta un botón manual. Si falta el número o es inválido, muestra un aviso claro y conserva el pedido. Se dejó sin número real configurado.

Configuración central en backend/config/shop.php y backend/.env.example:

- SHOP_WHATSAPP_NUMBER: 8 a 15 dígitos, código internacional incluido, sin +, espacios ni guiones.
- SHOP_PICKUP_ADDRESS: dirección del local, opcional.
- SHOP_PICKUP_HOURS: horarios, opcional.

El correo real sigue deshabilitado. No se agregaron dependencias.

## Pruebas

Antes de cambios: 250 tests / 1279 assertions, todos aprobados.
Resultado final: **283 tests / 1447 assertions, todos aprobados**.
Laravel Pint aprobado.

CartCheckoutTest incorpora 33 casos, incluyendo datasets: simples, variantes, IDs falsos, visibilidad, stock, cantidades inválidas, decimales, duplicados, actualización, eliminación, límites monetarios y de líneas, inquiry, invitados/registrados, snapshots, historial inicial, transacción revertida al fallar un item, contacto inválido, precio manipulado/cambiado, stock cambiado, variante desactivada, propietario ajeno, tokens obsoletos, reenvíos y recuperación después de commit. WhatsApp se prueba sin conexiones externas. La suite previa también verifica ciclo de migraciones, permisos, autenticación y catálogo.

Comandos desde backend:

~~~powershell
& ../.tools/php/php.exe artisan migrate --no-interaction
& ../.tools/php/php.exe artisan test --compact
& ../.tools/php/php.exe vendor/bin/pint --dirty --format agent
~~~

## Verificación manual

En la base aislada .tools/storefront-preview.sqlite, con datos ficticios:

- Invitado: ficha → carrito → checkout → pedido mixto confirmado.
- Pintura: selector 4 L → carrito → snapshot 4 L.
- Cable: 5,5 metros → controles +/- → actualizar → subtotal $13.752,81.
- Inquiry: corte a medida → precio a confirmar → total mixto $40.752,81 + una línea a confirmar.
- Registrado: login → taladro → datos precargados → edición de nombre solo para el pedido → confirmación; el perfil conservó su nombre.
- Stock: se cambió taladro a cero después de abrir checkout; el pedido se bloqueó y el carrito permaneció. Se restauró el stock ficticio y pudo completarse.
- WhatsApp sin configurar: aviso y botón deshabilitado. Con configuración temporal ficticia: botón, destino y codificación comprobados en navegador; no se abrió ni envió mensaje. El número ficticio fue retirado.
- Escritorio y móvil de 390 px: carrito, cantidad decimal, resumen antes del formulario, contacto, confirmación y botón WhatsApp revisados.

CheckoutPreviewSeeder prepara la variante 4 L con stock ficticio y un cliente de pruebas, solo en local/testing y en la base aislada anterior. No forma parte del seed normal. Usuario: cliente.checkout@example.test; contraseña SOLO DESARROLLO: SoloDesarrollo!2026. No ejecutar este seeder contra datos reales.

## Archivos principales

- backend/app/Services/Storefront/Cart.php: cantidades, validación, precios, sesión y resumen.
- backend/app/Services/Storefront/Checkout.php: revisión, transacción, snapshots e idempotencia.
- backend/app/Services/Storefront/WhatsAppOrder.php: mensaje y URL.
- backend/app/Http/Controllers/Storefront/CartController.php y CheckoutController.php.
- backend/config/shop.php y .env.example.
- backend/database/migrations/2026_09_25_015934_add_checkout_key_to_orders_table.php.
- backend/resources/views/storefront/{cart,checkout,confirmation,add-cart,pickup}.blade.php.
- Layout/ficha públicos, ProductPresentation, ProductDisplay, AppServiceProvider, routes/web.php y assets storefront.css/storefront.js.
- backend/tests/Feature/CartCheckoutTest.php y database/seeders/CheckoutPreviewSeeder.php.

## Próximas fases

Fase 12: administración completa de pedidos, respetando snapshots, historial, importes parciales y stock no descontado.
Fase 13: historial del cliente con autorización por propietario; no reutilizar la confirmación temporal como acceso público.
Pendientes del negocio: número real de WhatsApp, dirección y horarios.
Fuera de alcance: pagos, delivery, notificaciones, CMS/banners, integración Punto de Venta, WooCommerce, descuentos y cupones.
