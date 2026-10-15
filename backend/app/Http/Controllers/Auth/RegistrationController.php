<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Support\AccountSession;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = new User($request->safe()->only(['first_name', 'last_name', 'email', 'phone']));
        $user->password = Hash::make($request->validated('password'));
        $user->role = UserRole::Customer;
        $user->active = true;
        $user->save();
        event(new Registered($user));
        AccountSession::start($request, $user);

        return redirect()->route('account.edit');
    }
}
