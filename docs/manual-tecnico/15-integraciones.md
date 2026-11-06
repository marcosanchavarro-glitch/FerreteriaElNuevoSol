# 15. Integraciones externas

## 15.1 Servicios existentes y límites

| Servicio | Comunicación | Estado documentado |
|---|---|---|
| GitHub | Repositorio y ejecución de Actions | Versionado/CI utilizados; evidencia de validación enlazada |
| Render | Construcción Docker desde rama configurada; HTTP público | Demo publicada históricamente; candidata sin publicación confirmada |
| Neon | PDO PostgreSQL con TLS desde Laravel | Persistencia remota histórica; no usado para pruebas de esta redacción |
| Cloudinary | HTTP firmado desde backend | Adaptador implementado y prueba histórica de persistencia |
| WhatsApp | Enlace wa.me abierto por usuario | Implementado fuera de demo; sin envío automático ni confirmación de entrega |
| Soluciones Es | Ningún transporte implementado | Pendiente de documentación y conexión real |
| SMTP | Transporte configurado array | Recuperación preparada; entrega real pendiente |

## 15.2 Flujo multimedia real

El navegador sube un archivo a Laravel; la solicitud valida permisos y formato. ImageStorage normaliza a WebP y produce una ruta `products/{id}/{uuid}.webp` u otra familia permitida. Con CATALOG_STORAGE=cloudinary, CloudinaryImages deriva public_id añadiendo CLOUDINARY_PREFIX y quitando `.webp`. La carga usa `type=authenticated`, no una URL pública por defecto.

La lectura pública solicita una ruta de Laravel; este comprueba visibilidad y descarga los bytes con petición firmada de corta vigencia para retransmitirlos. La clave secreta no se entrega al cliente. El adaptador configura cinco segundos de conexión y 25 de timeout, sin seguir redirecciones. Los errores se traducen a mensajes controlados; un 404 de descarga se trata como ausencia. Este diseño protege acceso, pero suma tráfico y latencia al backend; no se debe describir como entrega directa por CDN al navegador.

## 15.3 Qué falta para Soluciones Es

Debe obtenerse versión y documentación del proveedor, medio de extracción, autenticación, límites, lista de precios, semántica de stock y manejo de bajas/variantes. El contrato JSON actual es interno y de prueba: no prueba que el proveedor lo produzca. También faltan política de frecuencia, reconciliación y mecanismo de ejecución si se requiere automatizar. Ninguno de esos elementos se implementa ni habilita con este manual.

La futura adaptación deberá mantener separados transporte y aplicación comercial, reutilizando validaciones y transacciones donde corresponda. No debe enviar pedidos o modificar caja solo porque exista un SKU coincidente. Las responsabilidades y permisos reales deben acordarse antes de escribir ese conector.

## 15.4 Ronda administrativa local

El mensaje WhatsApp incorpora nombre y teléfono del contacto congelado, precio unitario de líneas calculables y marca explícita de líneas sujetas a consulta, además de número, variantes, cantidades, subtotal y total conocidos. Indica que la solicitud está pendiente de atención y confirmación. El número único configurable existente se conserva, con vista previa administrativa del destino propuesto y su validación internacional. No se incorporaron múltiples destinos.

Abrir el enlace no modifica el estado, no acredita entrega y no factura. El panel explica la correspondencia operativa de los estados existentes: recibido es solicitud pendiente; preparación es gestión; listo para retirar exige acción explícita del personal. No se crearon estados nuevos ni integración externa. DEMO_MODE sigue anulando la URL de WhatsApp.
