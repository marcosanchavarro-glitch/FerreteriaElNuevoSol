<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiteSettingsRequest;
use App\Models\Setting;
use App\Services\Audit\ActivityLogService;
use App\Services\Content\SiteSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(SiteSettings $settings): View
    {
        return view('admin.settings', ['values' => $settings->all(), 'fields' => SiteSettings::FIELDS]);
    }

    public function update(SiteSettingsRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            Setting::query()->whereIn('key', array_keys(SiteSettings::FIELDS))->update(['updated_at' => now()]);
            $before = app(SiteSettings::class)->all();
            foreach ($request->validated() as $key => $value) {
                Setting::updateOrCreate(['key' => $key], ['value' => $value ?? '']);
            }
            app(ActivityLogService::class)->record('settings', 'updated', 'site', $before, app(SiteSettings::class)->all());
        });

        return back()->with('status', 'Configuración actualizada correctamente.');
    }
}
