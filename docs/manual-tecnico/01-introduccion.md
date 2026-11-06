# 1. Introducción

## 1.1 Presentación y problema

Ferretería El Nuevo Sol es una aplicación web para presentar un catálogo, recibir solicitudes de productos con retiro en local y administrar su preparación. Atiende la diferencia entre artículos con precio calculable y artículos cuyo importe requiere confirmación, así como unidades fraccionables y presentaciones comerciales. El sistema conserva el contenido editorial de la tienda separado de los datos comerciales susceptibles de importación.

El problema abordado es la dispersión entre la consulta del catálogo, el armado de una solicitud y el seguimiento interno. La solución registra un pedido persistente con copia de sus condiciones, aun cuando luego cambien los productos. No reemplaza la caja, la facturación ni la contabilidad del establecimiento.

## 1.2 Objetivos

El objetivo general es ofrecer un recorrido web coherente desde la búsqueda hasta el seguimiento del pedido, con controles de acceso y trazabilidad. Sus objetivos específicos son administrar productos simples y variables; publicar categorías e imágenes; calcular cantidades sin errores de coma flotante; conservar pedidos e historial; personalizar el inicio; y preparar una importación comercial identificada por SKU sin sobrescribir contenido editorial.

## 1.3 Alcance y usuarios

El visitante puede navegar, filtrar productos y utilizar el carrito y checkout sin cuenta. Customer dispone de perfil, pedidos propios y notificaciones internas. Seller accede al panel y a operaciones permitidas por políticas; Admin añade edición comercial local, configuración, auditoría e importación. No existe un formulario público para registrarse como Admin.

La entrega incluye catálogo, autenticación, productos, variantes, categorías, imágenes, carrito de sesión, checkout, pedidos, notificaciones internas, CMS, auditoría y motor manual de importación. Las mejoras de Dashboard/Pedidos y el motor manual son parte de la candidata pendiente de publicación según el último estado confirmado.

## 1.4 Limitaciones

Solo existe retiro en local (`pickup`). No hay pasarela de pagos, cálculo de envíos, facturación, descuento o reserva automática de stock ni envío de pedidos al punto de venta. WhatsApp se utiliza mediante un enlace que debe abrir y enviar el usuario; en modo demo se suprime ese enlace. El correo real no está habilitado en la configuración entregada. La integración con Soluciones Es requiere documentación y transporte todavía no implementados. Los respaldos recuperables no están acreditados y condicionan el despliegue autorizado.

Fuentes principales: `backend/routes/web.php`, `backend/app/Enums/`, `backend/app/Services/Storefront/Checkout.php`, `backend/app/Services/Storefront/WhatsAppOrder.php` y `docs/pendientes-correcciones.md`.
