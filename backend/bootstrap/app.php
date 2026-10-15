<?php

use App\Http\Middleware\DemoAccess;
use App\Http\Middleware\EnsureActiveUser;
use App\Services\Audit\ActivityLogService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        if (getenv('RENDER') === 'true') {
            $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
        }
        $middleware->appendToGroup('web', DemoAccess::class);
        $middleware->appendToPriorityList(StartSession::class, DemoAccess::class);
        $middleware->appendToGroup('web', EnsureActiveUser::class);
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => route($request->user()?->can('access-admin') ? 'admin.home' : 'account.edit'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if ($exception->getStatusCode() === 403 && ! $request->isMethodSafe() && $request->user()?->can('access-admin') && $request->routeIs('admin.*')) {
                $route = $request->route()->getName();
                $module = str_contains($route, '.images.') ? 'images' : (str_contains($route, '.variants.') ? 'variants' : explode('.', $route)[1]);
                if (isset(ActivityLogService::MODULES[$module])) {
                    app(ActivityLogService::class)->record($module, 'denied', null, [], [], $request->user(), 'admin', 'rejected');
                }
            }

            return null;
        });
        $exceptions->dontFlash(['current_password', 'password', 'password_confirmation', 'token']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
