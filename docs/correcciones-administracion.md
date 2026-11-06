# Ronda de correcciones administrativas — registro de avances

Fecha: 6 de noviembre de 2026. Rama local: `codex/admin-corrections`, creada desde `af85ee7` para conservar las fases 18 y 19. No se hizo push, merge ni despliegue.

## Primer punto de control histórico (superado por el avance siguiente)

Se compactó el encabezado administrativo a una altura mínima de 74 px en escritorio, con logotipo proporcional y nombre del usuario junto a su cuenta. Se compactó la banda de demo sin eliminar protecciones. Se añadió acceso directo por teclado al contenido y ajustes compartidos de espaciado, foco y movimiento reducido. El menú móvil conserva por ahora el componente details existente: todavía no es un drawer.

Archivos funcionales: `backend/resources/views/layouts/app.blade.php` y `backend/public/css/admin.css`. No hay cambios de PHP, consultas, migraciones ni reglas comerciales. No se modificaron las optimizaciones previas de Dashboard/Pedidos. La documentación pendiente preexistente se conservó.

## Verificación y límites

La línea base y la verificación posterior reutilizaron AdminDashboardTest, ProductManagementTest y OrderManagementTest: ambas pasaron con 80 pruebas y 398 aserciones, SQLite en memoria. No constituye una medición HTTP ni acredita mejora de rendimiento frente a Render. Las mediciones remotas históricas siguen siendo las de fases anteriores; no se comparan con la duración de PHPUnit.

La ejecución PHP requiere permiso fuera del sandbox para leer el autoload existente; no se instalaron dependencias. Falta verificación visual con capturas antes/después en escritorio y móvil. El cambio no se considera una Etapa A completa ni una ronda cerrada.

## Requisito local no disponible

`backend/AGENTS.md` exige: “Read the `testing-best-practices` skill before writing tests.” No se encontró esa skill en el catálogo disponible ni en la búsqueda de archivos SKILL.md bajo `C:/Users/Marco/.codex`. Se utilizaron pruebas existentes sin escribir pruebas nuevas. Antes de iniciar cambios de comportamiento y sus nuevas pruebas es necesario disponer de esa guía requerida.

## Pendientes registrados en el primer punto de control (estado histórico)

- Medición comparable HTTP/SQL de los módulos, apertura y guardado de productos; optimizaciones solo a partir de evidencia.
- Tarjetas/tabla, filtros, acciones rápidas y conservación de contexto.
- Editor unificado con categorías, imágenes y variantes; guardado progresivo y errores por campo.
- Presentación diferenciada de modalidades, sin alterar stock desconocido/cero ni descontarlo al pedir.
- Revisión del circuito WhatsApp y configuración existente del destinatario, sin confundir apertura con envío o confirmación.
- Drawer móvil, revisión responsive, capturas y validación funcional de los cambios futuros.

No se conectó Soluciones Es ni se accedió a datos comerciales. La descripción actual del manual permanece vigente; el único cambio implementado hasta este punto afecta la base visual del layout administrativo.


## Continuación autorizada y estado actual (prevalece sobre el primer punto de control)

Por instrucción posterior del usuario, la ausencia de la skill de pruebas quedó registrada y no se extendió a las tareas independientes. Se reutilizaron las convenciones de PHPUnit y los casos existentes, agregando regresiones específicas. No se instaló ninguna dependencia para sustituir la guía.

### Etapa A — Base visual y navegación

Commit inicial 5523cd7 y continuación fc4187a: encabezado compacto, identidad de usuario, banda de demo conservada, foco, acceso al contenido y movimiento reducido. Se implementó drawer móvil con cierre, Escape y contención del foco; el fondo queda inert mientras está abierto. CSS y JavaScript compartidos incluyen componentes de la siguiente etapa. No hay SPA, framework nuevo ni cambios de infraestructura.

### Etapa B — Productos

Commit a58d826: tarjetas principales, tabla alternativa y preferencia por navegador. Búsqueda por nombre/SKU, filtros por categoría, modalidad, publicación, disponibilidad, destacado y orden; paginación de 15. Las tarjetas usan fotografía real si existe; no se inventaron imágenes para los cuatro productos ficticios sin fotos del local.

El editor integra categorías, fotografías y variantes en la misma página. Reutiliza formularios, policies, requests, servicios y transacciones; cada sección guarda explícitamente por separado. Categorías permite búsqueda jerárquica y alta autorizada; después de crearla se selecciona para asociarla. Galería conserva principal, orden, validación, WebP y borrado confirmado. Las variantes se expanden dentro del editor y se paginan de 20, sin borrado de referencias históricas. Opciones avanzadas agrupa slug y descripción extensa. Crear un producto abre después el editor; sus relaciones requieren que exista primero.

Se incorporaron estados de guardado, errores 422 por campo, prevención de envíos duplicados, advertencia por cambios pendientes y conservación del contexto de retorno. Ante un fallo de conexión se indica verificar el resultado antes de reenviar: no se inventó idempotencia de todas las ediciones o cargas multimedia. La idempotencia del checkout existente permanece intacta.

Los controladores devuelven confirmación JSON solo cuando se solicita, después del commit, evitando seguir una redirección y renderizar otra página por cada guardado progresivo. Los formularios HTML mantienen su comportamiento. La actualización de una sección obtiene el editor y sustituye solo su panel, conservando otros formularios modificados y el desplazamiento. Todavía puede optimizarse el tamaño de ese fragmento si futuras mediciones lo justifican.

Las modalidades reutilizan inquiry/calculated, independientemente de simple/variable. Una oferta por consulta ahora siempre indica Consultar disponibilidad, aunque exista un valor interno de stock. El modo calculable conserva stock nulo/cero/positivo y decimales de tres posiciones. No hay reserva ni descuento de existencias. Los datos comerciales externos siguen protegidos.

### Etapa C — Pedidos

Commit c68190f: WhatsApp agrega contacto congelado del pedido, teléfono, precios unitarios calculables y líneas sujetas a consulta. Conserva número, variantes, cantidades, unidades y totales conocidos. No se agregaron observaciones nuevas sin un campo persistido en el modelo.

Se aprovechó el único destinatario configurable existente y se añadió vista previa administrativa. No se creó una administración de múltiples números: era opcional y no es necesaria para completar el destino activo. DEMO_MODE continúa anulando el enlace. El panel aclara recibido/pendiente, preparación/gestión y listo para retirar/acción explícita de la ferretería. Abrir WhatsApp no cambia estados ni acredita envío. Facturación e integración con Soluciones Es permanecen separadas.

### Etapa D — Verificación realizada

- Línea base del primer punto de control: 80 pruebas, 398 aserciones.
- Regresión de módulos afectados: 226 pruebas, 1433 aserciones, aprobadas en SQLite en memoria tras agregar JSON y pruebas nuevas.
- Tras el último ajuste de disponibilidad y consultas del editor simple: 92 pruebas, 412 aserciones de PublicCatalogTest, PublicSearchTest y ProductManagementTest aprobadas. Estos grupos se superponen con los anteriores; no sumar sus cantidades como pruebas únicas.
- Contratos JSON integrados de categorías, variantes y galería (incluidos principal, orden y eliminación) verificados con nuevas regresiones: último grupo ProductManagementTest/ImageManagementTest, 69 pruebas y 455 aserciones aprobadas.
- Pint ejecutado sobre PHP modificado. JavaScript comprobado con node --check. No se ejecutó nuevamente toda la suite.
- Prueba SQL del listado: cinco consultas con un producto y cinco con once; tiempo SQL observado 0,35 y 0,41 ms respectivamente, en SQLite ficticio. Prueba adicional comprueba que abrir un simple no consulta product_variants, dato que no se utiliza en esa pantalla.
- UI local: listado, editor variable, guardado sin recarga, rechazo de precio del padre variable conservando entrada, corrección posterior, guardado de categorías y apertura de variante existente. Guardados sobre productos ficticios sin cambiar sus valores comerciales; no se generaron pedidos ni mensajes reales.
- Viewports solicitados al navegador: 1366×900 y 390×844. Se verificó menú móvil, cierre y diseño de tarjetas. No equivale a una matriz de dispositivos físicos.

### Mediciones HTTP locales

Servidor PHP local, SQLite existente de desarrollo, cuatro productos ficticios y tres variantes. Login administrativo de desarrollo, una solicitud de calentamiento y cinco muestras completas por ruta; mediana de pared, incluye HTTP/backend y lectura de HTML, excluye recursos y renderizado del navegador. No es medición exclusiva de controlador/Blade.

| Ruta / sección | Mediana local (ms) | Referencia HTTP remota histórica (s) |
|---|---:|---:|
| Dashboard | 189,85 | 2,41 |
| Productos | 203,26 | 1,68 |
| Categorías | 186,56 | 1,46 |
| Pedidos | 194,25 | 1,96 |
| CMS | 192,31 | 1,72 |
| Editor variable | 207,58 | Sin referencia equivalente |
| /up | 151,63 | 0,26 |

Las columnas NO son antes/después comparables. Las mediciones locales se tomaron durante la ronda antes de eliminar las redirecciones del guardado JSON y no acreditan una mejora porcentual. No se dispone de medición HTTP local anterior al rediseño, ni se volvió a medir Render. El beneficio comprobado de guardado es la eliminación del renderizado/redirección intermedia; el listado se probó sin N+1. No se atribuye el límite a Render Free ni a distancia geográfica sin evidencia.

### Capturas reales

Carpeta docs/capturas-correcciones: productos-antes.png y productos-despues.png (vista inicial del navegador); productos-escritorio.png, editor-escritorio.png, variantes-integradas.png, productos-movil.png, menu-movil.png y guardado-confirmado.png. La captura inicial precede a tarjetas/editor, pero ya incluye el encabezado del primer punto de control. No hay captura histórica anterior del editor original; no se fabricó.

### Límites y pendientes concretos

1. PostgreSQL: estas modificaciones locales nuevas aún no se validaron en Actions/PostgreSQL. No se hizo push por restricción del usuario. La validación anterior de fases 18/19 no se presenta como evidencia de este nuevo código.
2. Rendimiento: falta un antes/después HTTP equivalente en demo, desglose exclusivo de controlador/Blade y medición específica de guardar. No se publicará para obtener esas cifras sin autorización. El ahorro de navegación se verifica funcionalmente, no como porcentaje de velocidad.
3. Fotografías: una imagen por tarjeta y carga diferida están implementadas, pero los bytes de la imagen original no se reducen. Falta medir un conjunto representativo; no se introdujo un servicio de miniaturas especulativo ni se modificó Cloudinary. Las pruebas de imágenes existentes cubren validación y almacenamiento; no se recorrió en navegador todo el CRUD multimedia de esta interfaz.
4. La creación de categoría es independiente de la asociación; cambiar tipo de producto puede requerir volver a abrir el editor para ver la sección de variantes. Se conserva la protección de salida con borradores pendientes. No hay autoguardado ni guardado atómico del conjunto de secciones.
5. No se ejecutó todavía en navegador el alta de todas las relaciones ni una matriz física de dispositivos; la cobertura de permisos/datos se apoya en pruebas existentes y nuevas.
6. No hay conector real de Punto de Venta, múltiples destinos WhatsApp, reserva/descuento de stock ni cobros. Las dos modalidades usan el contrato comercial vigente, sin inventar semántica de una integración aún desconocida.
7. Manual: revisión 3 refleja estos cambios locales y mantiene separada la candidata PostgreSQL anterior. El ZIP de revisión 2 permanece como entrega histórica; no representa esta revisión posterior.

No hay migraciones en esta ronda. No se modificaron Neon, Cloudinary, Render, credenciales ni datos comerciales. El archivo docs/pendientes-correcciones.md preexistente permanece sin integrar ni sobrescribir. Esta rama continúa siendo local: no hay push, merge ni despliegue.
