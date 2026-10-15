<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\AccountSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $key = 'login:'.hash('sha256', $request->string('email')->toString().'|'.$request->ip());
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages(['email' => 'Demasiados intentos. Volvé a intentar en '.RateLimiter::availableIn($key).' segundos.']);
        }
        if (! Auth::guard('web')->attempt([...$request->safe()->only(['email', 'password']), 'active' => true], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages(['email' => 'No pudimos iniciar sesión con esos datos.']);
        }
        RateLimiter::clear($key);
        $request->session()->regenerate();
        AccountSession::refreshPasswordHash($request, $request->user());

        return redirect()->route($request->user()->can('access-admin') ? 'admin.home' : 'account.edit');
    }

    public function destroy(Request $request): RedirectResponse
    {
        AccountSession::end($request);

        return redirect()->route('login');
    }
}
