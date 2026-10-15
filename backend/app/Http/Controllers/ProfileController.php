<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ProfileRequest;
use App\Support\AccountSession;
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
        return view($request->user()->can('access-admin') ? 'account.staff-profile' : 'account.edit', [
            'user' => $request->user(),
            'recentOrders' => $request->user()->can('access-account') ? $request->user()->orders()->latest('id')->limit(5)->get() : collect(),
        ]);
    }

    public function update(ProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $emailChanged = $user->email !== $request->input('email');
        if ($emailChanged) {
            Password::deleteToken($user);
            $user->email_verified_at = null;
        }
        $user->fill($request->safe()->only(['first_name', 'last_name', 'phone', 'email']));
        $user->save();

        return back()->with('status', 'Tus datos fueron guardados.');
    }

    public function password(ChangePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->password = Hash::make($request->validated('password'));
        $user->setRememberToken(Str::random(60));
        $user->save();
        Password::deleteToken($user);
        $request->session()->regenerate();
        AccountSession::refreshPasswordHash($request, $user);

        return back()->with('status', 'Contraseña actualizada. Las demás sesiones deberán volver a ingresar.');
    }
}
