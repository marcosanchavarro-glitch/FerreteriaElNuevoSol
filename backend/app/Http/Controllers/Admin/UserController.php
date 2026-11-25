<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\ActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $search = trim($data['q'] ?? '');
        $users = User::query()->select('id', 'first_name', 'last_name', 'email', 'role', 'active')
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q->whereLike('first_name', '%'.$search.'%')->orWhereLike('last_name', '%'.$search.'%')->orWhereLike('email', '%'.$search.'%')))
            ->orderBy('id')->paginate(15)->withQueryString();

        return view('admin.users.index', compact('users', 'search'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_if($request->hasAny(['id', 'user_id', 'active', 'email', 'password', 'remember_token']), 403);
        $data = $request->validate(['role' => ['required', Rule::enum(UserRole::class)], 'expected_role' => ['required', Rule::enum(UserRole::class)]]);
        DB::transaction(function () use ($request, $user, $data): void {
            $locked = User::whereIn('id', [$request->user()->id, $user->id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $actor = $locked->get($request->user()->id);
            $target = $locked->get($user->id);
            abort_unless($actor?->active && $actor->role === UserRole::Admin && $target, 403);
            if ($actor->id === $target->id) {
                throw ValidationException::withMessages(['role' => 'No podés cambiar el rol de tu propia cuenta. Pedile a otro administrador que lo revise.']);
            }
            if ($target->role->value !== $data['expected_role']) {
                throw ValidationException::withMessages(['role' => 'El rol cambió desde que abriste esta pantalla. Recargá y revisalo antes de guardar.']);
            }
            $audit = app(ActivityLogService::class);
            $before = $audit->snapshot($target);
            $target->role = UserRole::from($data['role']);
            $target->save();
            $audit->changed($target, $before, 'updated');
        }, 3);

        return back()->with('status', 'Rol actualizado. Los permisos se aplican en la próxima solicitud del usuario.');
    }
}
