<?php

namespace App\Http\Middleware;

use App\Support\AccountSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && ! $request->user()->active) {
            AccountSession::end($request);
            if ($request->expectsJson()) {
                abort(403, 'La cuenta está inactiva.');
            }

            return redirect()->route('login')->withErrors(['email' => 'La cuenta está inactiva.']);
        }

        return $next($request);
    }
}
