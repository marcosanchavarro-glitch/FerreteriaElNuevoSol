<?php

namespace App\Http\Controllers\Admin;

use App\Enums\HomepageSectionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\ContentRequest;
use App\Models\Banner;
use App\Models\HomepageSection;
use App\Services\Audit\ActivityLogService;
use App\Services\Catalog\ImageStorage;
use App\Services\Content\ContentManager;
use App\Services\Storefront\PublicImages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContentController extends Controller
{
    private function model(Request $request): HomepageSection|Banner
    {
        abort_unless(in_array($request->route('kind'), ['sections', 'banners'], true), 404);
        $class = $request->route('kind') === 'banners' ? Banner::class : HomepageSection::class;

        return $request->route('id') ? $class::findOrFail($request->route('id')) : new $class;
    }

    public function index(Request $request): View
    {
        $model = $this->model($request);
        Gate::authorize('viewAny', $model::class);

        return view('admin.content.index', ['records' => $model->newQuery()->orderBy('sort_order')->orderBy('id')->paginate(20), 'kind' => $request->route('kind')]);
    }

    public function edit(Request $request): View
    {
        $record = $this->model($request);
        Gate::authorize($record->exists ? 'update' : 'create', $record->exists ? $record : $record::class);

        return view('admin.content.edit', ['record' => $record, 'kind' => $request->route('kind'), 'types' => HomepageSectionType::cases()]);
    }

    public function save(ContentRequest $request, ContentManager $manager): RedirectResponse
    {
        $record = $this->model($request);
        Gate::authorize($record->exists ? 'update' : 'create', $record->exists ? $record : $record::class);
        $data = $request->safe()->except(['image', 'max_items', 'confirm_deactivate']);
        if ($record instanceof HomepageSection) {
            $data['settings'] = ['max_items' => (int) ($request->validated('max_items') ?: 6)];
        }
        try {
            $manager->save($record, $data, $request->file('image'));
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages(['image' => 'No se pudo guardar el contenido. Intentá nuevamente.']);
        }

        return redirect()->route('admin.'.$request->route('kind').'.edit', $record->id)->with('status', $record instanceof Banner ? 'Banner guardado correctamente.' : 'Sección guardada correctamente.');
    }

    public function move(Request $request, ContentManager $manager): RedirectResponse
    {
        $record = $this->model($request);
        Gate::authorize('update', $record);
        $data = $request->validate(['direction' => ['required', 'in:up,down']]);
        $manager->move($record, $data['direction']);

        return back()->with('status', 'Orden actualizado.');
    }

    public function visibility(Request $request): RedirectResponse
    {
        $record = $this->model($request);
        Gate::authorize('update', $record);
        $data = $request->validate(['active' => ['required', 'boolean'], 'confirm' => ['required', 'accepted']]);
        DB::transaction(function () use ($record, $data): void {
            $record->newQuery()->whereKey($record->id)->update(['updated_at' => now()]);
            $record->refresh();
            $before = app(ActivityLogService::class)->snapshot($record);
            $record->update(['active' => (bool) $data['active']]);
            app(ActivityLogService::class)->changed($record, $before, 'visibility');
        });

        return back()->with('status', $data['active'] ? 'Contenido activado.' : 'Contenido desactivado.');
    }

    public function removeImage(Request $request, ContentManager $manager): RedirectResponse
    {
        $record = $this->model($request);
        Gate::authorize('update', $record);
        abort_unless($record instanceof HomepageSection, 404);
        $request->validate(['confirm' => ['required', 'accepted']]);
        try {
            $manager->removeImage($record);
        } catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages(['image' => 'No se pudo eliminar la imagen. Intentá nuevamente.']);
        }

        return back()->with('status', 'Imagen eliminada correctamente.');
    }

    public function image(Request $request, ImageStorage $storage, PublicImages $images): StreamedResponse
    {
        $record = $this->model($request);
        if ($request->routeIs('admin.*')) {
            Gate::authorize('update', $record);
        } else {
            abort_unless($record->active, 404);
            if ($record instanceof Banner) {
                abort_if(($record->starts_at && $record->starts_at->isFuture()) || ($record->ends_at && $record->ends_at->lte(now())), 404);
                abort_if(HomepageSection::exists() && ! HomepageSection::where('type', 'banner')->where('active', true)->exists(), 404);
            }
        }
        $folder = $request->route('kind').'/'.$record->id;
        abort_unless($images->available($record->image_path, $folder), 404);

        return $storage->response($record->image_path, $folder);
    }
}
