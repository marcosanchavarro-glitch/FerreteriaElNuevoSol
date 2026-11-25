<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ProfileRequest;
use App\Support\AccountSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view($request->routeIs('admin.profile.edit') ? 'account.staff-profile' : 'account.edit', [
            'user' => $request->user(),
            'recentOrders' => $request->user()->can('access-account') ? $request->user()->orders()->latest('id')->limit(5)->get() : collect(),
        ]);
    }

    public function update(ProfileRequest $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $emailChanged = $user->email !== $request->input('email');
        if ($emailChanged) {
            Password::deleteToken($user);
            $user->email_verified_at = null;
        }
        $user->fill($request->safe()->only(['first_name', 'last_name', 'phone', 'email']));
        $user->save();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Tus datos fueron guardados.', 'csrf_token' => csrf_token()])->header('Cache-Control', 'no-store');
        }

        return back()->with('status', 'Tus datos fueron guardados.');
    }

    public function password(ChangePasswordRequest $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $user->password = Hash::make($request->validated('password'));
        $user->setRememberToken(Str::random(60));
        $user->save();
        Password::deleteToken($user);
        $request->session()->regenerate();
        AccountSession::refreshPasswordHash($request, $user);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Contraseña actualizada. Las demás sesiones deberán volver a ingresar.', 'csrf_token' => csrf_token()])->header('Cache-Control', 'no-store');
        }

        return back()->with('status', 'Contraseña actualizada. Las demás sesiones deberán volver a ingresar.');
    }
}
