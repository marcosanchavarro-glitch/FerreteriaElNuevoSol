<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
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

    public function read(Request $request, int $notification): RedirectResponse|JsonResponse
    {
        $owned = $request->user()->notifications()->visible()->findOrFail($notification);
        $request->user()->notifications()->wherePivotNull('read_at')->updateExistingPivot($owned->id, ['read_at' => now()]);

        if ($request->expectsJson()) {
            return $this->result($request, 'Notificación marcada como leída.');
        }

        return back()->with('status', 'Notificación marcada como leída.');
    }

    public function readAll(Request $request): RedirectResponse|JsonResponse
    {
        $ids = $request->user()->notifications()->visible()->select('notifications.id');
        DB::table('notification_user')->where('user_id', $request->user()->id)
            ->whereNull('read_at')->whereIn('notification_id', $ids)->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return $this->result($request, 'Todas tus notificaciones están leídas.');
        }

        return back()->with('status', 'Todas tus notificaciones están leídas.');
    }

    private function result(Request $request, string $message): JsonResponse
    {
        return response()->json(['message' => $message, 'csrf_token' => csrf_token(), 'unread' => $request->user()->notifications()->visible()->wherePivotNull('read_at')->count()])->header('Cache-Control', 'no-store');
    }
}
