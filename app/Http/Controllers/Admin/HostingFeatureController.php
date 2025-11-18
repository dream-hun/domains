<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\HostingFeature\CreateHostingFeatureAction;
use App\Actions\HostingFeature\DeleteHostingFeatureAction;
use App\Actions\HostingFeature\ListHostingFeatureAction;
use App\Actions\HostingFeature\UpdateHostingFeatureAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHostingFeatureRequest;
use App\Http\Requests\Admin\UpdateHostingFeatureRequest;
use App\Models\FeatureCategory;
use App\Models\HostingFeature;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class HostingFeatureController extends Controller
{
    public function index(ListHostingFeatureAction $action): View|Factory
    {
        abort_if(Gate::denies('hosting_feature_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $hostingFeatures = $action->handle();

        return view('admin.hosting-features.index', ['hostingFeatures' => $hostingFeatures]);
    }

    public function create(): View|Factory
    {
        abort_if(Gate::denies('hosting_feature_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $featureCategories = FeatureCategory::query()->orderBy('name')->get();

        return view('admin.hosting-features.create', [
            'featureCategories' => $featureCategories,
        ]);
    }

    public function store(StoreHostingFeatureRequest $request, CreateHostingFeatureAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('admin.hosting-features.index')->with('success', 'Hosting feature created successfully.');
    }

    public function edit(HostingFeature $hostingFeature): View|Factory
    {
        abort_if(Gate::denies('hosting_feature_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $hostingFeature->load('featureCategory');
        $featureCategories = FeatureCategory::query()->orderBy('name')->get();

        return view('admin.hosting-features.edit', [
            'hostingFeature' => $hostingFeature,
            'featureCategories' => $featureCategories,
        ]);
    }

    public function update(UpdateHostingFeatureRequest $request, HostingFeature $hostingFeature, UpdateHostingFeatureAction $action): RedirectResponse
    {
        $action->handle($hostingFeature, $request->validated());

        return to_route('admin.hosting-features.index')->with('success', 'Hosting feature updated successfully.');
    }

    public function destroy(HostingFeature $hostingFeature, DeleteHostingFeatureAction $action): RedirectResponse
    {
        abort_if(Gate::denies('hosting_feature_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $action->handle($hostingFeature);

        return to_route('admin.hosting-features.index')->with('success', 'Hosting feature deleted successfully.');
    }
}
