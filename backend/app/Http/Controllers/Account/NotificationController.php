<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.notifications', ['notifications' => $request->user()->notifications()->visible()->with('order:id,user_id')->latest('notifications.id')->paginate(15)]);
    }

    public function read(Request $request, int $notification): RedirectResponse
    {
        $owned = $request->user()->notifications()->visible()->findOrFail($notification);
        $request->user()->notifications()->wherePivotNull('read_at')->updateExistingPivot($owned->id, ['read_at' => now()]);

        return back()->with('status', 'Notificación marcada como leída.');
    }

    public function readAll(Request $request): RedirectResponse
    {
        $ids = $request->user()->notifications()->visible()->select('notifications.id');
        DB::table('notification_user')->where('user_id', $request->user()->id)
            ->whereNull('read_at')->whereIn('notification_id', $ids)->update(['read_at' => now()]);

        return back()->with('status', 'Todas tus notificaciones están leídas.');
    }
}
