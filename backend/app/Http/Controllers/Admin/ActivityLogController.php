<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Services\Audit\ActivityLogService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'actor' => ['nullable', 'integer', 'min:1'], 'action' => ['nullable', Rule::in(array_keys(ActivityLogService::ACTIONS))],
            'module' => ['nullable', Rule::in(array_keys(ActivityLogService::MODULES))], 'entity' => ['nullable', 'string', 'max:64'],
            'result' => ['nullable', Rule::in(['success', 'rejected', 'unknown'])], 'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', ...($request->filled('from') ? ['after_or_equal:from'] : [])], 'q' => ['nullable', 'string', 'max:100'],
        ]);
        $query = ActivityLog::query();
        foreach (['actor' => 'actor_id', 'action' => 'action', 'module' => 'module', 'entity' => 'entity_id', 'result' => 'result'] as $filter => $column) {
            if (isset($filters[$filter]) && $filters[$filter] !== '') {
                $query->where($column, $filters[$filter]);
            }
        }
        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', CarbonImmutable::parse($filters['from'], config('shop.timezone'))->startOfDay()->utc());
        }
        if (! empty($filters['to'])) {
            $query->where('created_at', '<', CarbonImmutable::parse($filters['to'], config('shop.timezone'))->addDay()->startOfDay()->utc());
        }
        if (! empty($filters['q'])) {
            $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], trim($filters['q'])).'%';
            $query->where(fn ($q) => $q->whereRaw("actor_name LIKE ? ESCAPE '\\'", [$term])->orWhereRaw("entity_id LIKE ? ESCAPE '\\'", [$term]));
        }

        return view('admin.activity.index', ['logs' => $query->orderByDesc('created_at')->orderByDesc('id')->paginate(20)->withQueryString(), 'filters' => $filters]);
    }

    public function show(ActivityLog $activityLog, ActivityLogService $audit): View
    {
        // Legacy metadata is deliberately not exposed: it predates the snapshot allowlist.
        $before = $audit->sanitize($activityLog->module, $activityLog->before_values ?? []);
        $after = $audit->sanitize($activityLog->module, $activityLog->after_values ?? []);

        return view('admin.activity.show', ['log' => $activityLog, 'before' => $before, 'after' => $after, 'fields' => array_unique([...array_keys($before), ...array_keys($after)])]);
    }
}
