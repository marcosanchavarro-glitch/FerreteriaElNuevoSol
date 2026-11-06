# 17. Despliegue

## 17.1 Configuración versionada y estado

`render.yaml` define un único Web Service Docker Free, rama `codex/render-demo`, contexto `./backend`, Dockerfile `./backend/Dockerfile`, healthcheck `/up` y autoDeployTrigger off. No define una base Render ni un disco persistente. El estado histórico ubicó Render en Oregon y Neon en Ohio; esas regiones no están fijadas en el YAML y no se comprobaron nuevamente.

El último código publicado confirmado fue `939164e`. El manual describe la candidata `af85ee7`, que incluye importación y optimización aún pendientes de publicación. Los archivos de despliegue no cambiaron entre esas referencias. La autorización anterior quedó condicionada a respaldos adecuados; no se considera cumplida esa condición.

## 17.2 Construcción y arranque

Docker utiliza PHP 8.4 con Apache, GD/WebP, PDO PostgreSQL y otras extensiones. Composer instala dependencias de producción desde lock. El contenedor incorpora CSS/JS estáticos; los secretos no se incluyen en la imagen. `deploy/apache.conf` dirige el document root a public y adapta el puerto; `deploy/php.ini` configura el runtime.

`deploy/start.sh` realiza esta secuencia: determina PORT y APP_URL, valida variables, escribe robots de demo, cachea configuración, ejecuta `migrate --force --no-interaction`, ejecuta `RenderDemoSeeder`, cachea vistas y arranca Apache. Esto **no es un procedimiento genérico para producción comercial**: el validador exige DEMO_MODE=true y condiciones de demo.

RenderDemoSeeder termina sin crear registros si existe `settings.key=demo.initialized`. Si no existe pero la base ya tiene datos, rechaza la inicialización. No ejecuta migrate:fresh ni borra datos al reiniciar. Antes de publicar sin crear datos ficticios, debe confirmarse que la demo ya está inicializada y que no existen migraciones inesperadas pendientes.

## 17.3 Variables y persistencia

La [tabla de configuración](anexos/configuracion.md) enumera las variables relevantes. APP_KEY debe conservarse. DB_URL apunta a Neon mediante TLS verify-full y PGSSLROOTCERT del contenedor. Sesiones y caché usan la base; imágenes usan CATALOG_STORAGE=cloudinary. FILESYSTEM_DISK=local no cambia ese adaptador multimedia. El prefijo Cloudinary identifica los archivos y no debe cambiarse sin migrar referencias.

El validador exige DEMO_ACCESS_HASH válido incluso con DEMO_ACCESS_REQUIRED=false; no eliminarlo por suponer que el acceso público lo vuelve opcional. La clave de entrada opcional y el login de administración son mecanismos diferentes. Las credenciales de los usuarios demo se utilizan para inicialización; cambiarlas en variables no equivale a cambiar contraseñas de usuarios ya creados.

## 17.4 Procedimiento de publicación pendiente

1. Revisar estado Git, diff entre versión activa y candidata, cambios documentales y SHA exacto a publicar. Confirmar que no se mezclan funcionalidades nuevas.
2. Verificar respaldo recuperable de base e imágenes, fecha, cobertura y prueba aislada. Registrar el SHA y deploy activo para reversión.
3. Confirmar configuración existente sin imprimir secretos, marcador de inicialización, migraciones y ausencia de conector real. No regenerar claves ni cambiar servicios.
4. Con autorización vigente y condiciones satisfechas, integrar por el procedimiento acordado en la rama usada por Render. Revisar checks requeridos; no saltar protecciones del repositorio.
5. Iniciar un solo deploy manual del SHA integrado, observar construcción y estado live. No cambiar región/plan para resolver problemas no diagnosticados.
6. Comprobar catálogo, login, vistas administrativas, productos, pedidos y CMS con lecturas. No confirmar checkout, importar ni crear datos de prueba en la demo.
7. Revisar errores posteriores y repetir tres rondas HTTP autenticadas, con calentamiento y el mismo cliente/método de la línea base. Medir cuerpo y cabeceras de las mismas rutas; registrar mediana y respuestas, sin mezclar métricas del navegador.

## 17.5 Reversión

Ante defecto crítico detener las comprobaciones y volver al deploy/commit previo confirmado. No ejecutar automáticamente `migrate:rollback`: la candidata no agrega migraciones y revertir código es distinto de revertir datos. Si hubiera corrupción, aislar el problema y usar el respaldo validado con un procedimiento autorizado que preserve cambios posteriores legítimos. La reversión del contenedor no restaura Neon ni Cloudinary.

Diagrama: [despliegue](diagramas/06-despliegue.mmd). Fuentes: render.yaml, Dockerfile, deploy/start.sh, deploy/validate-environment.php, RenderDemoSeeder y `docs/despliegue-demo-render.md`.
