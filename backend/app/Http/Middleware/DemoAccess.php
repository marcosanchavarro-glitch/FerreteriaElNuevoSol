<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DemoAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('demo.enabled')) {
            return $next($request);
        }

        $hash = (string) config('demo.access_hash');
        abort_unless(strlen($hash) >= 60, 503, 'El acceso a la demo todavía no está configurado.');

        if (! $request->routeIs('demo.*') && ! hash_equals(hash('sha256', $hash), (string) $request->session()->get('demo.access', ''))) {
            $response = $request->expectsJson()
                ? response()->json(['message' => 'Ingresá primero a la demostración.'], 403)
                : to_route('demo.login');
        } else {
            $response = $next($request);
        }

        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
