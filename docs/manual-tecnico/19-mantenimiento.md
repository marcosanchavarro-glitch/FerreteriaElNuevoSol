# 19. Mantenimiento y resolución de problemas

## 19.1 Diagnóstico por capas

Comenzar por versión ejecutada, URL, hora, rol y operación que falló. Consultar HTTP y logs antes de modificar configuración. Localmente se usa LOG_CHANNEL=stack/single y `storage/logs`; Render configura stderr/warning. El historial de actividad registra operaciones seleccionadas, no todos los errores de PHP ni cada visita. No incluir contraseñas, tokens, URLs de conexión o cuerpos con datos de clientes al compartir incidentes.

| Síntoma | Comprobación focalizada | Acción posterior razonable |
|---|---|---|
| 500 al arrancar | Validación de entorno, dependencias, APP_KEY, conexión y migraciones | Corregir causa identificada; no reinicializar base |
| 419 o sesión perdida | Host/cookie, HTTPS, APP_KEY estable, sesión DB, token del formulario | Mantener configuración coherente; no quitar CSRF |
| Login rechazado | Cuenta activa, rol, contraseña y límites de intentos | Recuperación autorizada; no crear un Admin por registro público |
| 403 | Gate, política y campos prohibidos | Revisar permisos y origen externo; no habilitar todo el panel |
| 404 en detalle/imagen | Publicación, variante activa, binding del padre, ruta válida | Revisar visibilidad antes de asumir archivo perdido |
| Imagen placeholder | Existencia local/remota, ruta UUID, prefijo y errores de Cloudinary | Recuperar archivo correcto; no publicar assets privados |
| Checkout solicita revisar | Cambio de resumen, token o cantidades | Volver al resumen actualizado; no ignorar huella |
| Importación rechazada | Propietario, vencimiento, fingerprint, SKU duplicado, bloqueo | Crear nueva preview; no aplicar datos obsoletos |
| Panel lento | Servicio caliente, tiempos HTTP/SQL, número de consultas | Medir antes de optimizar o cambiar infraestructura |

## 19.2 Comandos de consulta y precauciones

En una copia local correctamente configurada, `php artisan route:list --except-vendor` ayuda a verificar rutas y `php artisan migrate:status` consulta migraciones de la base seleccionada. `composer show --direct` muestra dependencias instaladas; `git status --short` y `git diff` identifican cambios. No imprimir `config:show database` ni todo el entorno en un canal público.

`config:clear` y `config:cache` cambian archivos de ejecución y deben formar parte de una operación controlada, no de un diagnóstico supuestamente de solo lectura. No usar migrate:fresh, db:wipe o semillas de preview para corregir una demo persistente. Tampoco borrar todas las imágenes de un prefijo para resolver una referencia rota.

## 19.3 Cambios y pruebas

Antes de modificar una regla, seguir ruta → request/policy → controlador/servicio → modelo/cast → vista y localizar su prueba. Hacer cambios pequeños con un caso de regresión cuando cambie comportamiento. Ejecutar primero el grupo afectado. Para PHP modificado, usar Pint según las reglas del repositorio. Actualizar el manual si cambian contrato JSON, estados, permisos o esquema.

Actualizar dependencias en una rama aislada, revisar lock y notas oficiales, ejecutar pruebas relevantes y validar el contenedor antes de publicar. No ejecutar composer update en el servidor. La versión PHP de las imágenes no está fijada a digest; reproducir exactamente un build futuro puede requerir registrar digest y lock durante la publicación.

## 19.4 Rendimiento y deuda conocida

No hay caché de precios/stock/pedidos agregado por la optimización. La navegación seguirá recargando Blade. La lectura Cloudinary a través del backend añade trabajo por imagen. El carrito resuelve líneas individualmente y el motor de importación bloquea todo el catálogo. Son puntos a medir cuando el volumen lo justifique; este manual no modifica ninguno. La comparación remota después de publicar sigue pendiente.
