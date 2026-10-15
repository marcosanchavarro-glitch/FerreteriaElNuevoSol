<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\PasswordEmailRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function request(): View
    {
        return view('auth.forgot-password');
    }

    public function email(PasswordEmailRequest $request): RedirectResponse
    {
        if (config('demo.enabled')) {
            return back()->with('status', 'La recuperación por correo está deshabilitada en la demostración. Contactá al administrador de la demo.');
        }
        Password::sendResetLink([...$request->safe()->only(['email']), 'active' => true]);

        return back()->with('status', 'Si hay una cuenta activa con ese email, se solicitará el enlace de recuperación.');
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', ['token' => $token, 'email' => is_string($request->query('email')) ? $request->query('email') : '']);
    }

    public function update(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset([...$request->safe()->only(['email', 'token', 'password', 'password_confirmation']), 'active' => true], function (User $user, string $password): void {
            $user->password = Hash::make($password);
            $user->setRememberToken(Str::random(60));
            $user->save();
            event(new PasswordReset($user));
        });
        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => 'El enlace no es válido o venció. Solicitá uno nuevo.']);
        }

        return redirect()->route('login')->with('status', 'Contraseña restablecida. Ya podés iniciar sesión.');
    }
}
