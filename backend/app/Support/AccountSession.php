<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountSession
{
    public static function start(Request $request, User $user, bool $remember = false): void
    {
        Auth::guard('web')->login($user, $remember);
        $request->session()->regenerate();
        self::refreshPasswordHash($request, $user);
    }

    public static function refreshPasswordHash(Request $request, User $user): void
    {
        $request->session()->put('password_hash_web', Auth::guard('web')->hashPasswordForCookie($user->getAuthPassword()));
    }

    public static function end(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
