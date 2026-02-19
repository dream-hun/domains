<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Hosting\Plan\DeletePlanAction;
use App\Actions\Hosting\Plan\ListPlanAction;
use App\Actions\Hosting\Plan\StorePlanAction;
use App\Actions\Hosting\Plan\UpdatePlanAction;
use App\Enums\Hosting\HostingPlanStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHostingPlanRequest;
use App\Http\Requests\UpdateHostingPlanRequest;
use App\Models\HostingCategory;
use App\Models\HostingPlan;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

final class HostingPlanController extends Controller
{
    public function __construct(
        private readonly ListPlanAction $listPlanAction,
        private readonly StorePlanAction $storePlanAction,
        private readonly UpdatePlanAction $updatePlanAction,
        private readonly DeletePlanAction $deletePlanAction
    ) {}

    public function index(): View|Factory
    {
        abort_if(Gate::denies('hosting_plan_access'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $selectedCategoryId = request()->filled('category_id') ? (int) request()->input('category_id') : null;
        $plans = $this->listPlanAction->handle($selectedCategoryId);
        $categories = $this->categories();

        return view('admin.hosting.plans.index', [
            'plans' => $plans,
            'statuses' => HostingPlanStatus::cases(),
            'categories' => $categories,
            'filters' => [
                'category_id' => $selectedCategoryId,
            ],
        ]);
    }

    public function create(): View|Factory
    {
        abort_if(Gate::denies('hosting_plan_create'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.hosting.plans.create', [
            'categories' => $this->categories(),
            'statuses' => HostingPlanStatus::cases(),
        ]);
    }

    public function store(StoreHostingPlanRequest $request): RedirectResponse
    {
        $this->storePlanAction->handle($request->validated());

        return to_route('admin.hosting-plans.index')
            ->with('success', 'Hosting plan created successfully.');
    }

    public function edit(HostingPlan $hostingPlan): View|Factory
    {
        abort_if(Gate::denies('hosting_plan_edit'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        return view('admin.hosting.plans.edit', [
            'plan' => $hostingPlan,
            'categories' => $this->categories(),
            'statuses' => HostingPlanStatus::cases(),
        ]);
    }

    public function update(UpdateHostingPlanRequest $request, HostingPlan $hostingPlan): RedirectResponse
    {
        $this->updatePlanAction->handle($hostingPlan, $request->validated());

        return to_route('admin.hosting-plans.index')
            ->with('success', 'Hosting plan updated successfully.');
    }

    public function destroy(HostingPlan $hostingPlan): RedirectResponse
    {
        abort_if(Gate::denies('hosting_plan_delete'), Response::HTTP_FORBIDDEN, '403 Forbidden');

        $this->deletePlanAction->handle($hostingPlan);

        return to_route('admin.hosting-plans.index')
            ->with('success', 'Hosting plan deleted successfully.');
    }

    private function categories(): Collection
    {
        return HostingCategory::getActiveCategories();
    }
}
