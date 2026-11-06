"""Genera anexos documentales desde archivos locales; no arranca Laravel ni toca bases."""
from pathlib import Path
import json
import re

manual = Path(__file__).resolve().parents[1]
root = manual.parents[1]
annex = manual / 'anexos'
annex.mkdir(exist_ok=True)

def read(path):
    return path.read_text(encoding='utf-8-sig')

def write(name, text):
    (annex / name).write_text(text.rstrip() + '\n', encoding='utf-8')

purpose = {
 'users':'Identidad y autorización de cuentas.', 'password_reset_tokens':'Tokens del broker de recuperación.',
 'sessions':'Persistencia de sesiones web.', 'cache':'Valores de caché.', 'cache_locks':'Propiedad y expiración de bloqueos atómicos.',
 'jobs':'Cola de trabajos de Laravel; su tabla no implica worker desplegado.', 'job_batches':'Metadatos de lotes de cola.',
 'failed_jobs':'Diagnóstico de trabajos fallidos.', 'products':'Producto editorial y comercial simple o padre variable.',
 'product_variants':'Presentación comercial perteneciente a producto variable.', 'categories':'Árbol de categorías.',
 'category_product':'Relación muchos-a-muchos entre catálogo y categorías.', 'product_images':'Galería privada y orden de imágenes.',
 'orders':'Cabecera y contacto congelado del pedido.', 'order_items':'Líneas con snapshots comerciales.',
 'order_status_history':'Transiciones de estado del pedido.', 'notifications':'Avisos internos dirigidos a usuarios.',
 'notification_user':'Destinatarios y fecha de lectura individual.', 'homepage_sections':'Bloques de inicio configurables.',
 'banners':'Banners con imagen y visibilidad.', 'settings':'Valores de configuración por clave.',
 'sync_runs':'Registro de planificación y aplicación de importaciones.', 'activity_logs':'Trazabilidad de operaciones seleccionadas.'
}
meaning = {
 'id':'Identificador primario del registro.', 'first_name':'Nombre.', 'last_name':'Apellido.', 'email':'Dirección de correo de la cuenta o recuperación.',
 'phone':'Teléfono de contacto.', 'password':'Hash de contraseña; nunca texto plano.', 'role':'Rol admin/seller/customer.',
 'active':'Habilitación del registro según el módulo.', 'email_verified_at':'Fecha de verificación; no implica flujo de verificación implementado.',
 'remember_token':'Token para recordar autenticación.', 'created_at':'Fecha de creación.', 'updated_at':'Última actualización.',
 'token':'Token de recuperación gestionado por el broker.', 'user_id':'Usuario relacionado; ver regla de borrado.',
 'ip_address':'IP de sesión.', 'user_agent':'Identificación del cliente HTTP.', 'payload':'Contenido serializado de sesión o trabajo.',
 'last_activity':'Marca temporal de última actividad de sesión.', 'key':'Clave de caché o configuración.',
 'value':'Valor de configuración/caché o descriptor de variante según tabla.', 'expiration':'Vencimiento del registro.',
 'owner':'Propietario del bloqueo.', 'queue':'Nombre de cola.', 'attempts':'Intentos del trabajo.',
 'reserved_at':'Instante de reserva del trabajo.', 'available_at':'Instante en que puede ejecutarse.',
 'total_jobs':'Cantidad total de trabajos.', 'pending_jobs':'Trabajos pendientes.', 'failed_jobs':'Cantidad de fallos.',
 'failed_job_ids':'IDs de trabajos fallidos.', 'options':'Opciones serializadas del lote.', 'cancelled_at':'Cancelación del lote.',
 'finished_at':'Finalización de lote o ejecución.', 'uuid':'Identificador único del trabajo fallido.',
 'connection':'Conexión de cola.', 'exception':'Detalle del error del trabajo.', 'failed_at':'Fecha del fallo.',
 'external_id':'Identificador externo opcional; no es la clave de coincidencia del importador SKU.',
 'sku':'Código comercial; índice no único globalmente.', 'name':'Nombre de entidad.', 'slug':'Identificador legible de URL.',
 'short_description':'Descripción breve del producto.', 'description':'Descripción editorial o resumen histórico.',
 'product_type':'simple o variable.', 'sale_unit':'Unidad enum; en variante NULL hereda.',
 'purchase_mode':'calculated o inquiry; en variante NULL hereda.', 'price':'Precio entero en centavos; NULL desconocido/no aplicable.',
 'stock':'Stock entero en milésimas; cast expone texto decimal.', 'status':'Estado del producto, pedido o ejecución.',
 'featured':'Selección como destacado; no publica por sí sola.', 'external_synced_at':'Marca de gestión comercial externa.',
 'product_id':'Producto padre o referencia opcional del snapshot.', 'parent_id':'Categoría superior; NULL raíz.',
 'category_id':'Categoría vinculada.', 'image_path':'Ruta relativa del archivo normalizado.',
 'sort_order':'Posición de presentación.', 'path':'Ruta relativa UUID de imagen.', 'alt_text':'Texto alternativo.',
 'is_primary':'Marca de imagen principal gestionada por el servicio.', 'order_number':'Número público ENS-ULID.',
 'customer_first_name':'Nombre del contacto copiado al confirmar.', 'customer_last_name':'Apellido copiado al confirmar.',
 'customer_phone':'Teléfono copiado al confirmar.', 'customer_email':'Correo opcional copiado al confirmar.',
 'subtotal':'Importe en centavos; en línea puede ser NULL pendiente.', 'confirmed_total':'Suma confirmada del pedido, sin líneas pendientes.',
 'has_items_to_confirm':'Indica líneas que requieren cotización.', 'fulfillment_method':'pickup, única modalidad actual.',
 'order_id':'Pedido relacionado.', 'product_variant_id':'Variante relacionada, anulable en snapshot.',
 'product_name':'Nombre de producto congelado.', 'variant_name':'Nombre de variante congelado.',
 'quantity':'Cantidad en milésimas, positiva en líneas de pedido.', 'unit_price':'Precio unitario congelado en centavos.',
 'requires_confirmation':'Línea sin importe definitivo.', 'previous_status':'Estado anterior; NULL al crear.',
 'new_status':'Estado resultante.', 'changed_by':'Usuario que realizó transición; opcional.',
 'title':'Título visible.', 'message':'Texto del aviso.', 'type':'Tipo de sección o notificación según tabla.',
 'created_by':'Creador opcional del aviso.', 'published_at':'Fecha desde la cual es visible el aviso.',
 'expires_at':'Vencimiento del aviso.', 'notification_id':'Aviso relacionado.', 'read_at':'Fecha de lectura por destinatario.',
 'subtitle':'Subtítulo.', 'content':'Texto de sección, mostrado escapado.', 'button_text':'Etiqueta de enlace.',
 'button_url':'Ruta interna validada.', 'settings':'Opciones JSON del bloque; actualmente max_items.',
 'starts_at':'Inicio de vigencia; no editable por el formulario CMS actual.',
 'ends_at':'Fin exclusivo de vigencia; no editable por el formulario CMS actual.',
 'source':'Origen lógico de importación.', 'started_at':'Inicio de ejecución.', 'processed_count':'Registros analizados.',
 'created_count':'Altas aplicadas.', 'updated_count':'Actualizaciones aplicadas.', 'error_count':'Filas inválidas/conflictivas o fallo de ejecución.',
 'error_details':'JSON de preview/apply, actor, filas, huella y errores; no solo errores.',
 'action':'Acción auditada.', 'entity_type':'Tipo descriptivo de entidad.', 'entity_id':'Identificador descriptivo sin FK.',
 'metadata':'Metadatos heredados; no expuestos libremente en vista de auditoría.',
 'actor_id':'ID histórico sin FK.', 'actor_name':'Nombre histórico del actor.', 'actor_role':'Rol histórico del actor.',
 'module':'Módulo de auditoría.', 'result':'success/rejected o valor legacy unknown.', 'origin':'admin/system/external o legacy.',
 'before_values':'Campos permitidos anteriores al cambio.', 'after_values':'Campos permitidos posteriores al cambio.',
 'checkout_key':'Hash SHA-256 del token para idempotencia, único y anulable en datos antiguos.'
}
tables = {}
for path in sorted((root/'backend/database/migrations').glob('*.php')):
    up = read(path).split('public function down')[0]
    matches = list(re.finditer(r"Schema::(?:create|table)\('([^']+)'", up))
    for i,m in enumerate(matches):
        name = m.group(1)
        body = up[m.end(): matches[i+1].start() if i+1<len(matches) else len(up)]
        table = tables.setdefault(name, {'fields':[], 'rules':[], 'files':[]})
        table['files'].append(path.name)
        for statement in re.findall(r'\$table->([^;]+);', body):
            statement = re.sub(r'\s+', ' ', statement)
            method = statement.split('(')[0]
            if method in ['primary','index','unique']:
                table['rules'].append(statement)
                continue
            if method == 'timestamps':
                fields = [('created_at','timestamp','Sí','—'), ('updated_at','timestamp','Sí','—')]
            elif method == 'rememberToken':
                fields = [('remember_token','string(100)','Sí','—')]
            elif method == 'id':
                fields = [('id','bigint autoincremental','No','PK')]
            else:
                field = re.search(r"\('([^']+)'", statement)
                if not field:
                    raise ValueError(statement)
                default = re.search(r'->default\((.*?)\)', statement)
                properties = []
                if default: properties.append('default '+default.group(1))
                if '->useCurrent()' in statement: properties.append('fecha actual')
                if '->unique()' in statement: properties.append('UNIQUE')
                if '->primary()' in statement: properties.append('PK')
                if '->index()' in statement: properties.append('INDEX')
                fk = re.search(r"->constrained\('([^']+)'\)", statement)
                if fk:
                    properties.append('FK '+fk.group(1)+'.id')
                    for rule,label in [('cascadeOnDelete','DELETE CASCADE'),('nullOnDelete','DELETE SET NULL'),('restrictOnDelete','DELETE RESTRICT')]:
                        if rule in statement: properties.append(label)
                args = re.match(r"\w+\('[^']+'(?:,\s*([^)]*))?\)", statement)
                kind = method + ('('+args.group(1)+')' if args and args.group(1) else '')
                fields=[(field.group(1),kind,'Sí' if '->nullable()' in statement else 'No', '; '.join(properties) or '—')]
            table['fields'].extend(fields)

out=['# Anexo A. Diccionario de datos', '',
     'Derivado de las operaciones `up()` de las diez migraciones, incorporando ampliaciones posteriores. Los tipos son nombres de Schema Builder; no se ejecutaron migraciones para extraer esta información. Las cadenas sin longitud explícita usan la longitud predeterminada del framework. `timestamps()` se expande a dos campos anulables. Las restricciones indicadas pertenecen a la base; reglas de enums, importes y jerarquía adicionales están en modelos/requests.', '',
     f'Tablas declaradas: **{len(tables)}**. Además Laravel mantiene `migrations` (id, migration y batch) como registro del migrador. El ER muestra las relaciones del dominio y sesiones; las tablas técnicas independientes se describen aquí.', '']
for i,(name,t) in enumerate(tables.items(),1):
    out += [f'## A.{i} `{name}`', '', purpose[name], '', 'Migraciones: '+', '.join('`'+f+'`' for f in dict.fromkeys(t['files']))+'.', '', '| Campo | Tipo | NULL | Restricciones / default | Significado |','|---|---|---|---|---|']
    for field,kind,nullable,rule in t['fields']:
        if field not in meaning: raise ValueError('Falta significado '+field)
        out.append(f'| `{field}` | {kind} | {nullable} | {rule} | {meaning[field]} |')
    if t['rules']:
        out += ['', 'Claves e índices adicionales (declaración exacta):', ''] + ['- `'+v+'`.' for v in t['rules']]
    out += ['']
write('diccionario-datos.md','\n'.join(out))

out=['# Anexo B. Inventario y trazabilidad','',
     'Inventario local de codex/admin-corrections, sobre la candidata af85ee7; revisión documental 3. Las rutas se obtuvieron con `php artisan route:list --except-vendor --json` en proceso aislado con DB_CONNECTION=sqlite y DB_DATABASE=:memory:, sin migrar ni ejecutar pruebas. La inspección arranca el framework para enumerar rutas; no acredita respuestas HTTP. El JSON original se conserva en `rutas-verificadas.json`. Los middleware de clase/grupo se muestran tal como los informa Laravel; las políticas ejecutadas dentro del controlador se documentan en los capítulos.', '',
     '## B.1 Rutas verificadas','', '| Método | URI | Nombre | Acción | Middleware |','|---|---|---|---|---|']
routes=json.loads(read(manual/'rutas-verificadas.json'))
for r in routes:
    action=r['action'].replace('App\\Http\\Controllers\\','')
    cells=[r['method'].replace('|',' / '),'/'+r['uri'].lstrip('/'),r['name'] or '—',action,', '.join(r['middleware'])]
    out.append('| '+' | '.join('`'+v.replace('|',' / ')+'`' for v in cells)+' |')
out += ['', 'La ruta `/up` está configurada en bootstrap/app.php y puede quedar excluida por el filtro de rutas del framework. No existe archivo de rutas API de negocio en esta entrega.', '', '## B.2 Componentes y entradas públicas', '']
for folder in ['Models','Casts','Enums','Policies','Rules','Support','Http/Controllers','Http/Requests','Http/Middleware','Services']:
    out += [f'### B.2.{folder.replace("/", " ")}','', '| Archivo bajo backend/app | Métodos públicos declarados / casos enum |','|---|---|']
    for p in sorted((root/'backend/app'/folder).rglob('*.php')):
        code=read(p)
        methods=re.findall(r'public\s+(?:static\s+)?function\s+(\w+)\s*\(',code)
        cases=re.findall(r"case\s+\w+\s*=\s*'([^']+)'",code)
        items=[x for x in methods if x!='__construct'] or cases or ['Configuración del modelo o herencia del framework']
        out.append('| `'+p.relative_to(root/'backend/app').as_posix()+'` | '+', '.join(items)+' |')
out += ['', '## B.3 Pruebas y fuentes de interfaz', '']
for folder in ['backend/tests','backend/resources/views','backend/public/css','backend/public/js','backend/database/seeders','backend/deploy']:
    out += ['### '+folder,'']
    for p in sorted((root/folder).rglob('*')):
        if p.is_file(): out.append('- `'+p.relative_to(root).as_posix()+'`')
    out.append('')
out += ['## B.4 Dependencias directas resueltas', '', '| Paquete | Rango declarado | Versión en composer.lock |','|---|---|---|']
manifest=json.loads(read(root/'backend/composer.json'))
lock=json.loads(read(root/'backend/composer.lock'))
versions={p['name']:p['version'] for p in lock['packages']+lock['packages-dev']}
for package,version in {**manifest['require'],**manifest['require-dev']}.items():
    out.append(f'| `{package}` | `{version}` | `{versions.get(package,"Requisito de plataforma")}` |')
out += ['', 'PHP local consultado: 8.4.26; Composer local: 2.10.3; SQLite de la extensión local: 3.53.4; Git local: 2.55.0.windows.3. Estos datos no fijan la versión de los servicios externos ni el parche de futuras imágenes Docker.', '',
        '## B.5 Documentación de evidencia', '',
        '- `docs/pendientes-correcciones.md`: mediciones y validación candidata, más revisión focalizada local aprobada sin commit al iniciar el manual.',
        '- `docs/integracion-punto-de-venta.md`: contrato manual y límites del motor.',
        '- `docs/despliegue-demo-render.md`: instalación y verificaciones históricas de la demo.',
        '- Documentos de fases: contexto histórico, subordinado al código actual cuando difieren.',
        '- `backend/app/Services/README.md`: documento antiguo de planificación; no describe el estado implementado completo.']
write('inventario.md','\n'.join(out))
print(f'Anexos: {len(tables)} tablas, {sum(len(t["fields"]) for t in tables.values())} campos, {len(routes)} rutas.')
