# Fase 1 — Auditoría y preparación

> Informe inicial anterior a la recepción del frontend. Sus observaciones sobre archivos faltantes quedaron superadas por [la auditoría del ZIP recibido](fase-1-frontend.md). La sección G conserva la propuesta de arquitectura y debe leerse junto con ese informe actualizado.

Fecha: 24 de septiembre de 2026.

## A. Estructura actual

Se inspeccionaron la carpeta de trabajo, el listado de archivos versionados, el estado de Git, la existencia de historial y el directorio del adjunto proporcionado.

La carpeta de trabajo solo contenía `.git`. Git no tiene commits ni archivos versionados. El único archivo en el directorio del adjunto es `Texto pegado.txt`, con las instrucciones del proyecto. No se recibió el frontend indicado en esas instrucciones.

Por ello, esta es una auditoría del material disponible y una propuesta de arquitectura; la auditoría del frontend permanece pendiente. La ausencia de código no permite concluir que el proyecto anterior carezca de problemas o dependencias.

## B. Tecnologías encontradas

No hay archivos PHP, HTML, CSS, JavaScript, imágenes, manifiestos de dependencias ni configuración de aplicación que permitan identificar tecnologías efectivamente utilizadas.

Laravel, PHP y MySQL/MariaDB son requisitos del nuevo proyecto, no tecnologías verificadas en una implementación existente. WordPress/WooCommerce se mencionan como antecedentes, pero no hay código para comprobar su uso.

## C. Componentes reutilizables

No se pueden identificar componentes concretos sin los archivos originales. Se propone evaluar para reutilización:

- Identidad visual, logotipo, fotografías, tipografías y paleta, verificando disponibilidad y licencias.
- HTML semántico adaptable a Blade, estilos responsive y componentes de navegación.
- Interacciones de presentación en JavaScript que no dependan del backend anterior.

Cada decisión de conservación deberá vincularse a archivos concretos y a una revisión de accesibilidad, dependencias y comportamiento móvil.

## D. Componentes a reemplazar

No se identificaron archivos que puedan eliminarse o reemplazarse todavía. Cuando se reciba el frontend se buscarán hooks, shortcodes, funciones de WordPress, plantillas WooCommerce, endpoints `wp-json`, llamadas a `admin-ajax.php`, plugins y credenciales incrustadas.

Si existen, las dependencias de ejecución de WordPress/WooCommerce deberán sustituirse por rutas, controladores, vistas y servicios Laravel. El carrito del navegador podrá aportar la interfaz, pero sus precios, totales y permisos no serán autoridad. Un flujo que abra WhatsApp sin persistir antes el pedido deberá reemplazarse en Fase 11.

WooCommerce podrá ser un origen externo futuro mediante un adaptador; no será el backend del sitio.

## E. Frontend

La viabilidad de conservar el diseño todavía no puede determinarse. La opción inicial es Blade con CSS y JavaScript, conservando recursos y componentes compatibles. No se propone una SPA.

Pendiente: inventario completo, páginas y componentes, estilos, puntos de quiebre responsive, carrito, WhatsApp, contenido hardcodeado, dependencias y validación visual en escritorio y móvil.

## F. Riesgos

- Falta el frontend: no se puede estimar el esfuerzo real de adaptación ni verificar la identidad visual.
- No se conoce el mecanismo de comunicación de Punto de Venta: no corresponde elegir protocolo, implementar un proveedor ni asumir acceso a Access.
- Falta acordar cómo se cargarán datos comerciales reales antes de disponer del conector. No se habilitará edición de precios a vendedores.
- Deben confirmarse reglas comerciales de cantidades fraccionarias, redondeo, impuestos incluidos en Lista 1 y tratamiento de disponibilidad durante la confirmación por WhatsApp.
- El entorno de ejecución y despliegue no fue validado; versiones, extensiones PHP, motor de base de datos y dependencias se verificarán al preparar Fase 2.

## G. Arquitectura propuesta

Monolito Laravel con vistas Blade, módulos organizados por responsabilidad y MySQL/MariaDB. El navegador solo se comunica con Laravel mediante HTTP; nunca accede directamente a la base de datos.

Flujo externo futuro: origen documentado → adaptador → servicio de integración → datos comerciales en MySQL → catálogo Laravel.

### Estructura prevista para fases posteriores

```text
app/
  Models/
  Http/Controllers/{Storefront,Account,Admin}/
  Http/Requests/
  Policies/
  Services/{Catalog,Cart,Orders,Integrations}/
  Contracts/Integrations/ExternalCatalogProvider.php
  Jobs/
resources/
  views/{layouts,components,storefront,account,admin}/
  css/
  js/
routes/web.php
database/{migrations,factories,seeders}/
public/images/
storage/app/
tests/{Feature,Unit}/
docs/
```

Este árbol es una propuesta, no una estructura ya implementada. Se crearán servicios, jobs y contratos cuando su fase los necesite, evitando clases vacías y proveedores ficticios. Las versiones se seleccionarán según compatibilidad y soporte verificados en Fase 2.

### Separación de datos y responsabilidades

- `products`: identidad del producto y contenido editorial web, publicación, destacados y SEO. `product_variants`: entidades seleccionables con atributos propios. Las claves externas incluirán el origen cuando sea necesario para evitar colisiones.
- Datos comerciales: SKU, Lista 1, stock, unidad e información comercial se distinguirán explícitamente de los campos editoriales. El diseño de migraciones deberá definir una lista cerrada de campos actualizables por sincronización. Ningún payload externo se asignará masivamente al producto.
- Los productos simples tendrán sus datos comerciales propios; las variantes tendrán los suyos. Un producto variable no usará el stock o precio del padre como dato vendible. Se resolverá la representación exacta en Fase 2 sin duplicar autoridades.
- `units` permitirá ampliar unidades de venta. Cantidades y dinero se almacenarán con precisión decimal definida según el dominio, sin cálculos monetarios con coma flotante. La modalidad calculable o a confirmar deberá resolverse por producto/variante.
- `categories.parent_id` y `category_product` permitirán jerarquías y pertenencia múltiple. Se impedirán ciclos, sin imponer dos niveles.
- `product_images` contendrá referencias y metadatos; los archivos se almacenarán fuera de MySQL. Cargas con validación de formato, tamaño y contenido, nombres generados y almacenamiento sin ejecución de scripts.
- `users`, autorización mediante Policies/Gates y validación backend: vendedores podrán editar contenido web, pero no datos comerciales sincronizados, usuarios ni integración.
- `orders`, `order_items` y `order_status_history`: pedidos de invitados o clientes, snapshots históricos, cantidades, importes y estado de confirmación. Una transacción validará precios y disponibilidad y persistirá el pedido antes de generar el enlace de WhatsApp. Se contemplará idempotencia para evitar pedidos duplicados por reintentos.
- El total confirmado excluirá conceptos pendientes de cotización y se mostrará como parcial cuando corresponda; un importe desconocido no se representará como precio cero. Abrir WhatsApp no demuestra que el mensaje se haya enviado.
- Los clientes accederán únicamente a sus pedidos mediante autorización backend. La consulta de pedidos de invitados necesitará un mecanismo seguro por definir; no bastará el número secuencial.
- `notifications` y `notification_user` registrarán destinatarios y lectura individual. `homepage_sections`, `banners` y `settings` cubrirán el mini CMS y la configuración, incluido el número de WhatsApp.
- `sync_runs` registrará ejecuciones cuando exista integración real. `activity_logs` registrará operaciones relevantes sin secretos. La sincronización no sobrescribirá descripciones, fotografías, categorías, publicación, SEO ni contenido editorial.

Se usarán claves foráneas, índices y restricciones según consultas reales. El catálogo tendrá paginación, carga anticipada de relaciones y búsqueda inicial en base de datos. Las pruebas críticas acompañarán cada fase: permisos, aislamiento entre clientes, cálculo de importes, pedidos e invariancia de datos editoriales durante sincronización.

La primera versión será solo retiro en local, sin pagos online ni envíos. No se construirán ahora módulos de promociones, sucursales, facturación, push ni otras extensiones futuras.

## H. Plan concreto de Fase 1

Cambios realizados exclusivamente de documentación:

| Archivo | Acción | Propósito |
| --- | --- | --- |
| `README.md` | Crear | Identificar el proyecto y su estado real. |
| `docs/fase-1-auditoria.md` | Crear | Registrar evidencia, límites, arquitectura y pendientes. |

No se modificó ni eliminó código existente; no había archivos de aplicación. No se instaló Laravel ni se crearon migraciones, autenticación, catálogo, pedidos o conectores.

Pasos pendientes dentro de Fase 1:

1. Recibir el frontend original o la ubicación exacta donde se encuentra.
2. Inventariar todos sus archivos y revisar el código fuente completo; separar recursos, código propio y dependencias de terceros.
3. Documentar componentes conservables y reemplazos con rutas concretas, contenido fijo, dependencias de WordPress/WooCommerce y riesgos encontrados.
4. Revisar el comportamiento visual y responsive y ajustar esta propuesta con la evidencia.
5. Entregar el cierre de Fase 1 y esperar instrucciones para Fase 2.

Decisiones pendientes: confirmar el frontend de referencia; resolver carga comercial transitoria, reglas de cantidades/importes y disponibilidad antes de desarrollar esos flujos; documentar el mecanismo externo antes de Fase 18. La recepción de archivos no autoriza avanzar automáticamente de fase.
