<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.orders.index', ['orders' => $request->user()->orders()->latest('id')->paginate(10)]);
    }

    public function show(Request $request, int $order): View
    {
        $order = $request->user()->orders()->with(['items', 'statusHistory' => fn ($q) => $q->orderBy('id')])->findOrFail($order);
        Gate::authorize('view', $order);

        return view('account.orders.show', compact('order'));
    }
}
