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
use App\Models\HostingCategory;
use App\Models\HostingFeature;
use App\Models\HostingPlan;
use App\Models\HostingPlanFeature;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class HostingPlanFeatureController extends Controller
{
    public function index(Request $request, ListHostingPlanFeatureAction $action): View|Factory
    {
        abort_if(Gate::denies('hosting_plan_feature_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $selectedCategoryId = $request->filled('hosting_category_id') ? (int) $request->input('hosting_category_id') : null;
        $selectedPlanId = $request->filled('hosting_plan_id') ? (int) $request->input('hosting_plan_id') : null;

        $hostingPlanFeatures = $action->handle($selectedCategoryId, $selectedPlanId);
        $hostingCategories = HostingCategory::query()->orderBy('name')->get();
        $hostingPlans = HostingPlan::query()->orderBy('name')->get();

        return view('admin.hosting-plan-features.index', [
            'hostingPlanFeatures' => $hostingPlanFeatures,
            'hostingCategories' => $hostingCategories,
            'hostingPlans' => $hostingPlans,
            'filters' => [
                'hosting_category_id' => $selectedCategoryId,
                'hosting_plan_id' => $selectedPlanId,
            ],
        ]);
    }

    public function create(): View|Factory
    {
        abort_if(Gate::denies('hosting_plan_feature_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $hostingPlans = HostingPlan::query()->orderBy('name')->get();
        $hostingFeatures = HostingFeature::query()->orderBy('name')->get();
        $hostingCategories = HostingCategory::query()->orderBy('name')->get();

        return view('admin.hosting-plan-features.create', [
            'hostingPlans' => $hostingPlans,
            'hostingFeatures' => $hostingFeatures,
            'hostingCategories' => $hostingCategories,
        ]);
    }

    public function store(StoreHostingPlanFeatureRequest $request, StoreHostingPlanFeatureAction $action): RedirectResponse
    {
        $payload = Arr::except($request->validated(), ['hosting_category_id']);

        $action->handle($payload);

        return to_route('admin.hosting-plan-features.index')->with('success', 'Hosting plan feature created successfully.');
    }

    public function edit(HostingPlanFeature $hostingPlanFeature): View|Factory
    {
        abort_if(Gate::denies('hosting_plan_feature_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $hostingPlanFeature->load(['hostingPlan.category', 'hostingFeature']);
        $hostingPlans = HostingPlan::query()->orderBy('name')->get();
        $hostingFeatures = HostingFeature::query()->orderBy('name')->get();
        $hostingCategories = HostingCategory::query()->orderBy('name')->get();

        return view('admin.hosting-plan-features.edit', [
            'hostingPlanFeature' => $hostingPlanFeature,
            'hostingPlans' => $hostingPlans,
            'hostingFeatures' => $hostingFeatures,
            'hostingCategories' => $hostingCategories,
        ]);
    }

    public function update(UpdateHostingPlanFeatureRequest $request, HostingPlanFeature $hostingPlanFeature, UpdateHostingPlanFeatureAction $action): RedirectResponse
    {
        $payload = Arr::except($request->validated(), ['hosting_category_id']);

        $action->handle($hostingPlanFeature, $payload);

        return to_route('admin.hosting-plan-features.index')->with('success', 'Hosting plan feature updated successfully.');
    }

    public function destroy(HostingPlanFeature $hostingPlanFeature, DeleteHostingPlanFeatureAction $action): RedirectResponse
    {
        abort_if(Gate::denies('hosting_plan_feature_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $action->handle($hostingPlanFeature);

        return to_route('admin.hosting-plan-features.index')->with('success', 'Hosting plan feature deleted successfully.');
    }
}
