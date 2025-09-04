<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSettingRequest;
use App\Http\Requests\Admin\UpdateSettingRequest;
use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class SettingController extends Controller
{
    public function index(): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
    {
        abort_if(Gate::denies('setting_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $settings = Setting::all();

        return view('admin.settings.index', ['settings' => $settings]);
    }

    public function create(): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
    {
        abort_if(Gate::denies('setting_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.settings.create');
    }

    public function store(StoreSettingRequest $request)
    {
        Setting::create($request->all());

        return redirect()->route('admin.settings.index');
    }

    public function edit(Setting $setting): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
    {
        abort_if(Gate::denies('setting_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.settings.edit', ['setting' => $setting]);
    }

    public function update(UpdateSettingRequest $request, Setting $setting)
    {
        $setting->update($request->all());

        return redirect()->route('admin.settings.index');
    }

    public function show(Setting $setting): \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
    {
        abort_if(Gate::denies('setting_show'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.settings.show', ['setting' => $setting]);
    }

    public function destroy(Setting $setting): \Illuminate\Http\RedirectResponse
    {
        abort_if(Gate::denies('setting_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $setting->delete();

        return back();
    }
}
