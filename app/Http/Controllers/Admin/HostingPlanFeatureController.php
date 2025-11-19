<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\HostingPlanFeature\DeleteHostingPlanFeatureAction;
use App\Actions\HostingPlanFeature\ListHostingPlanFeatureAction;
use App\Actions\HostingPlanFeature\StoreHostingPlanFeatureAction;
use App\Actions\HostingPlanFeature\UpdateHostingPlanFeatureAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHostingPlanFeatureRequest;
use App\Http\Requests\Admin\UpdateHostingPlanFeatureRequest;
use App\Models\HostingFeature;
use App\Models\HostingPlan;
use App\Models\HostingPlanFeature;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class HostingPlanFeatureController extends Controller
{
    public function index(ListHostingPlanFeatureAction $action): View|Factory
    {
        abort_if(Gate::denies('hosting_plan_feature_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $hostingPlanFeatures = $action->handle();

        return view('admin.hosting-plan-features.index', ['hostingPlanFeatures' => $hostingPlanFeatures]);
    }

    public function create(): View|Factory
    {
        abort_if(Gate::denies('hosting_plan_feature_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $hostingPlans = HostingPlan::query()->orderBy('name')->get();
        $hostingFeatures = HostingFeature::query()->orderBy('name')->get();

        return view('admin.hosting-plan-features.create', [
            'hostingPlans' => $hostingPlans,
            'hostingFeatures' => $hostingFeatures,
        ]);
    }

    public function store(StoreHostingPlanFeatureRequest $request, StoreHostingPlanFeatureAction $action): RedirectResponse
    {
        $action->handle($request->validated());

        return to_route('admin.hosting-plan-features.index')->with('success', 'Hosting plan feature created successfully.');
    }

    public function edit(HostingPlanFeature $hostingPlanFeature): View|Factory
    {
        abort_if(Gate::denies('hosting_plan_feature_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $hostingPlanFeature->load(['hostingPlan', 'hostingFeature']);
        $hostingPlans = HostingPlan::query()->orderBy('name')->get();
        $hostingFeatures = HostingFeature::query()->orderBy('name')->get();

        return view('admin.hosting-plan-features.edit', [
            'hostingPlanFeature' => $hostingPlanFeature,
            'hostingPlans' => $hostingPlans,
            'hostingFeatures' => $hostingFeatures,
        ]);
    }

    public function update(UpdateHostingPlanFeatureRequest $request, HostingPlanFeature $hostingPlanFeature, UpdateHostingPlanFeatureAction $action): RedirectResponse
    {
        $action->handle($hostingPlanFeature, $request->validated());

        return to_route('admin.hosting-plan-features.index')->with('success', 'Hosting plan feature updated successfully.');
    }

    public function destroy(HostingPlanFeature $hostingPlanFeature, DeleteHostingPlanFeatureAction $action): RedirectResponse
    {
        abort_if(Gate::denies('hosting_plan_feature_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $action->handle($hostingPlanFeature);

        return to_route('admin.hosting-plan-features.index')->with('success', 'Hosting plan feature deleted successfully.');
    }
}
