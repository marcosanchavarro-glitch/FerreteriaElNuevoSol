<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DemoAccessController extends Controller
{
    public function create(): View|RedirectResponse
    {
        abort_unless(config('demo.enabled'), 404);

        if (! config('demo.access_required')) {
            return to_route('home');
        }

        return view('auth.demo');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(config('demo.enabled'), 404);
        if (! config('demo.access_required')) {
            return to_route('home');
        }
        $data = $request->validate(['password' => ['required', 'string', 'max:200']]);
        $hash = (string) config('demo.access_hash');
        if (! Hash::check($data['password'], $hash)) {
            throw ValidationException::withMessages(['password' => 'La clave de acceso no es válida.']);
        }
        $request->session()->regenerate();
        $request->session()->put('demo.access', hash('sha256', $hash));

        return to_route('home');
    }
}
