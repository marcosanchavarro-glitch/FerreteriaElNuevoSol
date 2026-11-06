# 10. Carrito y checkout

## 10.1 Estado de sesión y cantidades

El carrito se almacena bajo `cart` en la sesión. No existe tabla de carritos. Cada clave es `productId-variantId`, usando cero cuando no hay variante; la línea contiene `product_id`, `product_variant_id` y `quantity`. No almacena un precio confiable del navegador. La sesión en base permite persistencia entre solicitudes mientras la cookie y el registro sigan vigentes; no es un carrito sincronizado entre dispositivos por cuenta.

`Cart::quantity()` admite texto o entero, coma o punto decimal, hasta cuatro dígitos enteros y tres decimales; exige valor positivo. Meter, Kilogram, Gram, Liter y Milliliter son fraccionables. Las demás unidades requieren enteros. El carrito admite 50 líneas distintas. Al acumular una cantidad existente vuelve a validar el resultado.

## 10.2 Resolución y cálculo

Cada línea consulta el producto con variantes e imágenes. Se verifica publicación, pertenencia/actividad de variante, unidad, modalidad, precio y stock. En modalidad calculable, si el stock es conocido, la cantidad no puede superarlo. Modalidad inquiry o precio NULL genera una línea por confirmar con subtotal NULL. `summary(false)` conserva líneas inválidas para que el usuario las pueda quitar; la confirmación usa el modo estricto.

El total suma solo subtotales conocidos y registra cuántas líneas quedan pendientes. El cliente no puede enviar un precio para reemplazar el obtenido por el servidor. La cantidad máxima y los límites monetarios previenen desbordamientos.

La implementación actual hace consultas por línea al resolver el carrito. No debe extrapolarse la ausencia de N+1 observada en Dashboard/Pedidos al carrito. Es una limitación a medir con carritos representativos, no una optimización aplicada en esta entrega.

## 10.3 Confirmación e idempotencia

`GET /checkout` requiere un carrito válido y crea un token aleatorio de 64 caracteres, más una huella SHA-256 del resumen. `POST /checkout` valida nombre, apellido, teléfono y email opcional, y exige ese token. El servicio deriva `checkout_key=sha256(token)`, con índice único en `orders`.

Dentro de una transacción crea el pedido con número `ENS-` y ULID, vuelve a resolver el carrito y compara la huella. Si cambió cualquier dato relevante del resumen, se revierte y se solicita nueva revisión. Guarda snapshots en líneas, totales, estado inicial e historial; agrega notificación interna para el usuario registrado. No descuenta stock ni llama a Soluciones Es.

La sesión mantiene hasta 20 confirmaciones bajo `checkout_completed`; al finalizar borra `cart` y `checkout`. Una repetición del token ya completado busca el pedido existente ligado al mismo usuario. Las rutas de carrito/checkout usan bloqueo de sesión; POST checkout especifica 30 segundos de duración y espera. Esta protección y la clave única evitan duplicados del mismo proceso, pero no constituyen reserva de inventario para compradores diferentes.

## 10.4 Confirmación y WhatsApp

`/pedido-confirmado/{number}` verifica que el número pertenezca a las confirmaciones de esa sesión y que el usuario coincida cuando el pedido sea de una cuenta. `WhatsAppOrder` genera texto desde los snapshots: líneas, variantes, cantidades, subtotales, total confirmado y retiro. Codifica con `rawurlencode` y forma `https://wa.me/{numero}?text=...` si el número es válido.

Con `DEMO_MODE=true`, `url()` devuelve NULL. El mensaje puede mostrarse para revisión, pero no se habilita envío. Fuera de demo, abrir el enlace tampoco prueba que el usuario haya enviado el mensaje; no hay webhook de entrega ni WhatsApp Business API.

## 10.5 Navegador y servidor

JavaScript controla botones de cantidad y cambia etiquetas/ID de variante. Blade renderiza la oferta inicial y formularios. El servidor realiza las comprobaciones decisivas y genera el pedido. Las funciones de interfaz no sustituyen la validación de `Cart` aunque el usuario manipule el DOM.

Diagrama: [proceso de compra](diagramas/04-compra.mmd). Fuentes: CartController, CheckoutController, Cart, Checkout, WhatsAppOrder y CartCheckoutTest.
