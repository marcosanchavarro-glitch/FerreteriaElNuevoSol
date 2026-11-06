# 7. Autenticación, roles y seguridad

## 7.1 Registro y sesiones

`RegistrationController` recibe `RegisterRequest` y crea una cuenta Customer; el rol no se toma libremente del formulario. `User` utiliza hash de contraseña y oculta campos de autenticación en serialización. `SessionController` normaliza email mediante la solicitud y llama al guard web con `active=true`. Un acceso correcto regenera el ID de sesión y actualiza la marca de contraseña mediante `AccountSession`. El cierre invalida la sesión y regenera CSRF.

El login combina un límite de ruta de 20 solicitudes/minuto por IP con cinco intentos fallidos por email/IP durante 60 segundos. Registro y recuperación tienen límites de cinco por minuto por IP. `auth.session` permite invalidar sesiones cuyo hash de contraseña ya no corresponde; `EnsureActiveUser` finaliza el acceso de una cuenta desactivada. La duración predeterminada es 10080 minutos de inactividad, con `expire_on_close=false`; no significa una sesión irrevocable.

La recuperación usa el broker de Laravel y `password_reset_tokens`, con respuesta genérica para evitar revelar cuentas. En demo, la solicitud de correo está deshabilitada. Fuera de demo, `MAIL_MAILER=array` no entrega mensajes: debe configurarse SMTP de forma separada antes de ofrecer recuperación real.

## 7.2 Matriz de autorización

| Operación | Visitante | Customer | Seller | Admin |
|---|---|---|---|---|
| Catálogo y carrito | Sí | Sí | Sí | Sí |
| Perfil y pedidos propios en Mi cuenta | No | Sí | Perfil staff | Perfil staff |
| Panel y gestión de pedidos | No | No | Sí | Sí |
| Edición editorial de productos | No | No | Sí | Sí |
| Precio/stock/SKU local | No | No | No | Sí |
| Precio/stock de producto externo por formulario | No | No | No | No |
| CMS y categorías | No | No | Sí | Sí |
| Configuración, auditoría e importación | No | No | No | Sí |

Todas las autorizaciones privadas exigen cuenta activa. `manage-roles` está definido, pero no hay un módulo CRUD de usuarios/roles expuesto en las rutas revisadas. No confundir un gate disponible con una pantalla implementada. Las variantes gestionadas externamente tienen restricciones más fuertes: `ProductVariantPolicy::update()` las bloquea por completo; los productos externos conservan edición editorial, imágenes y categorías.

## 7.3 Recursos ajenos y CSRF

Los pedidos de Customer se consultan a través de su relación `orders()` y se autorizan con `OrderPolicy`. La confirmación del checkout requiere un número guardado en la sesión y, si tiene usuario, que coincida con la cuenta actual. `scopeBindings()` restringe variantes e imágenes al producto de la URL. No basta ocultar botones: las políticas y solicitudes vuelven a controlar el servidor.

Los formularios mutables usan `@csrf`; PUT/PATCH/DELETE se expresan mediante el campo `_method` cuando corresponde. El grupo web mantiene la protección CSRF estándar sin exclusiones agregadas en `bootstrap/app.php`. Los errores de validación no deben confundirse con 403 de autorización o 404 de recurso no visible.

## 7.4 XSS, entradas e imágenes

Blade presenta texto con `{{ ... }}`; el contenido CMS no se trata como HTML arbitrario. `InternalLink` admite rutas internas y rechaza URLs externas, caracteres de control y secuencias peligrosas incluso codificadas. El JavaScript usa `textContent` para los datos de las variantes. Los filtros SQL interpolan solo fragmentos fijos o valores de listas permitidas; el texto del usuario se envía como parámetro.

Las cargas aceptan JPG/JPEG/PNG/WebP, máximo 5 MB y 3000 × 3000 píxeles, y verifican MIME y extensión. `ImageStorage` decodifica, vuelve a codificar WebP y genera UUID; no conserva nombres de archivo suministrados. La ruta debe coincidir con familia, ID y UUID. Se responde con `nosniff` y controles de visibilidad; el acceso público a una imagen de producto exige producto publicado. Los gestores de archivos incluyen compensación ante fallos, con los límites descritos en el capítulo 18.

## 7.5 Secretos y límites

`.env`, bases locales, archivos cargados y secretos se excluyen de Git y de la imagen Docker según sus archivos de exclusión. El arranque valida nombres de variables sin imprimir sus valores. Contraseñas y tokens no se guardan en flash de errores. Render configura HTTPS, cookie Secure y debug desactivado; `HttpOnly` y SameSite lax se definen en sesiones. La confianza en proxies se activa cuando `RENDER=true`.

La revisión focalizada histórica no halló defectos críticos/altos en su alcance. No equivale a pentest, revisión exhaustiva del historial de secretos ni evaluación de todos los navegadores. No publicar `.env` para diagnosticar un error.

Diagrama: [autenticación y autorización](diagramas/03-autenticacion.mmd). Fuentes: políticas, solicitudes Auth, `AccountSession`, `bootstrap/app.php`, `config/session.php`, `AppServiceProvider` y pruebas Authentication/ProfileSecurity/ImageManagement.
