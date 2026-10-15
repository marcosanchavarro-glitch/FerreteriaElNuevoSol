<?php

use App\Http\Controllers\Account\NotificationController;
use App\Http\Controllers\Account\OrderController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CategoryImageController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductCategoryController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductImageController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\VariantController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\Auth\SessionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CatalogController;
use App\Http\Controllers\Storefront\CheckoutController;
use App\Http\Controllers\Storefront\MediaController;
use Illuminate\Support\Facades\Route;

Route::get('/acceso-demo', [App\Http\Controllers\DemoAccessController::class, 'create'])->name('demo.login');
Route::post('/acceso-demo', [App\Http\Controllers\DemoAccessController::class, 'store'])->middleware('throttle:demo-access')->name('demo.enter');

Route::get('/', [CatalogController::class, 'home'])->name('home');
Route::get('/productos', [CatalogController::class, 'index'])->name('store.products');
Route::get('/productos/{slug}', [CatalogController::class, 'show'])->name('store.product');
Route::get('/categorias/{slug}', [CatalogController::class, 'index'])->name('store.category');
Route::get('/imagenes/productos/{image}', [MediaController::class, 'product'])->whereNumber('image')->name('store.images.product');
Route::get('/imagenes/categorias/{slug}', [MediaController::class, 'category'])->name('store.images.category');
Route::middleware('guest')->group(function (): void {
    Route::get('/registro', [RegistrationController::class, 'create'])->name('register');
    Route::post('/registro', [RegistrationController::class, 'store'])->middleware('throttle:registration');
    Route::get('/login', [SessionController::class, 'create'])->name('login');
    Route::post('/login', [SessionController::class, 'store'])->middleware('throttle:authentication');
    Route::get('/recuperar-contrasena', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/recuperar-contrasena', [PasswordResetController::class, 'email'])->middleware('throttle:password-recovery')->name('password.email');
    Route::get('/restablecer-contrasena/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/restablecer-contrasena', [PasswordResetController::class, 'update'])->middleware('throttle:password-recovery')->name('password.store');
});
Route::middleware(['auth', 'auth.session'])->group(function (): void {
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');
    Route::get('/mi-cuenta', [ProfileController::class, 'edit'])->middleware('can:access-account')->name('account.edit');
    Route::patch('/mi-cuenta', [ProfileController::class, 'update'])->middleware('throttle:authentication')->middleware('can:access-account')->name('account.update');
    Route::put('/mi-cuenta/contrasena', [ProfileController::class, 'password'])->middleware('throttle:authentication')->middleware('can:access-account')->name('account.password');
    Route::prefix('mi-cuenta')->name('account.')->middleware('can:access-account')->group(function (): void {
        Route::get('pedidos', [OrderController::class, 'index'])->name('orders.index');
        Route::get('pedidos/{order}', [OrderController::class, 'show'])->whereNumber('order')->name('orders.show');
        Route::get('notificaciones', [NotificationController::class, 'index'])->name('notifications.index');
        Route::post('notificaciones/leidas', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::patch('notificaciones/{notification}/leida', [NotificationController::class, 'read'])->whereNumber('notification')->name('notifications.read');
    });
    Route::prefix('admin')->name('admin.')->middleware('can:access-admin')->group(function (): void {
        Route::get('/', DashboardController::class)->name('home');
        Route::get('activity-logs', [ActivityLogController::class, 'index'])->middleware('can:view-activity')->name('activity.index');
        Route::get('activity-logs/{activityLog}', [ActivityLogController::class, 'show'])->whereNumber('activityLog')->middleware('can:view-activity')->name('activity.show');
        Route::get('settings', [SettingsController::class, 'edit'])->middleware('can:manage-settings')->name('settings.edit');
        Route::put('settings', [SettingsController::class, 'update'])->middleware('can:manage-settings')->name('settings.update');
        foreach (['sections', 'banners'] as $kind) {
            Route::prefix('content/'.$kind)->name($kind.'.')->group(function () use ($kind): void {
                $controller = ContentController::class;
                Route::get('/', [$controller, 'index'])->defaults('kind', $kind)->name('index');
                Route::get('/create', [$controller, 'edit'])->defaults('kind', $kind)->name('create');
                Route::post('/', [$controller, 'save'])->defaults('kind', $kind)->name('store');
                Route::get('/{id}/edit', [$controller, 'edit'])->whereNumber('id')->defaults('kind', $kind)->name('edit');
                Route::put('/{id}', [$controller, 'save'])->whereNumber('id')->defaults('kind', $kind)->name('update');
                Route::post('/{id}/move', [$controller, 'move'])->whereNumber('id')->defaults('kind', $kind)->name('move');
                Route::patch('/{id}/visibility', [$controller, 'visibility'])->whereNumber('id')->defaults('kind', $kind)->name('visibility');
                Route::get('/{id}/image', [$controller, 'image'])->whereNumber('id')->defaults('kind', $kind)->name('image');
                if ($kind === 'sections') {
                    Route::delete('/{id}/image', [$controller, 'removeImage'])->whereNumber('id')->defaults('kind', $kind)->name('image.destroy');
                }
            });
        }
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profile', [ProfileController::class, 'update'])->middleware('throttle:authentication')->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'password'])->middleware('throttle:authentication')->name('profile.password');
        Route::get('orders', [App\Http\Controllers\Admin\OrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [App\Http\Controllers\Admin\OrderController::class, 'show'])->whereNumber('order')->name('orders.show');
        Route::patch('orders/{order}/status', [App\Http\Controllers\Admin\OrderController::class, 'update'])->whereNumber('order')->name('orders.update');
        Route::resource('products', ProductController::class)->except('destroy');
        Route::resource('categories', CategoryController::class)->except(['show', 'destroy']);
        Route::post('categories/{category}/image', [CategoryImageController::class, 'store'])->name('categories.image.store');
        Route::get('categories/{category}/image', [CategoryImageController::class, 'show'])->name('categories.image.show');
        Route::delete('categories/{category}/image', [CategoryImageController::class, 'destroy'])->name('categories.image.destroy');
        Route::scopeBindings()->group(function (): void {
            Route::resource('products.variants', VariantController::class)->except('destroy');
            Route::get('products/{product}/categories', [ProductCategoryController::class, 'edit'])->name('products.categories.edit');
            Route::put('products/{product}/categories', [ProductCategoryController::class, 'update'])->name('products.categories.update');
            Route::resource('products.images', ProductImageController::class)->only(['index', 'store', 'show', 'update', 'destroy']);
            Route::post('products/{product}/images/{image}/primary', [ProductImageController::class, 'primary'])->name('products.images.primary');
            Route::post('products/{product}/images/{image}/move', [ProductImageController::class, 'move'])->name('products.images.move');
        });
    });
});

Route::middleware('auth.session')->group(function (): void {
    Route::get('/carrito', [CartController::class, 'index'])->block()->name('cart.index');
    Route::post('/carrito', [CartController::class, 'store'])->block()->name('cart.store');
    Route::patch('/carrito/{key}', [CartController::class, 'update'])->where('key', '[0-9]+-[0-9]+')->block()->name('cart.update');
    Route::delete('/carrito/{key}', [CartController::class, 'destroy'])->where('key', '[0-9]+-[0-9]+')->block()->name('cart.destroy');
    Route::get('/checkout', [CheckoutController::class, 'create'])->block()->name('checkout.create');
    Route::post('/checkout', [CheckoutController::class, 'store'])->block(30, 30)->name('checkout.store');
    Route::get('/pedido-confirmado/{number}', [CheckoutController::class, 'confirmation'])->name('checkout.confirmation');
});

Route::get('/imagenes/contenido/{kind}/{id}', [ContentController::class, 'image'])->whereIn('kind', ['sections', 'banners'])->whereNumber('id')->name('store.images.content');
