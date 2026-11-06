# 18. Respaldos y recuperación

## 18.1 Estado acreditado

No hay evidencia suficiente para afirmar que existe un respaldo completo y recuperable de la demo. La persistencia de archivos/datos entre despliegues fue comprobada históricamente, pero persistencia no equivale a respaldo. Tampoco lo son la copia Git, el rollback del contenedor o la compensación en memoria de ImageStorage.

La recomendación siguiente es un procedimiento pendiente, no una automatización instalada. No se consultaron secretos, no se exportaron datos y no se crearon recursos durante la elaboración del manual.

## 18.2 Cobertura y conservación

Debe conservarse la base PostgreSQL completa y los archivos originales usados por productos, categorías, secciones y banners. Para cada archivo registrar public_id, tipo de entrega authenticated, formato, ruta relativa y fecha, vinculándolo con los registros de base. Guardar el SHA del código, versiones de herramientas, hora UTC, ubicación privada y checksum del respaldo. Los secretos necesarios para reconstrucción deben mantenerse en un gestor autorizado, separados del documento y del repositorio.

Acordar una ventana sin cambios editoriales durante la captura coordinada de base e imágenes o registrar cómo conciliar cambios concurrentes. Un dump coherente de PostgreSQL no asegura por sí solo coherencia temporal con un servicio de archivos externo. Definir retención y tolerancia de pérdida/tiempo de recuperación antes de uso comercial.

## 18.3 PostgreSQL

Comprobar en Neon el proyecto/rama real de la demo y el historial recuperable disponible. La ventana depende de la configuración y del plan; no presumir cobertura ilimitada. Un punto histórico puede servir para recuperación rápida, pero una exportación independiente permite verificar el archivo de respaldo.

Ejemplo operativo para un entorno autorizado con clientes PostgreSQL 17 compatibles. `PGSERVICE` refiere a perfiles previamente configurados de forma segura; no son variables de Laravel ni perfiles que ya existan en este proyecto:

```powershell
$env:PGSERVICE = 'ens_origen_respaldo'
pg_dump --format=custom --file='ens-prepublicacion.dump'
pg_restore --list 'ens-prepublicacion.dump'
Get-FileHash 'ens-prepublicacion.dump' -Algorithm SHA256
```

El destino de archivo debe ser privado y estar fuera del repositorio. Revisar código de salida y advertencias. Listar el contenido verifica legibilidad del archivo, **no prueba restauración**. pg_dump respalda una base; no exporta automáticamente todos los roles del clúster. Fuente: [PostgreSQL 17 pg_dump](https://www.postgresql.org/docs/17/app-pgdump.html).

En una base temporal vacía y autorizada, nunca la demo:

```powershell
$env:PGSERVICE = 'ens_destino_aislado'
pg_restore --dbname='service=ens_destino_aislado' --no-owner --no-acl --exit-on-error --single-transaction 'ens-prepublicacion.dump'
```

Verificar tablas, cantidades de registros, claves, relaciones y una muestra de pedidos/snapshots. No arrancar la aplicación con envío de mensajes habilitado contra la copia. Registrar resultado y proteger o retirar la copia según la política acordada. No se propone copiar respaldos reales a artefactos de Actions públicos ni convertir las pruebas ficticias anteriores en pruebas sobre datos comerciales.

## 18.4 Cloudinary

Revisar Settings → Backup del entorno correspondiente y comprobar respaldo de imágenes existentes, no solo activación para nuevas cargas. Si no existe, proponer activación y copia inicial tras confirmar cuota/autorización. Revisar Version history y recuperar una muestra sin sobrescribir ni borrar originales de la demo. La verificación completa requiere cobertura de todos los archivos referenciados y correspondencia de identificadores, además de una prueba de recuperación aislada. Fuente: [Cloudinary backups](https://cloudinary.com/documentation/backups_and_version_management).

Una descarga ZIP sin mapa de identificadores puede conservar bytes pero no reconstruir las rutas que consulta Laravel. Un listado de URLs firmadas tampoco es un respaldo durable, porque esas URLs expiran. El prefijo y el tipo authenticated deben conservarse al diseñar una restauración.

## 18.5 Criterio para liberar el bloqueo

Se requiere origen/destino identificados, fecha previa a publicación, cobertura de base e imágenes, evidencia de restauración aislada y procedimiento de recuperación reproducible. Si una condición no está acreditada, mantener el despliegue detenido. No atribuir resultados a consolas que no se pudieron consultar.
