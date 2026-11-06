# 4. Organización del proyecto

## 4.1 Árbol representativo

```text
Ferreteria El Nuevo Sol/
├── .github/workflows/verify.yml
├── render.yaml
├── README.md
├── reference/wordpress/            referencia histórica del frontend
├── docs/                          documentación de fases y manual
└── backend/
    ├── app/
    │   ├── Casts/ Enums/ Models/ Policies/ Rules/ Support/
    │   ├── Http/Controllers/{Auth,Account,Admin,Storefront}/
    │   ├── Http/Requests/ y Http/Middleware/
    │   ├── Providers/AppServiceProvider.php
    │   └── Services/{Audit,Catalog,Content,Orders,Storefront}/
    ├── bootstrap/app.php
    ├── config/
    ├── database/{migrations,factories,seeders}/
    ├── deploy/
    ├── public/{css,js,images}/
    ├── resources/views/{layouts,admin,storefront,account,auth}/
    ├── routes/{web.php,console.php}
    ├── storage/ y bootstrap/cache/
    ├── tests/{Feature,Unit}/
    ├── composer.json y composer.lock
    ├── package.json
    ├── phpunit.xml
    └── Dockerfile
```

La notación con llaves agrupa directorios hermanos, no representa nombres literales. `reference/wordpress` no se ejecuta como WordPress en el despliegue Laravel. `.tools` contiene recursos locales ignorados y no forma parte del contrato de instalación para otro desarrollador.

## 4.2 Puntos de entrada para mantenimiento

Para cambiar una regla de catálogo, comenzar por `CatalogQuery`, `ProductPresentation` y `CatalogRequest`. Para corregir el cálculo de una compra, revisar `Cart` y `Checkout`; el JavaScript no es la autoridad económica. Para cambiar una transición de estado, revisar `OrderStatus`, `OrderStatusRequest` y `OrderStatusService`. Para imágenes, seguir `ImageStorage` y `CloudinaryImages`, además de los gestores de galería/CMS. Para importación, empezar por `CommercialSyncController` y `CommercialSyncService`.

`public/images/branding/logo-primary.png` y `tools-pattern.png` son recursos versionados de marca. Las imágenes cargadas por usuarios son privadas y sus rutas se guardan en datos; no deben añadirse al repositorio. `storage` contiene logs y archivos de ejecución. `bootstrap/cache` aloja configuración y manifiestos generados.

## 4.3 Documentación heredada

Los documentos de fases registran el estado en su fecha. Por ejemplo, `app/Services/README.md` todavía habla de servicios previstos desde Fase 2, aunque los servicios ya existen. El manual utiliza el código de la revisión fijada como fuente principal y conserva la documentación anterior sin reescribirla. El [inventario](anexos/inventario.md) permite localizar componentes y archivos de prueba concretos.
