<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Hosting\PlanPrices\DeletePlanPriceAction;
use App\Actions\Hosting\PlanPrices\ListPlanPriceAction;
use App\Actions\Hosting\PlanPrices\StorePlanPriceAction;
use App\Actions\Hosting\PlanPrices\UpdatePlanPriceAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePlanPriceRequest;
use App\Http\Requests\Admin\UpdatePlanPriceRequest;
use App\Models\HostingCategory;
use App\Models\HostingPlan;
use App\Models\HostingPlanPrice;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class HostingPlanPriceController extends Controller
{
    public function index(ListPlanPriceAction $action): View|Factory
    {
        $prices = $action->handle();

        return view('admin.hosting-plan-prices.index', ['prices' => $prices]);
    }

    public function create(): View|Factory
    {
        $categories = HostingCategory::query()->select(['id', 'name'])->orderBy('name')->get();
        $plans = HostingPlan::query()->select(['id', 'name', 'category_id'])->orderBy('name')->get();

        return view('admin.hosting-plan-prices.create', [
            'categories' => $categories,
            'plans' => $plans,
        ]);
    }

    public function store(StorePlanPriceRequest $request, StorePlanPriceAction $action): RedirectResponse
    {
        $data = $request->validated();
        unset($data['hosting_category_id']);

        $action->handle($data);

        return to_route('admin.hosting-plan-prices.index')->with('success', 'Hosting plan price created successfully.');
    }

    public function edit(HostingPlanPrice $hostingPlanPrice): View|Factory
    {
        $categories = HostingCategory::query()->select(['id', 'name'])->orderBy('name')->get();
        $plans = HostingPlan::query()->select(['id', 'name', 'category_id'])->orderBy('name')->get();
        $hostingPlanPrice->load('plan');

        return view('admin.hosting-plan-prices.edit', [
            'price' => $hostingPlanPrice,
            'categories' => $categories,
            'plans' => $plans,
        ]);
    }

    public function update(UpdatePlanPriceRequest $request, HostingPlanPrice $hostingPlanPrice, UpdatePlanPriceAction $action): RedirectResponse
    {
        $data = $request->validated();
        unset($data['hosting_category_id']);

        $action->handle($hostingPlanPrice->uuid, $data);

        return to_route('admin.hosting-plan-prices.index')->with('success', 'Hosting plan price updated successfully.');
    }

    public function destroy(HostingPlanPrice $hostingPlanPrice, DeletePlanPriceAction $action): RedirectResponse
    {
        $action->handle($hostingPlanPrice->uuid);

        return to_route('admin.hosting-plan-prices.index')->with('success', 'Hosting plan price deleted successfully.');
    }
}
