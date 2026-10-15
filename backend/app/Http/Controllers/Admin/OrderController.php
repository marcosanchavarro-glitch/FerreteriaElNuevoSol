<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderFilterRequest;
use App\Http\Requests\Admin\OrderStatusRequest;
use App\Models\Order;
use App\Services\Orders\OrderStatusService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(OrderFilterRequest $request): View
    {
        Gate::authorize('viewAny', Order::class);
        $filters = $request->validated();
        $query = Order::query();
        foreach (preg_split('/\s+/u', trim($filters['q'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) as $term) {
            $query->where(function (Builder $search) use ($term): void {
                foreach (['order_number', 'customer_first_name', 'customer_last_name', 'customer_phone', 'customer_email'] as $column) {
                    $search->orWhereLike($column, '%'.$term.'%');
                }
            });
        }
        if ($filters['status'] ?? null) {
            $query->where('status', $filters['status']);
        }
        if ($filters['from'] ?? null) {
            $query->where('created_at', '>=', CarbonImmutable::parse($filters['from'], config('shop.timezone'))->startOfDay()->utc());
        }
        if ($filters['to'] ?? null) {
            $query->where('created_at', '<', CarbonImmutable::parse($filters['to'], config('shop.timezone'))->addDay()->startOfDay()->utc());
        }
        if (($filters['customer'] ?? null) === 'guest') {
            $query->whereNull('user_id');
        } elseif (($filters['customer'] ?? null) === 'registered') {
            $query->whereNotNull('user_id');
        }

        $counts = Order::select('status')->selectRaw('count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $hasFilters = collect($filters)->except('page')->contains(fn ($value) => $value !== null && trim((string) $value) !== '');

        return view('admin.orders.index', [
            'orders' => $query->latest('id')->paginate(20, total: $hasFilters ? null : (int) $counts->sum())->withQueryString(), 'filters' => $filters,
            'counts' => $counts,
        ]);
    }

    public function show(Order $order): View
    {
        Gate::authorize('view', $order);
        $order->load(['items', 'statusHistory' => fn ($q) => $q->orderBy('id'), 'statusHistory.changedBy']);

        return view('admin.orders.show', compact('order'));
    }

    public function update(OrderStatusRequest $request, Order $order, OrderStatusService $service): RedirectResponse
    {
        $service->change($order, $request->user(), OrderStatus::from($request->validated('expected_status')), OrderStatus::from($request->validated('status')));

        return to_route('admin.orders.show', $order)->with('status', 'Estado actualizado. El historial y el aviso interno quedaron guardados cuando corresponde.');
    }
}
