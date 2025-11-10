<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSettingRequest;
use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Models\Setting;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class SettingController extends Controller
{
    public function index(): View|Factory
    {
        abort_if(Gate::denies('setting_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $settings = Setting::all();

        return view('admin.settings.index', ['settings' => $settings]);
    }

    public function create(): View|Factory
    {
        abort_if(Gate::denies('setting_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.settings.create');
    }

    public function store(StoreSettingRequest $request): Redirector|RedirectResponse
    {
        Setting::query()->create($request->all());

        return to_route('admin.settings.index');
    }

    public function edit(Setting $setting): View|Factory
    {
        abort_if(Gate::denies('setting_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.settings.edit', ['setting' => $setting]);
    }

    public function update(UpdateSettingRequest $request, Setting $setting): Redirector|RedirectResponse
    {
        $setting->update($request->all());

        return to_route('admin.settings.index');
    }

    public function show(Setting $setting): View|Factory
    {
        abort_if(Gate::denies('setting_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.settings.show', ['setting' => $setting]);
    }

    public function destroy(Setting $setting): RedirectResponse
    {
        abort_if(Gate::denies('setting_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $setting->delete();

        return back();
    }
}
