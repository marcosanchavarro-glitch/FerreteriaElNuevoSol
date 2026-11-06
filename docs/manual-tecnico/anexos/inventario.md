# Anexo B. Inventario y trazabilidad

Inventario local de codex/admin-corrections, sobre la candidata af85ee7; revisión documental 3. Las rutas se obtuvieron con `php artisan route:list --except-vendor --json` en proceso aislado con DB_CONNECTION=sqlite y DB_DATABASE=:memory:, sin migrar ni ejecutar pruebas. La inspección arranca el framework para enumerar rutas; no acredita respuestas HTTP. El JSON original se conserva en `rutas-verificadas.json`. Los middleware de clase/grupo se muestran tal como los informa Laravel; las políticas ejecutadas dentro del controlador se documentan en los capítulos.

## B.1 Rutas verificadas

| Método | URI | Nombre | Acción | Middleware |
|---|---|---|---|---|
| `GET / HEAD` | `/` | `home` | `Storefront\CatalogController@home` | `web` |
| `GET / HEAD` | `/acceso-demo` | `demo.login` | `DemoAccessController@create` | `web` |
| `POST` | `/acceso-demo` | `demo.enter` | `DemoAccessController@store` | `web, throttle:demo-access` |
| `GET / HEAD` | `/admin` | `admin.home` | `Admin\DashboardController` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/activity-logs` | `admin.activity.index` | `Admin\ActivityLogController@index` | `web, auth, auth.session, can:access-admin, can:view-activity` |
| `GET / HEAD` | `/admin/activity-logs/{activityLog}` | `admin.activity.show` | `Admin\ActivityLogController@show` | `web, auth, auth.session, can:access-admin, can:view-activity` |
| `GET / HEAD` | `/admin/categories` | `admin.categories.index` | `Admin\CategoryController@index` | `web, auth, auth.session, can:access-admin` |
| `POST` | `/admin/categories` | `admin.categories.store` | `Admin\CategoryController@store` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/categories/create` | `admin.categories.create` | `Admin\CategoryController@create` | `web, auth, auth.session, can:access-admin` |
| `PUT / PATCH` | `/admin/categories/{category}` | `admin.categories.update` | `Admin\CategoryController@update` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/categories/{category}/edit` | `admin.categories.edit` | `Admin\CategoryController@edit` | `web, auth, auth.session, can:access-admin` |
| `POST` | `/admin/categories/{category}/image` | `admin.categories.image.store` | `Admin\CategoryImageController@store` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/categories/{category}/image` | `admin.categories.image.show` | `Admin\CategoryImageController@show` | `web, auth, auth.session, can:access-admin` |
| `DELETE` | `/admin/categories/{category}/image` | `admin.categories.image.destroy` | `Admin\CategoryImageController@destroy` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/commercial-sync` | `admin.sync.index` | `Admin\CommercialSyncController@index` | `web, auth, auth.session, can:access-admin, can:manage-commercial-sync` |
| `POST` | `/admin/commercial-sync/preview` | `admin.sync.preview` | `Admin\CommercialSyncController@preview` | `web, auth, auth.session, can:access-admin, can:manage-commercial-sync` |
| `POST` | `/admin/commercial-sync/{syncRun}/apply` | `admin.sync.apply` | `Admin\CommercialSyncController@apply` | `web, auth, auth.session, can:access-admin, can:manage-commercial-sync` |
| `GET / HEAD` | `/admin/content/banners` | `admin.banners.index` | `Admin\ContentController@index` | `web, auth, auth.session, can:access-admin` |
| `POST` | `/admin/content/banners` | `admin.banners.store` | `Admin\ContentController@save` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/content/banners/create` | `admin.banners.create` | `Admin\ContentController@edit` | `web, auth, auth.session, can:access-admin` |
| `PUT` | `/admin/content/banners/{id}` | `admin.banners.update` | `Admin\ContentController@save` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/content/banners/{id}/edit` | `admin.banners.edit` | `Admin\ContentController@edit` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/content/banners/{id}/image` | `admin.banners.image` | `Admin\ContentController@image` | `web, auth, auth.session, can:access-admin` |
| `POST` | `/admin/content/banners/{id}/move` | `admin.banners.move` | `Admin\ContentController@move` | `web, auth, auth.session, can:access-admin` |
| `PATCH` | `/admin/content/banners/{id}/visibility` | `admin.banners.visibility` | `Admin\ContentController@visibility` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/content/sections` | `admin.sections.index` | `Admin\ContentController@index` | `web, auth, auth.session, can:access-admin` |
| `POST` | `/admin/content/sections` | `admin.sections.store` | `Admin\ContentController@save` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/content/sections/create` | `admin.sections.create` | `Admin\ContentController@edit` | `web, auth, auth.session, can:access-admin` |
| `PUT` | `/admin/content/sections/{id}` | `admin.sections.update` | `Admin\ContentController@save` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/content/sections/{id}/edit` | `admin.sections.edit` | `Admin\ContentController@edit` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/content/sections/{id}/image` | `admin.sections.image` | `Admin\ContentController@image` | `web, auth, auth.session, can:access-admin` |
| `DELETE` | `/admin/content/sections/{id}/image` | `admin.sections.image.destroy` | `Admin\ContentController@removeImage` | `web, auth, auth.session, can:access-admin` |
| `POST` | `/admin/content/sections/{id}/move` | `admin.sections.move` | `Admin\ContentController@move` | `web, auth, auth.session, can:access-admin` |
| `PATCH` | `/admin/content/sections/{id}/visibility` | `admin.sections.visibility` | `Admin\ContentController@visibility` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/orders` | `admin.orders.index` | `Admin\OrderController@index` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/orders/{order}` | `admin.orders.show` | `Admin\OrderController@show` | `web, auth, auth.session, can:access-admin` |
| `PATCH` | `/admin/orders/{order}/status` | `admin.orders.update` | `Admin\OrderController@update` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/products` | `admin.products.index` | `Admin\ProductController@index` | `web, auth, auth.session, can:access-admin` |
| `POST` | `/admin/products` | `admin.products.store` | `Admin\ProductController@store` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/products/create` | `admin.products.create` | `Admin\ProductController@create` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/products/{product}` | `admin.products.show` | `Admin\ProductController@show` | `web, auth, auth.session, can:access-admin` |
| `PUT / PATCH` | `/admin/products/{product}` | `admin.products.update` | `Admin\ProductController@update` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/products/{product}/categories` | `admin.products.categories.edit` | `Admin\ProductCategoryController@edit` | `web, auth, auth.session, can:access-admin` |
| `PUT` | `/admin/products/{product}/categories` | `admin.products.categories.update` | `Admin\ProductCategoryController@update` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/products/{product}/edit` | `admin.products.edit` | `Admin\ProductController@edit` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/products/{product}/images` | `admin.products.images.index` | `Admin\ProductImageController@index` | `web, auth, auth.session, can:access-admin` |
| `POST` | `/admin/products/{product}/images` | `admin.products.images.store` | `Admin\ProductImageController@store` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/products/{product}/images/{image}` | `admin.products.images.show` | `Admin\ProductImageController@show` | `web, auth, auth.session, can:access-admin` |
| `PUT / PATCH` | `/admin/products/{product}/images/{image}` | `admin.products.images.update` | `Admin\ProductImageController@update` | `web, auth, auth.session, can:access-admin` |
| `DELETE` | `/admin/products/{product}/images/{image}` | `admin.products.images.destroy` | `Admin\ProductImageController@destroy` | `web, auth, auth.session, can:access-admin` |
| `POST` | `/admin/products/{product}/images/{image}/move` | `admin.products.images.move` | `Admin\ProductImageController@move` | `web, auth, auth.session, can:access-admin` |
| `POST` | `/admin/products/{product}/images/{image}/primary` | `admin.products.images.primary` | `Admin\ProductImageController@primary` | `web, auth, auth.session, can:access-admin` |
| `POST` | `/admin/products/{product}/quick` | `admin.products.quick` | `Admin\ProductController@quickUpdate` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/products/{product}/variants` | `admin.products.variants.index` | `Admin\VariantController@index` | `web, auth, auth.session, can:access-admin` |
| `POST` | `/admin/products/{product}/variants` | `admin.products.variants.store` | `Admin\VariantController@store` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/products/{product}/variants/create` | `admin.products.variants.create` | `Admin\VariantController@create` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/products/{product}/variants/{variant}` | `admin.products.variants.show` | `Admin\VariantController@show` | `web, auth, auth.session, can:access-admin` |
| `PUT / PATCH` | `/admin/products/{product}/variants/{variant}` | `admin.products.variants.update` | `Admin\VariantController@update` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/products/{product}/variants/{variant}/edit` | `admin.products.variants.edit` | `Admin\VariantController@edit` | `web, auth, auth.session, can:access-admin` |
| `GET / HEAD` | `/admin/profile` | `admin.profile.edit` | `ProfileController@edit` | `web, auth, auth.session, can:access-admin` |
| `PATCH` | `/admin/profile` | `admin.profile.update` | `ProfileController@update` | `web, auth, auth.session, can:access-admin, throttle:authentication` |
| `PUT` | `/admin/profile/password` | `admin.profile.password` | `ProfileController@password` | `web, auth, auth.session, can:access-admin, throttle:authentication` |
| `GET / HEAD` | `/admin/settings` | `admin.settings.edit` | `Admin\SettingsController@edit` | `web, auth, auth.session, can:access-admin, can:manage-settings` |
| `PUT` | `/admin/settings` | `admin.settings.update` | `Admin\SettingsController@update` | `web, auth, auth.session, can:access-admin, can:manage-settings` |
| `GET / HEAD` | `/carrito` | `cart.index` | `Storefront\CartController@index` | `web, auth.session` |
| `POST` | `/carrito` | `cart.store` | `Storefront\CartController@store` | `web, auth.session` |
| `PATCH` | `/carrito/{key}` | `cart.update` | `Storefront\CartController@update` | `web, auth.session` |
| `DELETE` | `/carrito/{key}` | `cart.destroy` | `Storefront\CartController@destroy` | `web, auth.session` |
| `GET / HEAD` | `/categorias/{slug}` | `store.category` | `Storefront\CatalogController@index` | `web` |
| `GET / HEAD` | `/checkout` | `checkout.create` | `Storefront\CheckoutController@create` | `web, auth.session` |
| `POST` | `/checkout` | `checkout.store` | `Storefront\CheckoutController@store` | `web, auth.session` |
| `GET / HEAD` | `/imagenes/categorias/{slug}` | `store.images.category` | `Storefront\MediaController@category` | `web` |
| `GET / HEAD` | `/imagenes/contenido/{kind}/{id}` | `store.images.content` | `Admin\ContentController@image` | `web` |
| `GET / HEAD` | `/imagenes/productos/{image}` | `store.images.product` | `Storefront\MediaController@product` | `web` |
| `GET / HEAD` | `/login` | `login` | `Auth\SessionController@create` | `web, guest` |
| `POST` | `/login` | `—` | `Auth\SessionController@store` | `web, guest, throttle:authentication` |
| `POST` | `/logout` | `logout` | `Auth\SessionController@destroy` | `web, auth, auth.session` |
| `GET / HEAD` | `/mi-cuenta` | `account.edit` | `ProfileController@edit` | `web, auth, auth.session, can:access-account` |
| `PATCH` | `/mi-cuenta` | `account.update` | `ProfileController@update` | `web, auth, auth.session, throttle:authentication, can:access-account` |
| `PUT` | `/mi-cuenta/contrasena` | `account.password` | `ProfileController@password` | `web, auth, auth.session, throttle:authentication, can:access-account` |
| `GET / HEAD` | `/mi-cuenta/notificaciones` | `account.notifications.index` | `Account\NotificationController@index` | `web, auth, auth.session, can:access-account` |
| `POST` | `/mi-cuenta/notificaciones/leidas` | `account.notifications.read-all` | `Account\NotificationController@readAll` | `web, auth, auth.session, can:access-account` |
| `PATCH` | `/mi-cuenta/notificaciones/{notification}/leida` | `account.notifications.read` | `Account\NotificationController@read` | `web, auth, auth.session, can:access-account` |
| `GET / HEAD` | `/mi-cuenta/pedidos` | `account.orders.index` | `Account\OrderController@index` | `web, auth, auth.session, can:access-account` |
| `GET / HEAD` | `/mi-cuenta/pedidos/{order}` | `account.orders.show` | `Account\OrderController@show` | `web, auth, auth.session, can:access-account` |
| `GET / HEAD` | `/pedido-confirmado/{number}` | `checkout.confirmation` | `Storefront\CheckoutController@confirmation` | `web, auth.session` |
| `GET / HEAD` | `/productos` | `store.products` | `Storefront\CatalogController@index` | `web` |
| `GET / HEAD` | `/productos/{slug}` | `store.product` | `Storefront\CatalogController@show` | `web` |
| `GET / HEAD` | `/recuperar-contrasena` | `password.request` | `Auth\PasswordResetController@request` | `web, guest` |
| `POST` | `/recuperar-contrasena` | `password.email` | `Auth\PasswordResetController@email` | `web, guest, throttle:password-recovery` |
| `GET / HEAD` | `/registro` | `register` | `Auth\RegistrationController@create` | `web, guest` |
| `POST` | `/registro` | `—` | `Auth\RegistrationController@store` | `web, guest, throttle:registration` |
| `POST` | `/restablecer-contrasena` | `password.store` | `Auth\PasswordResetController@update` | `web, guest, throttle:password-recovery` |
| `GET / HEAD` | `/restablecer-contrasena/{token}` | `password.reset` | `Auth\PasswordResetController@edit` | `web, guest` |

La ruta `/up` está configurada en bootstrap/app.php y puede quedar excluida por el filtro de rutas del framework. No existe archivo de rutas API de negocio en esta entrega.

## B.2 Componentes y entradas públicas

### B.2.Models

| Archivo bajo backend/app | Métodos públicos declarados / casos enum |
|---|---|
| `Models/ActivityLog.php` | user |
| `Models/Banner.php` | Configuración del modelo o herencia del framework |
| `Models/Category.php` | parent, children, products |
| `Models/HomepageSection.php` | Configuración del modelo o herencia del framework |
| `Models/Notification.php` | order, scopeVisible, creator, users |
| `Models/Order.php` | user, items, statusHistory |
| `Models/OrderItem.php` | order, product, productVariant |
| `Models/OrderStatusHistory.php` | order, changedBy |
| `Models/Product.php` | isExternallyManaged, variants, images, categories |
| `Models/ProductImage.php` | product |
| `Models/ProductVariant.php` | product, isExternallyManaged, effectiveSaleUnit, effectivePurchaseMode |
| `Models/Setting.php` | Configuración del modelo o herencia del framework |
| `Models/SyncRun.php` | Configuración del modelo o herencia del framework |
| `Models/User.php` | orders, notifications, activityLogs, createdNotifications, statusChanges |
### B.2.Casts

| Archivo bajo backend/app | Métodos públicos declarados / casos enum |
|---|---|
| `Casts/MinorUnits.php` | get, set |
| `Casts/ScaledQuantity.php` | get, set |
### B.2.Enums

| Archivo bajo backend/app | Métodos públicos declarados / casos enum |
|---|---|
| `Enums/FulfillmentMethod.php` | pickup |
| `Enums/HomepageSectionType.php` | label |
| `Enums/OrderStatus.php` | label, nextStates |
| `Enums/ProductStatus.php` | draft, published, private |
| `Enums/ProductType.php` | simple, variable |
| `Enums/PurchaseMode.php` | calculated, inquiry |
| `Enums/SaleUnit.php` | unit, meter, kilogram, gram, liter, milliliter, box, package, bundle, roll, other |
| `Enums/SyncRunStatus.php` | running, completed, failed |
| `Enums/UserRole.php` | admin, seller, customer |
### B.2.Policies

| Archivo bajo backend/app | Métodos públicos declarados / casos enum |
|---|---|
| `Policies/CategoryPolicy.php` | viewAny, create, update, delete |
| `Policies/ContentPolicy.php` | viewAny, create, update |
| `Policies/OrderPolicy.php` | viewAny, view, update |
| `Policies/ProductPolicy.php` | viewAny, view, create, update, updateIdentity, updateCommercial, delete |
| `Policies/ProductVariantPolicy.php` | view, create, update, updateCommercial, delete |
### B.2.Rules

| Archivo bajo backend/app | Métodos públicos declarados / casos enum |
|---|---|
| `Rules/InternalLink.php` | safe, validate |
| `Rules/PasswordInput.php` | validate |
### B.2.Support

| Archivo bajo backend/app | Métodos públicos declarados / casos enum |
|---|---|
| `Support/AccountSession.php` | start, refreshPasswordHash, end |
| `Support/ActivityDisplay.php` | field, value |
| `Support/ProductDisplay.php` | quantity, money, moneyInput, toMinorUnits, label |
### B.2.Http Controllers

| Archivo bajo backend/app | Métodos públicos declarados / casos enum |
|---|---|
| `Http/Controllers/Account/NotificationController.php` | index, read, readAll |
| `Http/Controllers/Account/OrderController.php` | index, show |
| `Http/Controllers/Admin/ActivityLogController.php` | index, show |
| `Http/Controllers/Admin/CategoryController.php` | index, create, store, edit, update |
| `Http/Controllers/Admin/CategoryImageController.php` | store, show, destroy |
| `Http/Controllers/Admin/CommercialSyncController.php` | index, preview, apply |
| `Http/Controllers/Admin/ContentController.php` | index, edit, save, move, visibility, removeImage, image |
| `Http/Controllers/Admin/DashboardController.php` | __invoke |
| `Http/Controllers/Admin/OrderController.php` | index, show, update |
| `Http/Controllers/Admin/ProductCategoryController.php` | edit, update |
| `Http/Controllers/Admin/ProductController.php` | index, quickUpdate, create, store, show, edit, update |
| `Http/Controllers/Admin/ProductImageController.php` | index, store, show, update, primary, move, destroy |
| `Http/Controllers/Admin/SettingsController.php` | edit, update |
| `Http/Controllers/Admin/VariantController.php` | index, create, store, show, edit, update |
| `Http/Controllers/Auth/PasswordResetController.php` | request, email, edit, update |
| `Http/Controllers/Auth/RegistrationController.php` | create, store |
| `Http/Controllers/Auth/SessionController.php` | create, store, destroy |
| `Http/Controllers/Controller.php` | Configuración del modelo o herencia del framework |
| `Http/Controllers/DemoAccessController.php` | create, store |
| `Http/Controllers/ProfileController.php` | edit, update, password |
| `Http/Controllers/Storefront/CartController.php` | index, store, update, destroy |
| `Http/Controllers/Storefront/CatalogController.php` | home, index, show |
| `Http/Controllers/Storefront/CheckoutController.php` | create, store, confirmation |
| `Http/Controllers/Storefront/MediaController.php` | product, category |
### B.2.Http Requests

| Archivo bajo backend/app | Métodos públicos declarados / casos enum |
|---|---|
| `Http/Requests/Admin/OrderFilterRequest.php` | authorize, rules, after, messages |
| `Http/Requests/Admin/OrderStatusRequest.php` | authorize, rules, messages |
| `Http/Requests/Auth/LoginRequest.php` | authorize, rules |
| `Http/Requests/Auth/PasswordEmailRequest.php` | authorize, rules |
| `Http/Requests/Auth/RegisterRequest.php` | authorize, rules |
| `Http/Requests/Auth/ResetPasswordRequest.php` | rules |
| `Http/Requests/CatalogRequest.php` | authorize, rules |
| `Http/Requests/CategoryRequest.php` | authorize, rules |
| `Http/Requests/ChangePasswordRequest.php` | authorize, rules |
| `Http/Requests/ContentRequest.php` | authorize, rules, attributes, messages |
| `Http/Requests/ImageUpdateRequest.php` | authorize, rules |
| `Http/Requests/ImageUploadRequest.php` | authorize, rules |
| `Http/Requests/ProductCategoriesRequest.php` | authorize, rules |
| `Http/Requests/ProductRequest.php` | product, authorize, rules, after, productData |
| `Http/Requests/ProfileRequest.php` | authorize, rules |
| `Http/Requests/SiteSettingsRequest.php` | authorize, rules, after |
| `Http/Requests/VariantRequest.php` | variant, authorize, rules, after, variantData |
### B.2.Http Middleware

| Archivo bajo backend/app | Métodos públicos declarados / casos enum |
|---|---|
| `Http/Middleware/DemoAccess.php` | handle |
| `Http/Middleware/EnsureActiveUser.php` | handle |
### B.2.Services

| Archivo bajo backend/app | Métodos públicos declarados / casos enum |
|---|---|
| `Services/Audit/ActivityLogService.php` | module, snapshot, gallery, order, sanitize, record, changed |
| `Services/Catalog/CategoryImages.php` | replace |
| `Services/Catalog/CategoryManager.php` | tree, save |
| `Services/Catalog/CloudinaryImages.php` | put, get, delete |
| `Services/Catalog/CommercialSyncService.php` | normalizeSku, decode, preview, apply |
| `Services/Catalog/ImageStorage.php` | store, assertPath, backup, delete, restore, response |
| `Services/Catalog/ProductGallery.php` | upload, primary, move, delete |
| `Services/Catalog/VariantManager.php` | save |
| `Services/Content/ContentManager.php` | save, removeImage, move |
| `Services/Content/Homepage.php` | defaults, data |
| `Services/Content/SiteSettings.php` | all, get |
| `Services/Orders/OrderNotifications.php` | record |
| `Services/Orders/OrderStatusService.php` | change |
| `Services/Storefront/Cart.php` | fractional, quantity, decimal, lines, resolve, summary, put, remove |
| `Services/Storefront/CatalogQuery.php` | base, catalog |
| `Services/Storefront/Checkout.php` | fingerprint, review, place |
| `Services/Storefront/ProductPresentation.php` | offer, card |
| `Services/Storefront/PublicCategories.php` | all, rows, find, descendants, ancestors |
| `Services/Storefront/PublicImages.php` | available, product, category |
| `Services/Storefront/WhatsAppOrder.php` | message, url |

## B.3 Pruebas y fuentes de interfaz

### backend/tests

- `backend/tests/Feature/ActivityAuditTest.php`
- `backend/tests/Feature/AdminDashboardTest.php`
- `backend/tests/Feature/AuthenticationTest.php`
- `backend/tests/Feature/CartCheckoutTest.php`
- `backend/tests/Feature/CategoryManagementTest.php`
- `backend/tests/Feature/CloudinaryImagesTest.php`
- `backend/tests/Feature/CommercialSyncPostgresTest.php`
- `backend/tests/Feature/CommercialSyncTest.php`
- `backend/tests/Feature/CustomerOrdersTest.php`
- `backend/tests/Feature/DataIntegrityTest.php`
- `backend/tests/Feature/DataModelTest.php`
- `backend/tests/Feature/DemoModeTest.php`
- `backend/tests/Feature/ExampleTest.php`
- `backend/tests/Feature/HomepageCmsTest.php`
- `backend/tests/Feature/ImageManagementTest.php`
- `backend/tests/Feature/MigrationLifecycleTest.php`
- `backend/tests/Feature/OrderManagementTest.php`
- `backend/tests/Feature/OrderNotificationsTest.php`
- `backend/tests/Feature/ProductManagementTest.php`
- `backend/tests/Feature/ProfileSecurityTest.php`
- `backend/tests/Feature/PublicCatalogTest.php`
- `backend/tests/Feature/PublicSearchTest.php`
- `backend/tests/Feature/RenderConfigurationTest.php`
- `backend/tests/Feature/SiteSettingsTest.php`
- `backend/tests/Feature/VariantManagementTest.php`
- `backend/tests/TestCase.php`
- `backend/tests/Unit/ExampleTest.php`

### backend/resources/views

- `backend/resources/views/account/edit.blade.php`
- `backend/resources/views/account/navigation.blade.php`
- `backend/resources/views/account/notifications.blade.php`
- `backend/resources/views/account/orders/index.blade.php`
- `backend/resources/views/account/orders/list.blade.php`
- `backend/resources/views/account/orders/show.blade.php`
- `backend/resources/views/account/staff-profile.blade.php`
- `backend/resources/views/admin/activity/index.blade.php`
- `backend/resources/views/admin/activity/show.blade.php`
- `backend/resources/views/admin/categories/form.blade.php`
- `backend/resources/views/admin/categories/index.blade.php`
- `backend/resources/views/admin/content/edit.blade.php`
- `backend/resources/views/admin/content/index.blade.php`
- `backend/resources/views/admin/home.blade.php`
- `backend/resources/views/admin/navigation.blade.php`
- `backend/resources/views/admin/orders/index.blade.php`
- `backend/resources/views/admin/orders/show.blade.php`
- `backend/resources/views/admin/products/categories-panel.blade.php`
- `backend/resources/views/admin/products/categories.blade.php`
- `backend/resources/views/admin/products/form.blade.php`
- `backend/resources/views/admin/products/images-panel.blade.php`
- `backend/resources/views/admin/products/images.blade.php`
- `backend/resources/views/admin/products/index.blade.php`
- `backend/resources/views/admin/products/sections.blade.php`
- `backend/resources/views/admin/products/show.blade.php`
- `backend/resources/views/admin/settings.blade.php`
- `backend/resources/views/admin/sync.blade.php`
- `backend/resources/views/admin/variants/fields.blade.php`
- `backend/resources/views/admin/variants/form.blade.php`
- `backend/resources/views/admin/variants/index.blade.php`
- `backend/resources/views/admin/variants/show.blade.php`
- `backend/resources/views/auth/demo.blade.php`
- `backend/resources/views/auth/forgot-password.blade.php`
- `backend/resources/views/auth/login.blade.php`
- `backend/resources/views/auth/register.blade.php`
- `backend/resources/views/auth/reset-password.blade.php`
- `backend/resources/views/components/demo-notice.blade.php`
- `backend/resources/views/errors/404.blade.php`
- `backend/resources/views/home.blade.php`
- `backend/resources/views/layouts/app.blade.php`
- `backend/resources/views/layouts/storefront.blade.php`
- `backend/resources/views/storefront/add-cart.blade.php`
- `backend/resources/views/storefront/card.blade.php`
- `backend/resources/views/storefront/cart.blade.php`
- `backend/resources/views/storefront/catalog.blade.php`
- `backend/resources/views/storefront/checkout.blade.php`
- `backend/resources/views/storefront/confirmation.blade.php`
- `backend/resources/views/storefront/content-block.blade.php`
- `backend/resources/views/storefront/content-button.blade.php`
- `backend/resources/views/storefront/home.blade.php`
- `backend/resources/views/storefront/invalid.blade.php`
- `backend/resources/views/storefront/order-items.blade.php`
- `backend/resources/views/storefront/pagination.blade.php`
- `backend/resources/views/storefront/pickup.blade.php`
- `backend/resources/views/storefront/product.blade.php`
- `backend/resources/views/welcome.blade.php`

### backend/public/css

- `backend/public/css/admin.css`
- `backend/public/css/branding.css`
- `backend/public/css/content.css`
- `backend/public/css/internal.css`
- `backend/public/css/orders.css`
- `backend/public/css/storefront.css`

### backend/public/js

- `backend/public/js/admin.js`
- `backend/public/js/storefront.js`

### backend/database/seeders

- `backend/database/seeders/CheckoutPreviewSeeder.php`
- `backend/database/seeders/DatabaseSeeder.php`
- `backend/database/seeders/DevelopmentSeeder.php`
- `backend/database/seeders/HomepageSeeder.php`
- `backend/database/seeders/OrderOperationsPreviewSeeder.php`
- `backend/database/seeders/RenderDemoSeeder.php`
- `backend/database/seeders/StorefrontPreviewSeeder.php`

### backend/deploy

- `backend/deploy/apache.conf`
- `backend/deploy/create-demo-secrets.php`
- `backend/deploy/php.ini`
- `backend/deploy/start.sh`
- `backend/deploy/validate-environment.php`

## B.4 Dependencias directas resueltas

| Paquete | Rango declarado | Versión en composer.lock |
|---|---|---|
| `php` | `^8.3` | `Requisito de plataforma` |
| `laravel/framework` | `^13.17` | `v13.33.0` |
| `laravel/tinker` | `^3.0` | `v3.0.2` |
| `fakerphp/faker` | `^1.23` | `v1.24.1` |
| `laravel/boost` | `^2.10` | `v2.10.0` |
| `laravel/pail` | `^1.2.5` | `v1.2.7` |
| `laravel/pao` | `^1.0.6` | `v1.1.5` |
| `laravel/pint` | `^1.27` | `v1.32.1` |
| `mockery/mockery` | `^1.6` | `1.6.15` |
| `nunomaduro/collision` | `^8.6` | `v8.9.5` |
| `phpunit/phpunit` | `^12.5.12` | `12.5.35` |

PHP local consultado: 8.4.26; Composer local: 2.10.3; SQLite de la extensión local: 3.53.4; Git local: 2.55.0.windows.3. Estos datos no fijan la versión de los servicios externos ni el parche de futuras imágenes Docker.

## B.5 Documentación de evidencia

- `docs/pendientes-correcciones.md`: mediciones y validación candidata, más revisión focalizada local aprobada sin commit al iniciar el manual.
- `docs/integracion-punto-de-venta.md`: contrato manual y límites del motor.
- `docs/despliegue-demo-render.md`: instalación y verificaciones históricas de la demo.
- Documentos de fases: contexto histórico, subordinado al código actual cuando difieren.
- `backend/app/Services/README.md`: documento antiguo de planificación; no describe el estado implementado completo.
