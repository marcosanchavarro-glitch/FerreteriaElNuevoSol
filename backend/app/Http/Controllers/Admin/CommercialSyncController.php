<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SyncRun;
use App\Services\Catalog\CommercialSyncService;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CommercialSyncController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate(['run' => ['nullable', 'integer', 'min:1']]);
        $query = SyncRun::where('source', CommercialSyncService::SOURCE);
        $run = $request->filled('run') ? (clone $query)->findOrFail($request->integer('run')) : (clone $query)->latest('id')->first();

        return view('admin.sync', ['run' => $run, 'history' => $query->latest('id')->limit(10)->get()]);
    }

    public function preview(Request $request, CommercialSyncService $service): RedirectResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:256', 'extensions:json', 'mimetypes:application/json,text/plain']]);
        $run = $service->preview($service->decode($request->file('file')->get()));

        return to_route('admin.sync.index', ['run' => $run->id]);
    }

    public function apply(Request $request, SyncRun $syncRun, CommercialSyncService $service): RedirectResponse
    {
        $request->validate(['confirm' => ['required', 'accepted']]);
        try {
            $run = $service->apply($syncRun);
        } catch (LockTimeoutException) {
            throw ValidationException::withMessages(['preview' => 'Hay otra importación en curso. Volvé a intentar en unos segundos.']);
        }

        return to_route('admin.sync.index', ['run' => $run->id])->with('status', $run->status->value === 'completed' ? 'Importación finalizada. Revisá los registros omitidos.' : 'Importación revertida; no se modificaron productos.');
    }
}
